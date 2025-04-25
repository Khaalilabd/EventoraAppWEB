<?php

namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\HttpClient;

#[Route("/user/chat_ai")]
final class ChatAiController extends AbstractController
{
    private string $geminiApiKey;

    public function __construct(string $geminiApiKey)
    {
        $this->geminiApiKey = $geminiApiKey;
    }

    #[Route('/', name: 'user_chat_ai', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/chat_ai/index.html.twig', [
            'controller_name' => 'ChatAiController',
        ]);
    }

    #[Route('/ask', name: 'user_chat_ai_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifier le jeton CSRF
        $token = $request->headers->get('X-CSRF-Token');
        if (!$this->isCsrfTokenValid('chat_ai', $token)) {
            return new JsonResponse(['error' => 'Jeton CSRF invalide'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        try {
            // Liste des mots-clés autorisés
            $allowedKeywords = [
                'eventora', 'gestion des reservations', 'gestion des packs', 'gestion des services',
                'gestion des reclamations', 'tableau de bord', 'statistique', 'gestion des événements',
                'feedback', 'partenaire', 'profil utilisateur', 'expérience utilisateur', 'interface utilisateur',
                'evenement', 'place', 'hello', 'salut'
            ];

            // Définir le prompt système avec les mots-clés autorisés
            $systemPrompt = "Tu es Evi, l'assistant du site Eventora. Ton rôle est d'aider les utilisateurs uniquement sur les sujets suivants : " . implode(', ', $allowedKeywords) . ". Si une question ne concerne pas ces sujets, réponds : 'Désolé, je ne peux répondre qu'aux questions liées à Eventora et ses fonctionnalités (réservations, packs, services, etc.).' Ne réponds pas aux questions hors sujet, même si elles sont générales ou personnelles.";

            // Combiner le prompt système avec le message de l'utilisateur
            $fullMessage = $systemPrompt . "\n\nUtilisateur : " . $message;

            $client = HttpClient::create();
            $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->geminiApiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $fullMessage]
                            ]
                        ]
                    ]
                ]
            ]);

            $result = $response->toArray();
            $generatedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune réponse générée.';

            // Vérification supplémentaire pour s'assurer que la réponse est pertinente
            if (stripos($generatedText, 'Désolé, je ne peux répondre qu') !== false || $this->isResponseRelevant($generatedText, $allowedKeywords)) {
                return new JsonResponse(['response' => $generatedText]);
            } else {
                return new JsonResponse(['response' => 'Désolé, je ne peux répondre qu\'aux questions liées à Eventora et ses fonctionnalités (réservations, packs, services, etc.).']);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la communication avec l\'API : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Vérifie si la réponse générée est pertinente pour les mots-clés autorisés
     * @param string $response
     * @param array $keywords
     * @return bool
     */
    private function isResponseRelevant(string $response, array $keywords): bool
    {
        // Convertir la réponse en minuscules pour la comparaison
        $responseLower = strtolower($response);

        // Vérifier si la réponse contient au moins un mot-clé autorisé
        foreach ($keywords as $keyword) {
            if (stripos($responseLower, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }
}
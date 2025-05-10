<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiResponseGenerator
{
    private $httpClient;
    private $logger;
    private $apiKey;
    private $apiEndpoint;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $apiKey, string $apiEndpoint)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
        $this->apiEndpoint = $apiEndpoint;
    }

    public function generateResponse(string $titre, string $description, string $type): ?string
    {
        $prompt = "Vous êtes un assistant professionnel chargé de rédiger des réponses aux réclamations clients. Rédigez une réponse polie, concise et adaptée à la réclamation suivante. Titre : {$titre}. Description : {$description}. Type : {$type}. La réponse doit être en français, professionnelle, et proposer une solution ou une explication claire.";

        try {
            $response = $this->httpClient->request('POST', $this->apiEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'key' => $this->apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 300,
                        'temperature' => 0.7,
                    ],
                ],
            ]);

            $data = $response->toArray();
            $generatedResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$generatedResponse) {
                $this->logger->error('Aucune réponse générée par l\'API Gemini.', ['response' => $data]);
                return "Cher(e) client(e), nous sommes désolés pour l'inconvénient causé. Veuillez nous fournir plus de détails pour que nous puissions vous aider.";
            }

            return $generatedResponse;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'appel à l\'API Gemini : ' . $e->getMessage(), ['exception' => $e]);
            return "Cher(e) client(e), nous sommes désolés pour l'inconvénient causé. Veuillez nous fournir plus de détails pour que nous puissions vous aider.";
        }
    }

    public function generate(string $summary): string
    {
        $prompt = "Vous êtes un assistant IA chargé d'analyser des réclamations clients et de proposer des suggestions pour améliorer les services. Basé sur le résumé suivant des réclamations : '{$summary}', proposez une suggestion concise et actionable en français pour résoudre les problèmes identifiés ou améliorer l'expérience client.";

        try {
            $response = $this->httpClient->request('POST', $this->apiEndpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'query' => [
                    'key' => $this->apiKey,
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 200,
                        'temperature' => 0.7,
                    ],
                ],
            ]);

            $data = $response->toArray();
            $generatedSuggestion = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$generatedSuggestion) {
                $this->logger->error('Aucune suggestion générée par l\'API Gemini.', ['response' => $data]);
                return "Aucune suggestion disponible. Veuillez analyser les réclamations manuellement.";
            }

            return $generatedSuggestion;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération de suggestion via l\'API Gemini : ' . $e->getMessage(), ['exception' => $e]);
            return "Aucune suggestion disponible en raison d'une erreur technique.";
        }
    }
}
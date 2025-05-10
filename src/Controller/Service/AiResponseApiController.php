<?php
namespace App\Controller\Service;

use App\Service\AiResponseGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AiResponseApiController extends AbstractController
{
    #[Route('/generate-suggestion', name: 'api_generate_suggestion', methods: ['POST'])]
    public function generate(Request $request, AiResponseGenerator $aiResponseGenerator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $summary = $data['summary'] ?? '';

        if (empty($summary)) {
            return $this->json(['error' => 'Summary is required'], 400);
        }

        try {
            $suggestion = $aiResponseGenerator->generate($summary);
            return $this->json(['suggestion' => $suggestion]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to generate suggestion: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/generate-response', name: 'api_generate_response', methods: ['POST'])]
    public function generateResponse(Request $request, AiResponseGenerator $aiResponseGenerator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $titre = $data['titre'] ?? '';
        $description = $data['description'] ?? '';
        $type = $data['type'] ?? '';

        if (empty($titre) || empty($description) || empty($type)) {
            return $this->json(['error' => 'Titre, description, and type are required'], 400);
        }

        try {
            $response = $aiResponseGenerator->generateResponse($titre, $description, $type);
            return $this->json(['response' => $response]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to generate response: ' . $e->getMessage()], 500);
        }
    }
}
<?php
namespace App\Controller\Service;

use App\Repository\ReclamationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ReclamationApiController extends AbstractController
{
    #[Route('/reclamations', name: 'api_reclamations', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): JsonResponse
    {
        $reclamations = $reclamationRepository->findAll();
        $data = array_map(fn($r) => [
            'id' => $r->getId(),
            'titre' => $r->getTitre(),
            'description' => $r->getDescription(),
            'type' => $r->getType(),
            'statut' => $r->getStatut(),
        ], $reclamations);

        return $this->json($data);
    }
}
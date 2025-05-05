<?php
namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GServiceRepository;
use App\Entity\GService;
use Psr\Log\LoggerInterface;

#[Route("/user/service")]
final class ServiceController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'user_services', methods: ['GET'])]
    public function index(GServiceRepository $GServiceRepository): Response
    {
        $GServices = $GServiceRepository->findAll();
        
        // Vérifier que chaque service a une image ou assigner une image par défaut
        foreach ($GServices as $service) {
            if (!$service->getImage()) {
                $service->setImage('service/default.jpg');
            }
        }

        return $this->render('user/service/index.html.twig', [
            'GServices' => $GServices,
        ]);
    }

    #[Route('/{id}/details', name: 'user_service_details', methods: ['GET'])]
    public function getServiceDetails(int $id, GServiceRepository $serviceRepository): JsonResponse
    {
        try {
            $service = $serviceRepository->find($id);
            if (!$service) {
                return new JsonResponse([
                    'success' => false,
                    'message' => sprintf('Service avec ID %d non trouvé.', $id)
                ], 404);
            }

            // Pour les images, utilise simplement le chemin relatif
            $imagePath = $service->getImage();
            
            return new JsonResponse([
                'success' => true,
                'service' => [
                    'titre' => $service->getTitre() ?? 'Non spécifié',
                    'description' => $service->getDescription() ?? 'Non spécifiée',
                    'prix' => $service->getPrix() ?? 'Non spécifié',
                    'location' => $service->getLocation() ?? 'Non spécifiée',
                    'type_service' => $service->getTypeService() ?? 'Non spécifié',
                    'image' => $imagePath ?? 'service/default.jpg',
                    'partenaire' => $service->getSponsor() ? $service->getSponsor()->getNomPartenaire() : 'Non spécifié',
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des détails du service ID ' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }
}
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
        $imageDirectory = $this->getParameter('kernel.project_dir') . '/public/service/';
        $imageFiles = glob($imageDirectory . '*.{jpg,png}', GLOB_BRACE);

        if (!empty($imageFiles)) {
            foreach ($GServices as $service) {
                $randomImage = $imageFiles[array_rand($imageFiles)];
                $service->setImage('service/' . basename($randomImage));
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
            $imagePath = $service->getImage();
            $imageUrl = $imagePath ? $this->getParameter('app.base_url') . '/' . ltrim($imagePath, '/') : null;
            return new JsonResponse([
                'success' => true,
                'service' => [
                    'titre' => $service->getTitre() ?? 'Non spécifié',
                    'description' => $service->getDescription() ?? 'Non spécifiée',
                    'prix' => $service->getPrix() ?? 'Non spécifié',
                    'location' => $service->getLocation() ?? 'Non spécifiée',
                    'type_service' => $service->getTypeService() ?? 'Non spécifié',
                    'image' => $imageUrl,
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
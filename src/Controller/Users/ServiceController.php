<?php

namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\GServiceRepository;
use App\Entity\GService;
#[Route("/user/service")]
final class ServiceController extends AbstractController
{
    #[Route('/', name: 'user_services', methods: ['GET'])]
    public function index(GServiceRepository $GServiceRepository): Response
    {
       
        $GServices = $GServiceRepository->findAll();
         // Répertoire où se trouvent les images
         $imageDirectory = $this->getParameter('kernel.project_dir') . '/public/service/';

         // Récupérer toutes les images dans le répertoire
         $imageFiles = glob($imageDirectory . '*.png');  // Récupère toutes les images .jpg
         
         // Si on trouve des images
         if (!empty($imageFiles)) {
             foreach ($GServices as $service) {
                 // Choisir une image aléatoire
                 $randomImage = $imageFiles[array_rand($imageFiles)];
                 // Associer l'URL de l'image au service
                 $service->setImage('service/' . basename($randomImage));
             }
         }

        return $this->render('user/service/index.html.twig', [
            'GServices' => $GServices,
        ]);
    }
   
}

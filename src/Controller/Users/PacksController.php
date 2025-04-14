<?php

namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\PackRepository;
use App\Entity\Pack;

#[Route("/user/packs")]
final class PacksController extends AbstractController
{
    #[Route('/', name: 'user_packs', methods: ['GET'])]
    public function index(PackRepository $packRepository): Response
    {
        $packs = $packRepository->findAll();

        return $this->render('user/packs/index.html.twig', [
            'packs' => $packs,
        ]);
    }

    #[Route('/{id}', name: 'user_pack_details', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function details(int $id, PackRepository $packRepository): Response
    {
        $pack = $packRepository->find($id);

        if (!$pack) {
            throw $this->createNotFoundException('Pack not found');
        }

        return $this->render('user/packs/pack_details.html.twig', [
            'pack' => $pack,
        ]);
    }
}
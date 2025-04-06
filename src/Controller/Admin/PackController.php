<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Pack;
use App\Repository\PackRepository;

#[Route("/admin/pack")]
final class PackController extends AbstractController
{
    #[Route('/', name: 'admin_packs', methods: ['GET'])]
    public function index(PackRepository $packRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $packs = $packRepository->findAll();

        return $this->render('admin/Pack/index.html.twig', [
            'packs' => $packs,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_packs_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pack $pack): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Placeholder: Render the edit template without processing
        return $this->render('admin/Pack/edit.html.twig', [
            'pack' => $pack,
        ]);
    }

    #[Route('/{id}', name: 'admin_packs_delete', methods: ['POST'])]
    public function delete(Request $request, Pack $pack): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Placeholder: Redirect back without deleting
        $this->addFlash('info', 'Suppression en attente de mise en œuvre.');
        return $this->redirectToRoute('admin_packs');
    }
}
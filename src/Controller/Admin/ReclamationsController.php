<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Form\ReclamationStatusType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

#[Route('/admin/reclamations')]
class ReclamationsController extends AbstractController
{
    #[Route('/', name: 'admin_reclamations', methods: ['GET'])]
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $reclamations = $reclamationRepository->findAll();

        return $this->render('admin/reclamations/index.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

    #[Route('/{id}/edit-status', name: 'admin_reclamations_edit_status', methods: ['GET', 'POST'])]
    public function editStatus(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ReclamationStatusType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            $this->addFlash('success', 'Statut mis à jour avec succès. Nouveau statut : ' . $reclamation->getStatut());
            return $this->redirectToRoute('admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reclamations/edit_status.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_reclamations_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_reclamations', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/qr', name: 'admin_reclamations_show_qr', methods: ['GET'])]
    public function showQr(Reclamation $reclamation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('admin/reclamations/show_qr.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/generate-qr', name: 'admin_reclamations_generate_qr', methods: ['GET'])]
    public function generateQr(Reclamation $reclamation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $qrCode = QrCode::create("Réclamation #{$reclamation->getId()} - {$reclamation->getTitre()}")
            ->setSize(200)
            ->setMargin(10);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return new Response($result->getString(), Response::HTTP_OK, ['Content-Type' => 'image/png']);
    }
}
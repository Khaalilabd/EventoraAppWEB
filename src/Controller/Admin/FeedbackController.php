<?php

namespace App\Controller\Admin;

use App\Entity\Feedback;
use App\Form\FeedbackRecommendType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/feedback')]
class FeedbackController extends AbstractController
{
    #[Route('/', name: 'admin_feedback', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $feedbacks = $feedbackRepository->findAll();

        return $this->render('admin/feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }

    #[Route('/{id}/edit-recommend', name: 'admin_feedback_edit_recommend', methods: ['GET', 'POST'])]
    public function editRecommend(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->createForm(FeedbackRecommendType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newRecommend = $feedback->getRecommend();
            $entityManager->persist($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Recommandation mise à jour avec succès. Nouvelle valeur : ' . ($newRecommend === 'oui' ? 'Oui' : 'Non'));
            return $this->redirectToRoute('admin_feedback', [], Response::HTTP_SEE_OTHER); // Corrigé ici
        }

        return $this->render('admin/feedback/edit_recommend.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }
}
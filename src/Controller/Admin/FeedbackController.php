<?php

namespace App\Controller\Admin;

use App\Entity\Feedback;
use App\Form\FeedbackType;
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
    public function index(Request $request, FeedbackRepository $feedbackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupérer les paramètres de tri depuis la requête
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        // Validation des paramètres de tri
        $validSortFields = ['membre.email', 'vote', 'date', 'recommend'];
        $validSortOrders = ['asc', 'desc'];

        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'date';
        }
        if (!in_array($sortOrder, $validSortOrders)) {
            $sortOrder = 'desc';
        }

        // Construire la requête de tri
        $queryBuilder = $feedbackRepository->createQueryBuilder('f')
            ->leftJoin('f.membre', 'm');

        switch ($sortBy) {
            case 'membre.email':
                $queryBuilder->orderBy('m.email', $sortOrder);
                break;
            case 'vote':
                $queryBuilder->orderBy('f.vote', $sortOrder);
                break;
            case 'date':
                $queryBuilder->orderBy('f.date', $sortOrder);
                break;
            case 'recommend':
                $queryBuilder->orderBy('f.recommend', $sortOrder);
                break;
        }

        $feedbacks = $queryBuilder->getQuery()->getResult();

        return $this->render('admin/feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }

    #[Route('/{id}', name: 'admin_feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_feedback_edit_recommend', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Créer le formulaire sans passer l'option 'data'
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Feedback mis à jour avec succès.');

            return $this->redirectToRoute('admin_feedback', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/feedback/edit_recommend.html.twig', [
            'feedback' => $feedback,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Feedback supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_feedback', [], Response::HTTP_SEE_OTHER);
    }
}
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

        // Récupérer les paramètres de tri/filtrage et pagination depuis la requête
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');
        $selectedUser = $request->query->get('user_filter', null);
        $selectedDate = $request->query->get('date_filter', null);
        $page = $request->query->getInt('page', 1); // Page actuelle (par défaut : 1)
        $limit = 5; // Nombre de feedbacks par page

        // Validation des paramètres de tri
        $validSortFields = ['membre.email', 'Vote', 'date', 'recommend'];
        $validSortOrders = ['asc', 'desc'];

        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'date';
        }
        if (!in_array($sortOrder, $validSortOrders)) {
            $sortOrder = 'desc';
        }

        // Construire la requête de base pour les feedbacks paginés
        $queryBuilder = $feedbackRepository->createQueryBuilder('f')
            ->leftJoin('f.membre', 'm')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        // Filtrer par utilisateur si sélectionné
        if ($selectedUser) {
            $queryBuilder->andWhere('m.email = :email')
                         ->setParameter('email', $selectedUser);
        }

        // Filtrer par date si sélectionnée
        if ($selectedDate) {
            $queryBuilder->andWhere('DATE(f.date) = :selected_date')
                         ->setParameter('selected_date', $selectedDate);
        }

        // Appliquer le tri
        switch ($sortBy) {
            case 'membre.email':
                $queryBuilder->orderBy('m.email', $sortOrder);
                break;
            case 'Vote':
                $queryBuilder->orderBy('f.Vote', $sortOrder);
                break;
            case 'date':
                $queryBuilder->orderBy('f.date', $sortOrder);
                break;
            case 'recommend':
                $queryBuilder->orderBy('f.recommend', $sortOrder);
                break;
        }

        $feedbacks = $queryBuilder->getQuery()->getResult();

        // Calculer le nombre total de feedbacks pour la pagination
        $countQueryBuilder = $feedbackRepository->createQueryBuilder('f')
            ->leftJoin('f.membre', 'm')
            ->select('COUNT(f.ID)');

        if ($selectedUser) {
            $countQueryBuilder->andWhere('m.email = :email')
                             ->setParameter('email', $selectedUser);
        }

        if ($selectedDate) {
            $countQueryBuilder->andWhere('DATE(f.date) = :selected_date')
                             ->setParameter('selected_date', $selectedDate);
        }

        $totalFeedbacks = $countQueryBuilder->getQuery()->getSingleScalarResult();
        $totalPages = ceil($totalFeedbacks / $limit);

        // Récupérer la liste des utilisateurs uniques pour le menu déroulant
        $users = $feedbackRepository->createQueryBuilder('f')
            ->leftJoin('f.membre', 'm')
            ->select('DISTINCT m.email')
            ->where('m.email IS NOT NULL')
            ->orderBy('m.email', 'ASC')
            ->getQuery()
            ->getResult();

        $userEmails = array_column($users, 'email');

        // Traiter chaque feedback pour encoder le BLOB souvenirs en base64
        $processedFeedbacks = [];
        foreach ($feedbacks as $feedback) {
            $processedFeedback = [
                'ID' => $feedback->getId(),
                'membre' => $feedback->getMembre(),
                'Vote' => $feedback->getVote(),
                'description' => $feedback->getDescription(),
                'date' => $feedback->getDate(),
                'recommend' => $feedback->getRecommend(),
                'souvenirsBase64' => null,
            ];

            $souvenirs = $feedback->getSouvenirs();
            if ($souvenirs) {
                if (is_resource($souvenirs)) {
                    $souvenirsData = stream_get_contents($souvenirs);
                    if ($souvenirsData === false) {
                        continue;
                    }
                } else {
                    $souvenirsData = $souvenirs;
                }
                $base64 = base64_encode($souvenirsData);
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($souvenirsData);
                $processedFeedback['souvenirsBase64'] = 'data:' . $mimeType . ';base64,' . $base64;
            }

            $processedFeedbacks[] = $processedFeedback;
        }

        return $this->render('admin/feedback/index.html.twig', [
            'feedbacks' => $processedFeedbacks,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'selected_user' => $selectedUser,
            'selected_date' => $selectedDate,
            'users' => $userEmails,
            'current_page' => $page,
            'total_pages' => $totalPages,
        ]);
    }

    #[Route('/{id}', name: 'admin_feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $processedFeedback = [
            'ID' => $feedback->getId(),
            'membre' => $feedback->getMembre(),
            'Vote' => $feedback->getVote(),
            'description' => $feedback->getDescription(),
            'date' => $feedback->getDate(),
            'recommend' => $feedback->getRecommend(),
            'souvenirsBase64' => null,
        ];

        $souvenirs = $feedback->getSouvenirs();
        if ($souvenirs) {
            if (is_resource($souvenirs)) {
                $souvenirsData = stream_get_contents($souvenirs);
                if ($souvenirsData === false) {
                    $this->addFlash('error', 'Impossible de lire l\'image associée au feedback.');
                } else {
                    $base64 = base64_encode($souvenirsData);
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($souvenirsData);
                    $processedFeedback['souvenirsBase64'] = 'data:' . $mimeType . ';base64,' . $base64;
                }
            } else {
                $base64 = base64_encode($souvenirs);
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($souvenirs);
                $processedFeedback['souvenirsBase64'] = 'data:' . $mimeType . ';base64,' . $base64;
            }
        }

        return $this->render('admin/feedback/show.html.twig', [
            'feedback' => $processedFeedback,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_feedback_edit_recommend', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
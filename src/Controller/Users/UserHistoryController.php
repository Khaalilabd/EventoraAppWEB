<?php

namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReclamationRepository;
use App\Repository\FeedbackRepository;
use Symfony\Component\Security\Core\Security;
use App\Entity\Reclamation;
use App\Entity\Feedback;
use Knp\Component\Pager\PaginatorInterface;

class UserHistoryController extends AbstractController
{
    private $security;
    private $reclamationRepository;
    private $feedbackRepository;
    private $paginator;

    public function __construct(
        Security $security,
        ReclamationRepository $reclamationRepository,
        FeedbackRepository $feedbackRepository,
        PaginatorInterface $paginator
    ) {
        $this->security = $security;
        $this->reclamationRepository = $reclamationRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->paginator = $paginator;
    }

    #[Route('/user/history', name: 'app_user_history', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        if (!$user instanceof \App\Entity\Membre) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        // Récupérer les réclamations triées par statut et date
        $queryBuilder = $this->reclamationRepository->createQueryBuilder('r')
            ->where('r.membre = :user')
            ->setParameter('user', $user)
            ->orderBy('r.statut', 'ASC')
            ->addOrderBy('r.date', 'DESC');

        // Pagination
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/history.html.twig', [
            'reclamations' => $pagination,
            'selected_reclamation' => null,
            'feedbacks' => null,
            'selected_feedback' => null,
            'section' => 'reclamations',
        ]);
    }

    #[Route('/user/reclamation/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function showReclamation(Reclamation $reclamation, Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user || $user !== $reclamation->getMembre()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette réclamation.');
        }

        // Récupérer les réclamations pour la pagination
        $queryBuilder = $this->reclamationRepository->createQueryBuilder('r')
            ->where('r.membre = :user')
            ->setParameter('user', $user)
            ->orderBy('r.statut', 'ASC')
            ->addOrderBy('r.date', 'DESC');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/history.html.twig', [
            'reclamations' => $pagination,
            'selected_reclamation' => $reclamation,
            'feedbacks' => null,
            'selected_feedback' => null,
            'section' => 'reclamations',
        ]);
    }

    #[Route('/user/history/feedbacks', name: 'app_user_history_feedbacks', methods: ['GET'])]
    public function feedbacks(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        if (!$user instanceof \App\Entity\Membre) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        // Récupérer les feedbacks triés par vote et date
        $queryBuilder = $this->feedbackRepository->createQueryBuilder('f')
            ->where('f.membre = :user')
            ->setParameter('user', $user)
            ->orderBy('f.Vote', 'DESC')
            ->addOrderBy('f.date', 'DESC');

        // Pagination
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/history.html.twig', [
            'reclamations' => null,
            'selected_reclamation' => null,
            'feedbacks' => $pagination,
            'selected_feedback' => null,
            'section' => 'feedbacks',
        ]);
    }

    #[Route('/user/feedback/{id}', name: 'app_feedback_show', methods: ['GET'])]
    public function showFeedback(Feedback $feedback, Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user || $user !== $feedback->getMembre()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir ce feedback.');
        }

        // Récupérer les feedbacks pour la pagination
        $queryBuilder = $this->feedbackRepository->createQueryBuilder('f')
            ->where('f.membre = :user')
            ->setParameter('user', $user)
            ->orderBy('f.Vote', 'DESC')
            ->addOrderBy('f.date', 'DESC');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/history.html.twig', [
            'reclamations' => null,
            'selected_reclamation' => null,
            'feedbacks' => $pagination,
            'selected_feedback' => $feedback,
            'section' => 'feedbacks',
        ]);
    }
}
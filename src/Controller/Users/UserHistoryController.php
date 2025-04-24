<?php

namespace App\Controller\Users;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReclamationRepository;
use App\Repository\FeedbackRepository;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use Symfony\Component\Security\Core\Security;
use App\Entity\Reclamation;
use App\Entity\Feedback;
use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use Knp\Component\Pager\PaginatorInterface;

class UserHistoryController extends AbstractController
{
    private $security;
    private $reclamationRepository;
    private $feedbackRepository;
    private $reservationpackRepository;
    private $reservationpersonnaliseRepository;
    private $paginator;

    public function __construct(
        Security $security,
        ReclamationRepository $reclamationRepository,
        FeedbackRepository $feedbackRepository,
        ReservationpackRepository $reservationpackRepository,
        ReservationpersonnaliseRepository $reservationpersonnaliseRepository,
        PaginatorInterface $paginator
    ) {
        $this->security = $security;
        $this->reclamationRepository = $reclamationRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->reservationpackRepository = $reservationpackRepository;
        $this->reservationpersonnaliseRepository = $reservationpersonnaliseRepository;
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
            'reservations' => null,
            'selected_reservation' => null,
            'reservation_type' => null,
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
            'reservations' => null,
            'selected_reservation' => null,
            'reservation_type' => null,
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
            'reservations' => null,
            'selected_reservation' => null,
            'reservation_type' => null,
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
            'reservations' => null,
            'selected_reservation' => null,
            'reservation_type' => null,
            'section' => 'feedbacks',
        ]);
    }

    #[Route('/user/feedback/image/{id}', name: 'app_feedback_image', methods: ['GET'])]
    public function getFeedbackImage(Feedback $feedback): Response
    {
        $user = $this->security->getUser();
        if (!$user || $user !== $feedback->getMembre()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette image.');
        }

        $souvenirs = $feedback->getSouvenirs();
        if (!$souvenirs) {
            throw $this->createNotFoundException('Aucune image trouvée pour ce feedback.');
        }

        // Déterminer le type MIME
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer(stream_get_contents($souvenirs));

        $response = new Response(stream_get_contents($souvenirs));
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'inline; filename="feedback_image"');

        return $response;
    }

    #[Route('/user/history/reservations/{type?}', name: 'app_user_history_reservations', methods: ['GET'])]
    public function reservations(Request $request, ?string $type = null): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        if (!$user instanceof \App\Entity\Membre) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        $userId = $user->getId();
        $reservations = [];

        if ($type === 'pack') {
            $packReservations = $this->reservationpackRepository->findBy(['membre' => $userId]);
            foreach ($packReservations as $reservation) {
                $reservations[] = [
                    'type' => 'Pack',
                    'IDReservationPack' => $reservation->getIDReservationPack(),
                    'IDReservationPersonalise' => null,
                    'nom' => $reservation->getNom(),
                    'prenom' => $reservation->getPrenom(),
                    'date' => $reservation->getDate(),
                    'pack' => $reservation->getPack(),
                    'services' => [],
                    'status' => $reservation->getStatus(),
                    'description' => $reservation->getDescription(),
                ];
            }
        } elseif ($type === 'personnalise') {
            $personaliseReservations = $this->reservationpersonnaliseRepository->findBy(['membre' => $userId]);
            foreach ($personaliseReservations as $reservation) {
                $reservations[] = [
                    'type' => 'Personnalisée',
                    'IDReservationPack' => null,
                    'IDReservationPersonalise' => $reservation->getIDReservationPersonalise(),
                    'nom' => $reservation->getNom(),
                    'prenom' => $reservation->getPrenom(),
                    'date' => $reservation->getDate(),
                    'pack' => null,
                    'services' => $reservation->getServices(),
                    'status' => $reservation->getStatus(),
                    'description' => $reservation->getDescription(),
                ];
            }
        } else {
            // Afficher les deux types par défaut
            $packReservations = $this->reservationpackRepository->findBy(['membre' => $userId]);
            $personaliseReservations = $this->reservationpersonnaliseRepository->findBy(['membre' => $userId]);

            foreach ($packReservations as $reservation) {
                $reservations[] = [
                    'type' => 'Pack',
                    'IDReservationPack' => $reservation->getIDReservationPack(),
                    'IDReservationPersonalise' => null,
                    'nom' => $reservation->getNom(),
                    'prenom' => $reservation->getPrenom(),
                    'date' => $reservation->getDate(),
                    'pack' => $reservation->getPack(),
                    'services' => [],
                    'status' => $reservation->getStatus(),
                    'description' => $reservation->getDescription(),
                ];
            }
            foreach ($personaliseReservations as $reservation) {
                $reservations[] = [
                    'type' => 'Personnalisée',
                    'IDReservationPack' => null,
                    'IDReservationPersonalise' => $reservation->getIDReservationPersonalise(),
                    'nom' => $reservation->getNom(),
                    'prenom' => $reservation->getPrenom(),
                    'date' => $reservation->getDate(),
                    'pack' => null,
                    'services' => $reservation->getServices(),
                    'status' => $reservation->getStatus(),
                    'description' => $reservation->getDescription(),
                ];
            }
        }

        $pagination = $this->paginator->paginate(
            $reservations,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/history.html.twig', [
            'reclamations' => null,
            'selected_reclamation' => null,
            'feedbacks' => null,
            'selected_feedback' => null,
            'reservations' => $pagination,
            'selected_reservation' => null,
            'reservation_type' => $type,
            'section' => 'reservations',
        ]);
    }

    #[Route('/user/reservation/{id}/{type}', name: 'app_reservation_show', methods: ['GET'])]
    public function showReservation(int $id, string $type, Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        if (!$user instanceof \App\Entity\Membre) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        $reservation = null;
        if ($type === 'pack') {
            $reservation = $this->reservationpackRepository->find($id);
            if (!$reservation || $reservation->getMembre()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette réservation.');
            }
        } elseif ($type === 'personnalise') {
            $reservation = $this->reservationpersonnaliseRepository->find($id);
            if (!$reservation || $reservation->getMembre()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette réservation.');
            }
        } else {
            throw $this->createNotFoundException('Type de réservation invalide.');
        }

        $userId = $user->getId();
        $reservations = [];

        if ($type === 'pack') {
            $packReservations = $this->reservationpackRepository->findBy(['membre' => $userId]);
            foreach ($packReservations as $packReservation) {
                $reservations[] = [
                    'type' => 'Pack',
                    'IDReservationPack' => $packReservation->getIDReservationPack(),
                    'IDReservationPersonalise' => null,
                    'nom' => $packReservation->getNom(),
                    'prenom' => $packReservation->getPrenom(),
                    'date' => $packReservation->getDate(),
                    'pack' => $packReservation->getPack(),
                    'services' => [],
                    'status' => $packReservation->getStatus(),
                    'description' => $packReservation->getDescription(),
                ];
            }
        } elseif ($type === 'personnalise') {
            $personaliseReservations = $this->reservationpersonnaliseRepository->findBy(['membre' => $userId]);
            foreach ($personaliseReservations as $personaliseReservation) {
                $reservations[] = [
                    'type' => 'Personnalisée',
                    'IDReservationPack' => null,
                    'IDReservationPersonalise' => $personaliseReservation->getIDReservationPersonalise(),
                    'nom' => $personaliseReservation->getNom(),
                    'prenom' => $personaliseReservation->getPrenom(),
                    'date' => $personaliseReservation->getDate(),
                    'pack' => null,
                    'services' => $personaliseReservation->getServices(),
                    'status' => $personaliseReservation->getStatus(),
                    'description' => $personaliseReservation->getDescription(),
                ];
            }
        } else {
            $packReservations = $this->reservationpackRepository->findBy(['membre' => $userId]);
            $personaliseReservations = $this->reservationpersonnaliseRepository->findBy(['membre' => $userId]);

            foreach ($packReservations as $packReservation) {
                $reservations[] = [
                    'type' => 'Pack',
                    'IDReservationPack' => $packReservation->getIDReservationPack(),
                    'IDReservationPersonalise' => null,
                    'nom' => $packReservation->getNom(),
                    'prenom' => $packReservation->getPrenom(),
                    'date' => $packReservation->getDate(),
                    'pack' => $packReservation->getPack(),
                    'services' => [],
                    'status' => $packReservation->getStatus(),
                    'description' => $packReservation->getDescription(),
                ];
            }
            foreach ($personaliseReservations as $personaliseReservation) {
                $reservations[] = [
                    'type' => 'Personnalisée',
                    'IDReservationPack' => null,
                    'IDReservationPersonalise' => $personaliseReservation->getIDReservationPersonalise(),
                    'nom' => $personaliseReservation->getNom(),
                    'prenom' => $personaliseReservation->getPrenom(),
                    'date' => $personaliseReservation->getDate(),
                    'pack' => null,
                    'services' => $personaliseReservation->getServices(),
                    'status' => $personaliseReservation->getStatus(),
                    'description' => $personaliseReservation->getDescription(),
                ];
            }
        }

        $pagination = $this->paginator->paginate(
            $reservations,
            $request->query->getInt('page', 1),
            10
        );

        $reservationData = [
            'type' => $type === 'pack' ? 'Pack' : 'Personnalisée',
            'IDReservationPack' => $type === 'pack' ? $reservation->getIDReservationPack() : null,
            'IDReservationPersonalise' => $type === 'personnalise' ? $reservation->getIDReservationPersonalise() : null,
            'nom' => $reservation->getNom(),
            'prenom' => $reservation->getPrenom(),
            'date' => $reservation->getDate(),
            'pack' => $type === 'pack' ? $reservation->getPack() : null,
            'services' => $type === 'personnalise' ? $reservation->getServices() : [],
            'status' => $reservation->getStatus(),
            'description' => $reservation->getDescription(),
        ];

        return $this->render('user/history.html.twig', [
            'reclamations' => null,
            'selected_reclamation' => null,
            'feedbacks' => null,
            'selected_feedback' => null,
            'reservations' => $pagination,
            'selected_reservation' => $reservationData,
            'reservation_type' => $type,
            'section' => 'reservations',
        ]);
    }
}
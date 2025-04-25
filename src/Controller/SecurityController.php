<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Entity\Reclamation;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use App\Repository\ReclamationRepository;
use App\Repository\FeedbackRepository;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;

class SecurityController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_home_page');
    }

    #[Route('/home', name: 'app_home_page')]
    public function homePage(): Response
    {
        return $this->render('home/home.html.twig');
    }

    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(
        AuthenticationUtils $authenticationUtils,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $formConnexion = $this->createForm(LoginFormType::class);
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/auth.html.twig', [
            'registration_form' => $this->createForm(RegistrationFormType::class)->createView(),
            'login_form' => $formConnexion->createView(),
            'error' => $error,
            'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $membre = new Membre();
        $form = $this->createForm(RegistrationFormType::class, $membre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = $userPasswordHasher->hashPassword($membre, $plainPassword);
                $membre->setMotDePasse($hashedPassword);
                $membre->setRole('MEMBRE');
                $membre->setIsConfirmed(false);

                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $membre->setImage($newFilename);
                }

                $entityManager->persist($membre);
                $entityManager->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès !');
                return $this->redirectToRoute('app_auth');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'inscription : ' . $e->getMessage());
            }
        }

        return $this->render('security/register.html.twig', [
            'registration_form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/login-check', name: 'login_check')]
    public function checkLogin(Security $security): Response
    {
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->getUser();
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('app_home_page');
        }
        return $this->redirectToRoute('app_auth');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(
        ReclamationRepository $reclamationRepository,
        FeedbackRepository $feedbackRepository,
        ReservationpackRepository $reservationpackRepository,
        ReservationpersonnaliseRepository $reservationpersonnaliseRepository
    ): Response {
        try {
            $this->logger->info('Début de la méthode index pour admin_dashboard');
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            // Réclamations
            $totalReclamations = $reclamationRepository->count([]);
            $reclamationsTraitees = $reclamationRepository->count(['statut' => Reclamation::STATUT_RESOLU]);
            $pourcentageTraitees = $totalReclamations > 0 ? ($reclamationsTraitees / $totalReclamations) * 100 : 0;
            $pourcentageNonTraitees = 100 - $pourcentageTraitees;

            $reclamationsParType = $reclamationRepository->createQueryBuilder('r')
                ->select('r.Type as type, COUNT(r.id) as count')
                ->groupBy('r.Type')
                ->getQuery()
                ->getResult();
            $allTypes = Reclamation::TYPES;
            $reclamationsParTypeFormatted = array_map(function ($type) use ($reclamationsParType) {
                $found = array_filter($reclamationsParType, fn($item) => $item['type'] === $type);
                return [
                    'type' => $type,
                    'count' => !empty($found) ? reset($found)['count'] : 0
                ];
            }, $allTypes);

            $reclamationsParStatut = $reclamationRepository->createQueryBuilder('r')
                ->select('r.statut, COUNT(r.id) as count')
                ->groupBy('r.statut')
                ->getQuery()
                ->getResult();

            // Feedbacks
            $avgVote = $feedbackRepository->createQueryBuilder('f')
                ->select('AVG(f.Vote) as avgVote')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;
            $avgVoteTrend = 0;

            $recommendOui = $feedbackRepository->count(['Recommend' => 'Oui']);
            $recommendNon = $feedbackRepository->count(['Recommend' => 'Non']);
            $totalFeedbacks = $feedbackRepository->count([]);
            $npsScore = $totalFeedbacks > 0 ? (($recommendOui - $recommendNon) / $totalFeedbacks) * 100 : 0;

            $feedbacksWithImage = $feedbackRepository->createQueryBuilder('f')
                ->select('COUNT(f.ID)')
                ->where('f.Souvenirs IS NOT NULL')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;

            $imageFeedbackRate = $totalFeedbacks > 0 ? ($feedbacksWithImage / $totalFeedbacks) * 100 : 0;

            $feedbacksByVote = $feedbackRepository->createQueryBuilder('f')
                ->select('f.Vote as vote, COUNT(f.ID) as count')
                ->groupBy('f.Vote')
                ->orderBy('f.Vote', 'ASC')
                ->getQuery()
                ->getResult();

            $userEngagement = $feedbackRepository->createQueryBuilder('f')
                ->select('m.email, COUNT(f.ID) as feedbackCount, AVG(f.Vote) as avgVote')
                ->leftJoin('f.membre', 'm')
                ->groupBy('m.id')
                ->orderBy('feedbackCount', 'DESC')
                ->setMaxResults(50)
                ->getQuery()
                ->getResult();

            // Réservations
            $totalPackReservations = $reservationpackRepository->count([]);
            $totalPersonaliseReservations = $reservationpersonnaliseRepository->count([]);
            $totalReservations = $totalPackReservations + $totalPersonaliseReservations;

            $refusedPackReservations = $reservationpackRepository->count(['status' => 'Refusé']);
            $refusedPersonaliseReservations = $reservationpersonnaliseRepository->count(['status' => 'Refusé']);
            $totalRefusedReservations = $refusedPackReservations + $refusedPersonaliseReservations;
            $refusalRate = $totalReservations > 0 ? ($totalRefusedReservations / $totalReservations) * 100 : 0;

            // Valeur moyenne des réservations
            $avgPackValue = $reservationpackRepository->createQueryBuilder('rp')
                ->select('AVG(p.prix) as avgValue')
                ->leftJoin('rp.pack', 'p')
                ->where('rp.status = :status')
                ->setParameter('status', 'Validé')
                ->getQuery()
                ->getSingleScalarResult() ?? 0;

            $avgPersonaliseValue = 0;
            try {
                $avgPersonaliseValue = $reservationpersonnaliseRepository->createQueryBuilder('rp')
                    ->select('AVG(s.prix) as avgValue')
                    ->leftJoin('rp.services', 's')
                    ->where('rp.status = :status')
                    ->setParameter('status', 'Validé')
                    ->getQuery()
                    ->getSingleScalarResult() ?? 0;
            } catch (\Doctrine\ORM\NoResultException $e) {
                $this->logger->warning('No valid personalised reservations found for avgPersonaliseValue: ' . $e->getMessage());
                $avgPersonaliseValue = 0;
            }

            $avgReservationValue = ($avgPackValue + $avgPersonaliseValue) / 2;

            // Réservations par type
            $reservationsByType = [
                ['type' => 'Pack', 'count' => $totalPackReservations],
                ['type' => 'Personnalise', 'count' => $totalPersonaliseReservations]
            ];

            // Réservations par statut
            $packReservationsByStatus = $reservationpackRepository->createQueryBuilder('rp')
                ->select('rp.status as status, COUNT(rp.IDReservationPack) as count')
                ->groupBy('rp.status')
                ->getQuery()
                ->getResult();

            $personaliseReservationsByStatus = $reservationpersonnaliseRepository->createQueryBuilder('rp')
                ->select('rp.status as status, COUNT(rp.IDReservationPersonalise) as count')
                ->groupBy('rp.status')
                ->getQuery()
                ->getResult();

            // Fusionner les statuts
            $reservationsByStatus = [];
            $statuses = ['En attente', 'Validé', 'Refusé'];
            foreach ($statuses as $status) {
                $packCount = array_filter($packReservationsByStatus, fn($item) => $item['status'] === $status);
                $personaliseCount = array_filter($personaliseReservationsByStatus, fn($item) => $item['status'] === $status);
                $totalCount = (!empty($packCount) ? reset($packCount)['count'] : 0) + (!empty($personaliseCount) ? reset($personaliseCount)['count'] : 0);
                $reservationsByStatus[] = ['status' => $status, 'count' => $totalCount];
            }

            // Rendu du template
            return $this->render('admin/dashboard.html.twig', [
                'total_reclamations' => $totalReclamations,
                'pourcentage_traitees' => $pourcentageTraitees,
                'pourcentage_non_traitees' => $pourcentageNonTraitees,
                'reclamations_par_type' => $reclamationsParTypeFormatted,
                'reclamations_par_statut' => $reclamationsParStatut,
                'avg_vote' => $avgVote,
                'avg_vote_trend' => $avgVoteTrend,
                'nps_score' => $npsScore,
                'image_feedback_rate' => $imageFeedbackRate,
                'feedbacks_by_vote' => $feedbacksByVote,
                'user_engagement' => $userEngagement,
                'total_reservations' => $totalReservations,
                'refusal_rate' => $refusalRate,
                'avg_reservation_value' => $avgReservationValue,
                'reservations_by_type' => $reservationsByType,
                'reservations_by_status' => $reservationsByStatus,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur globale dans admin_dashboard: ' . $e->getMessage());
            throw new \Exception('Erreur détaillée : ' . $e->getMessage());
        }
    }
}
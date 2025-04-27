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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordFormType;
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
    public function homePage(FeedbackRepository $feedbackRepository): Response
    {
        // Récupérer des feedbacks aléatoires
        $feedbacks = $feedbackRepository->findRandomFeedbacks(10);
    
        // Ajouter un message flash si aucun feedback n'est trouvé
        if (empty($feedbacks)) {
            $this->addFlash('warning', 'Aucun feedback disponible pour le moment.');
        }
    
        return $this->render('home/home.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }

    // Le reste du code reste inchangé
    #[Route('/auth', name: 'app_auth', methods: ['GET', 'POST'])]
    public function auth(
        AuthenticationUtils $authenticationUtils,
        CsrfTokenManagerInterface $csrfTokenManager,
        Request $request,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ): Response {
        $formConnexion = $this->createForm(LoginFormType::class);
        $error = $authenticationUtils->getLastAuthenticationError();

        // Valider reCAPTCHA uniquement si le formulaire de connexion est soumis
        if ($request->isMethod('POST') && $request->request->has('login_form')) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            if (!$recaptchaResponse) {
                $this->addFlash('error', 'Veuillez cocher la case reCAPTCHA.');
                return $this->render('security/auth.html.twig', [
                    'registration_form' => $this->createForm(RegistrationFormType::class)->createView(),
                    'login_form' => $formConnexion->createView(),
                    'error' => $error,
                    'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
                    'recaptcha_site_key' => $_ENV['EWZ_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }

            try {
                $response = $httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                    'body' => [
                        'secret' => $_ENV['EWZ_RECAPTCHA_SECRET_KEY'] ?? '',
                        'response' => $recaptchaResponse,
                    ],
                ]);
                $recaptchaData = $response->toArray(false);

                $logger->info('reCAPTCHA response', ['data' => $recaptchaData]);

                if (!$recaptchaData['success']) {
                    $errors = $recaptchaData['error-codes'] ?? ['unknown-error'];
                    $this->addFlash('error', 'Échec de la vérification reCAPTCHA : ' . implode(', ', $errors));
                    return $this->render('security/auth.html.twig', [
                        'registration_form' => $this->createForm(RegistrationFormType::class)->createView(),
                        'login_form' => $formConnexion->createView(),
                        'error' => $error,
                        'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
                        'recaptcha_site_key' => $_ENV['EWZ_RECAPTCHA_SITE_KEY'] ?? '',
                    ]);
                }
            } catch (\Exception $e) {
                $logger->error('Erreur lors de la vérification reCAPTCHA : ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la vérification reCAPTCHA.');
                return $this->render('security/auth.html.twig', [
                    'registration_form' => $this->createForm(RegistrationFormType::class)->createView(),
                    'login_form' => $formConnexion->createView(),
                    'error' => $error,
                    'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
                    'recaptcha_site_key' => $_ENV['EWZ_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }
        }

        // Rendre la vue pour GET ou si reCAPTCHA est valide
        return $this->render('security/auth.html.twig', [
            'registration_form' => $this->createForm(RegistrationFormType::class)->createView(),
            'login_form' => $formConnexion->createView(),
            'error' => $error,
            'csrf_token' => $csrfTokenManager->getToken('authenticate')->getValue(),
            'recaptcha_site_key' => $_ENV['EWZ_RECAPTCHA_SITE_KEY'] ?? '',
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

    #[Route('/reset-password', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function requestResetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        \Psr\Log\LoggerInterface $logger
    ): Response {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $logger->info('Email saisi dans le formulaire : ' . $email);

            $membre = $entityManager->getRepository(Membre::class)->findOneBy(['email' => $email]);

            if ($membre) {
                $logger->info('Utilisateur trouvé : ' . $membre->getEmail());
                $token = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 32);
                $logger->info('Token généré : ' . $token);
                $membre->setToken($token);
                $membre->setTokenExpiration(new \DateTime('+1 hour'));
                $entityManager->persist($membre);

                try {
                    $entityManager->flush();
                    $logger->info('Token enregistré avec succès.');
                } catch (\Exception $e) {
                    $logger->error('Erreur lors de l\'enregistrement du token : ' . $e->getMessage());
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement du token : ' . $e->getMessage());
                    return $this->redirectToRoute('app_auth');
                }

                $membreAfterFlush = $entityManager->getRepository(Membre::class)->findOneBy(['email' => $email]);
                $logger->info('Token après flush : ' . ($membreAfterFlush->getToken() ?? 'NULL'));
                $logger->info('Date d\'expiration après flush : ' . ($membreAfterFlush->getTokenExpiration() ? $membreAfterFlush->getTokenExpiration()->format('Y-m-d H:i:s') : 'NULL'));

                $resetLink = $this->generateUrl('app_reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
                $logger->info('Lien de réinitialisation généré : ' . $resetLink);
                $emailMessage = (new Email())
                    ->from('eventoraeventora@gmail.com')
                    ->to($email)
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html("Cliquez sur ce lien pour réinitialiser votre mot de passe : <a href='$resetLink'>$resetLink</a>");

                try {
                    $logger->info('Envoi d\'un email de réinitialisation à : ' . $email);
                    $mailer->send($emailMessage);
                    $this->addFlash('success', 'Un email de réinitialisation a été envoyé à ' . $email . '.');
                } catch (\Exception $e) {
                    $logger->error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
                }

                return $this->redirectToRoute('app_auth');
            } else {
                $logger->error('Aucun utilisateur trouvé avec l\'email : ' . $email);
                $this->addFlash('error', 'Aucun compte trouvé avec cet email.');
            }
        }

        return $this->render('security/reset_password_request.html.twig', [
            'request_form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        \Psr\Log\LoggerInterface $logger
    ): Response {
        $logger->info('Token reçu dans l\'URL : ' . $token);

        $membre = $entityManager->getRepository(Membre::class)->findOneBy(['token' => $token]);

        if (!$membre) {
            $logger->error('Aucun utilisateur trouvé avec le token : ' . $token);
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        $storedToken = $membre->getToken();
        $logger->info('Token stocké dans la base de données : ' . ($storedToken ?? 'NULL'));
        $logger->info('Comparaison des tokens : URL=[' . $token . '], DB=[' . ($storedToken ?? 'NULL') . ']');

        $tokenExpiration = $membre->getTokenExpiration();
        $logger->info('Date d\'expiration du token : ' . ($tokenExpiration ? $tokenExpiration->format('Y-m-d H:i:s') : 'NULL'));

        if ($storedToken !== $token) {
            $logger->error('Les tokens ne correspondent pas : URL=[' . $token . '], DB=[' . ($storedToken ?? 'NULL') . ']');
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        if ($tokenExpiration && $tokenExpiration < new \DateTime()) {
            $logger->error('Token expiré : Date d\'expiration=' . $tokenExpiration->format('Y-m-d H:i:s'));
            $this->addFlash('error', 'Token invalide ou expiré.');
            return $this->redirectToRoute('app_auth');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $hashedPassword = $userPasswordHasher->hashPassword($membre, $newPassword);
            $membre->setMotDePasse($hashedPassword);
            $membre->setToken(null);
            $membre->setTokenExpiration(null);
            $entityManager->persist($membre);
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_auth');
        }

        return $this->render('security/reset_password.html.twig', [
            'reset_form' => $form->createView(),
        ]);
    }

    #[Route('/api/generate-password', name: 'api_generate_password', methods: ['GET'])]
    public function generatePassword(LoggerInterface $logger): Response
    {
        $logger->info('Appel à l\'action generatePassword');
        $charset = [
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numbers' => '0123456789',
            'symbols' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
        ];
        $password = '';
        $lowercaseArray = str_split($charset['lowercase']);
        $uppercaseArray = str_split($charset['uppercase']);
        $numbersArray = str_split($charset['numbers']);
        $symbolsArray = str_split($charset['symbols']);

        $password .= $lowercaseArray[array_rand($lowercaseArray)];
        $password .= $uppercaseArray[array_rand($uppercaseArray)];
        $password .= $numbersArray[array_rand($numbersArray)];
        $password .= $symbolsArray[array_rand($symbolsArray)];

        $allChars = str_split($charset['lowercase'] . $charset['uppercase'] . $charset['numbers'] . $charset['symbols']);
        for ($i = strlen($password); $i < 12; $i++) {
            $password .= $allChars[array_rand($allChars)];
        }

        $password = str_shuffle($password);
        $logger->info('Mot de passe généré localement : ' . $password);
        return $this->json(['password' => $password], 200);
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
        ReservationpersonnaliseRepository $reservationpersonnaliseRepository,
        Request $request
    ): Response {
        try {
            $this->logger->info('Début de la méthode index pour admin_dashboard');
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            // Gestion de la plage de dates pour les réclamations
            $startDate = $request->query->get('startDate');
            $endDate = $request->query->get('endDate');

            // Par défaut, les 30 derniers jours si aucune date n'est fournie
            $endDateObj = $endDate ? new \DateTime($endDate) : new \DateTime();
            $startDateObj = $startDate ? new \DateTime($startDate) : (clone $endDateObj)->modify('-30 days');

            // S'assurer que startDate est avant endDate
            if ($startDateObj > $endDateObj) {
                $temp = $startDateObj;
                $startDateObj = $endDateObj;
                $endDateObj = $temp;
            }

            // Réclamations pour la plage de dates sélectionnée
            $reclamationsByDateRaw = $reclamationRepository->createQueryBuilder('r')
                ->select('r.date as date, COUNT(r.id) as count')
                ->where('r.date BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDateObj->format('Y-m-d'))
                ->setParameter('endDate', $endDateObj->format('Y-m-d'))
                ->groupBy('r.date')
                ->orderBy('r.date', 'ASC')
                ->getQuery()
                ->getResult();

            // Formatter les dates en PHP
            $reclamationsByDate = array_map(function ($item) {
                $date = $item['date'] instanceof \DateTime ? $item['date'] : new \DateTime($item['date']);
                return [
                    'date' => $date->format('Y-m-d'),
                    'count' => (int) $item['count']
                ];
            }, $reclamationsByDateRaw);

            $totalReclamationsSelected = array_sum(array_column($reclamationsByDate, 'count'));

            // Logique existante pour les réclamations
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

            $reservationsByType = [
                ['type' => 'Pack', 'count' => $totalPackReservations],
                ['type' => 'Personnalise', 'count' => $totalPersonaliseReservations]
            ];

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

            $reservationsByStatus = [];
            $statuses = ['En attente', 'Validé', 'Refusé'];
            foreach ($statuses as $status) {
                $packCount = array_filter($packReservationsByStatus, fn($item) => $item['status'] === $status);
                $personaliseCount = array_filter($personaliseReservationsByStatus, fn($item) => $item['status'] === $status);
                $totalCount = (!empty($packCount) ? reset($packCount)['count'] : 0) + (!empty($personaliseCount) ? reset($personaliseCount)['count'] : 0);
                $reservationsByStatus[] = ['status' => $status, 'count' => $totalCount];
            }

            return $this->render('admin/dashboard.html.twig', [
                'total_reclamations' => $totalReclamations,
                'pourcentage_traitees' => $pourcentageTraitees,
                'pourcentage_non_traitees' => $pourcentageNonTraitees,
                'reclamations_par_type' => $reclamationsParTypeFormatted,
                'reclamations_par_statut' => $reclamationsParStatut,
                'reclamations_by_date' => $reclamationsByDate,
                'total_reclamations_selected' => $totalReclamationsSelected,
                'start_date' => $startDateObj->format('Y-m-d'),
                'end_date' => $endDateObj->format('Y-m-d'),
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
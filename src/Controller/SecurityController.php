<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Entity\Reclamation;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use App\Repository\ReclamationRepository;
use App\Repository\MembreRepository;
use App\Repository\GServiceRepository;
use App\Repository\PackRepository;
use App\Repository\SponsorRepository;
use App\Repository\TypepackRepository;
use App\Repository\FeedbackRepository;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
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
use App\Service\TwoFactorAuthService;

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
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient,
        TwoFactorAuthService $twoFactorAuthService
    ): Response {
        $user = new Membre();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'email saisi
            $email = $user->getEmail();

            // Vérifier si l'email existe déjà dans la base de données
            $existingUser = $entityManager->getRepository(Membre::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez utiliser une autre adresse email ou vous connecter.');
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Appeler l'API Ninja pour vérifier la validité de l'email
            try {
                $response = $httpClient->request('GET', 'https://api.api-ninjas.com/v1/validateemail', [
                    'query' => ['email' => $email],
                    'headers' => [
                        'X-Api-Key' => 'jdhXyNvADDDqUg9coiHcfQ==DyCXNx8yocrDHQSA',
                    ],
                ]);

                $emailData = $response->toArray();

                // Vérifier si l'email est valide selon l'API
                if (!$emailData['is_valid']) {
                    $this->addFlash('error', 'L\'adresse email n\'est pas valide selon l\'API Ninja.');
                    return $this->render('security/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

            } catch (\Exception $e) {
                // En cas d'erreur avec l'API, log l'erreur et continue ou échoue selon ton choix
                $this->logger->error('Erreur lors de la vérification de l\'email via API Ninja : ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la vérification de l\'email. Veuillez réessayer.');
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }

            // Si tout est OK, encoder le mot de passe et persister l'utilisateur
            $user->setMotDePasse(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('motDePasse')->getData()
                )
            );

            try {
                $entityManager->persist($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_auth');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur technique : ' . $e->getMessage());
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
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
            if (!$user instanceof Membre) {
                $this->addFlash('error', 'Utilisateur invalide.');
                return $this->redirectToRoute('app_auth');
            }

            // Vérifier si le compte est confirmé
            if (!$user->isConfirmed()) {
                $this->addFlash('error', 'Compte bloqué. Veuillez contacter l\'administrateur.');
                // Déconnecter l'utilisateur
                $this->container->get('security.token_storage')->setToken(null);
                return $this->redirectToRoute('app_auth');
            }

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
        MembreRepository $membreRepository,
        GServiceRepository $gServiceRepository,
        PackRepository $packRepository,
        SponsorRepository $sponsorRepository,
        TypepackRepository $typepackRepository,
        Request $request,
        Connection $connection
    ): Response {
        $this->logger->info('Début de la méthode index pour admin_dashboard');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Configurer DebugStack pour logger les requêtes SQL
        $debugStack = new DebugStack();
        $connection->getConfiguration()->setSQLLogger($debugStack);

        // Gestion de la plage de dates
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $endDateObj = $endDate ? new \DateTime($endDate) : new \DateTime();
        $startDateObj = $startDate ? new \DateTime($startDate) : (clone $endDateObj)->modify('-30 days');

        if ($startDateObj > $endDateObj) {
            $temp = $startDateObj;
            $startDateObj = $endDateObj;
            $endDateObj = $temp;
        }

        // Réclamations
        $reclamationsByDateRaw = $reclamationRepository->createQueryBuilder('r')
            ->select('r.date as date, COUNT(r.id) as count')
            ->where('r.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('r.date')
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();

        $reclamationsByDate = array_map(function ($item) {
            $date = $item['date'] instanceof \DateTime ? $item['date'] : new \DateTime($item['date']);
            return [
                'date' => $date->format('Y-m-d'),
                'count' => (int) $item['count']
            ];
        }, $reclamationsByDateRaw);

        $totalReclamationsSelected = array_sum(array_column($reclamationsByDate, 'count'));
        $totalReclamations = $reclamationRepository->count([]);
        $reclamationsTraitees = $reclamationRepository->count(['statut' => 'Resolue']);
        $pourcentageTraitees = $totalReclamations > 0 ? ($reclamationsTraitees / $totalReclamations) * 100 : 0;
        $pourcentageNonTraitees = 100 - $pourcentageTraitees;

        $reclamationsParType = $reclamationRepository->createQueryBuilder('r')
            ->select('r.Type as type, COUNT(r.id) as count')
            ->where('r.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('r.Type')
            ->getQuery()
            ->getResult();

        $reclamationsParStatut = $reclamationRepository->createQueryBuilder('r')
            ->select('r.statut as statut, COUNT(r.id) as count')
            ->where('r.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('r.statut')
            ->getQuery()
            ->getResult();

        // Feedbacks
        $feedbacks = $feedbackRepository->findBy([], [], 100);
        $avgVote = count($feedbacks) > 0 ? array_sum(array_map(fn($f) => $f->getVote(), $feedbacks)) / count($feedbacks) : 0;
        $feedbacksWithImage = count(array_filter($feedbacks, fn($f) => !is_null($f->getSouvenirs())));
        $imageFeedbackRate = count($feedbacks) > 0 ? ($feedbacksWithImage / count($feedbacks)) * 100 : 0;
        $npsScore = $feedbackRepository->calculateNps($startDateObj, $endDateObj);
        $avgVoteTrend = 0; // À implémenter si historique disponible

        $feedbacksByVote = $feedbackRepository->createQueryBuilder('f')
            ->select('f.Vote as vote, COUNT(f.ID) as count')
            ->where('f.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('f.Vote')
            ->orderBy('f.Vote', 'ASC')
            ->getQuery()
            ->getResult();

        $userEngagement = $feedbackRepository->createQueryBuilder('f')
            ->select('m.email as email, COUNT(f.ID) as feedbackCount')
            ->join('f.membre', 'm')
            ->where('f.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('m.email')
            ->orderBy('feedbackCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Réservations
        $totalReservations = $reservationpackRepository->countAll() + $reservationpersonnaliseRepository->count([]);
        $reservationsByType = [
            ['type' => 'Pack', 'count' => $reservationpackRepository->countAll()],
            ['type' => 'Personnalisé', 'count' => $reservationpersonnaliseRepository->count([])],
        ];
        $reservationsByStatus = $reservationpackRepository->createQueryBuilder('rp')
            ->select('rp.status as status, COUNT(rp.status) as count')
            ->where('rp.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('rp.status')
            ->getQuery()
            ->getResult();

        $refusedReservations = $reservationpackRepository->countByStatus('Refusé') + $reservationpersonnaliseRepository->count(['status' => 'Refusé']);
        $refusalRate = $totalReservations > 0 ? ($refusedReservations / $totalReservations) * 100 : 0;

        $avgReservationValue = $reservationpackRepository->createQueryBuilder('rp')
            ->select('AVG(p.prix) as avgPrice')
            ->join('rp.pack', 'p')
            ->where('rp.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Membres
        $totalMembers = $membreRepository->count([]);
        $activeMembers = $membreRepository->count(['isConfirmed' => true]);
        $membersByRole = $membreRepository->createQueryBuilder('m')
            ->select('m.role as role, COUNT(m.id) as count')
            ->groupBy('m.role')
            ->getQuery()
            ->getResult();
        $recentRegistrations = $membreRepository->createQueryBuilder('m')
            ->select('COUNT(m.id) as count')
            ->where('m.id >= :minId')
            ->setParameter('minId', $membreRepository->createQueryBuilder('m2')
                ->select('MIN(m2.id)')
                ->where('m2.id > :threshold')
                ->setParameter('threshold', max(0, $totalMembers - 100))
                ->getQuery()
                ->getSingleScalarResult() ?? 0)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Services (GService)
        $totalServices = $gServiceRepository->count([]);
        $avgServicePriceResult = $connection->executeQuery(
            "SELECT AVG(prix + 0) as avgPrice 
             FROM g_service 
             WHERE prix REGEXP '^[0-9]+\\.?[0-9]*$'"
        )->fetchOne();
        $avgServicePrice = $avgServicePriceResult ? (float) $avgServicePriceResult : 0;

        $servicesByType = $gServiceRepository->createQueryBuilder('s')
            ->select('s.type_service as type_service, COUNT(s.id) as count')
            ->groupBy('s.type_service')
            ->getQuery()
            ->getResult();
        $topServices = $reservationpersonnaliseRepository->createQueryBuilder('rp')
            ->select('s.titre as titre, COUNT(rp.IDReservationPersonalise) as count')
            ->join('rp.services', 's')
            ->where('rp.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('s.titre')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Packs
        $totalPacks = $packRepository->count([]);
        $avgPackPrice = $packRepository->createQueryBuilder('p')
            ->select('AVG(p.prix) as avgPrice')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $packsByType = $packRepository->countByType();
        $popularPacks = $reservationpackRepository->createQueryBuilder('rp')
            ->select('p.nomPack as nomPack, COUNT(rp.status) as count')
            ->join('rp.pack', 'p')
            ->where('rp.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDateObj->format('Y-m-d'))
            ->setParameter('endDate', $endDateObj->format('Y-m-d'))
            ->groupBy('p.nomPack')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Sponsors
        $totalSponsors = $sponsorRepository->count([]);
        $activeSponsors = $sponsorRepository->createQueryBuilder('sp')
            ->select('COUNT(DISTINCT sp.id_partenaire) as count')
            ->join('sp.gServices', 's')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $avgServicesPerSponsor = $totalSponsors > 0 ? ($gServiceRepository->count([]) / $totalSponsors) : 0;
        $newSponsors = $sponsorRepository->createQueryBuilder('sp')
            ->select('COUNT(sp.id_partenaire) as count')
            ->where('sp.id_partenaire >= :minId')
            ->setParameter('minId', $sponsorRepository->createQueryBuilder('sp2')
                ->select('MIN(sp2.id_partenaire)')
                ->where('sp2.id_partenaire > :threshold')
                ->setParameter('threshold', max(0, $totalSponsors - 50))
                ->getQuery()
                ->getSingleScalarResult() ?? 0)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Logger les requêtes SQL capturées par DebugStack
        foreach ($debugStack->queries as $query) {
            $this->logger->debug('SQL Query: ' . $query['sql'], [
                'params' => $query['params'],
                'executionMS' => $query['executionMS']
            ]);
        }

        return $this->render('admin/dashboard.html.twig', [
            'start_date' => $startDateObj->format('Y-m-d'),
            'end_date' => $endDateObj->format('Y-m-d'),
            'total_reclamations' => $totalReclamations,
            'total_reclamations_selected' => $totalReclamationsSelected,
            'pourcentage_traitees' => $pourcentageTraitees,
            'pourcentage_non_traitees' => $pourcentageNonTraitees,
            'reclamations_by_date' => $reclamationsByDate,
            'reclamations_par_type' => $reclamationsParType,
            'reclamations_par_statut' => $reclamationsParStatut,
            'avg_vote' => $avgVote,
            'nps_score' => $npsScore,
            'image_feedback_rate' => $imageFeedbackRate,
            'avg_vote_trend' => $avgVoteTrend,
            'feedbacks_by_vote' => $feedbacksByVote,
            'user_engagement' => $userEngagement,
            'total_reservations' => $totalReservations,
            'reservations_by_type' => $reservationsByType,
            'reservations_by_status' => $reservationsByStatus,
            'refusal_rate' => $refusalRate,
            'avg_reservation_value' => $avgReservationValue,
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'members_by_role' => $membersByRole,
            'recent_registrations' => $recentRegistrations,
            'total_services' => $totalServices,
            'avg_service_price' => $avgServicePrice,
            'services_by_type' => $servicesByType,
            'top_services' => $topServices,
            'total_packs' => $totalPacks,
            'avg_pack_price' => $avgPackPrice,
            'packs_by_type' => $packsByType,
            'popular_packs' => $popularPacks,
            'total_sponsors' => $totalSponsors,
            'active_sponsors' => $activeSponsors,
            'avg_services_per_sponsor' => $avgServicesPerSponsor,
            'new_sponsors' => $newSponsors,
        ]);
    }
}
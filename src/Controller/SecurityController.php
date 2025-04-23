<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Entity\Reclamation;
use App\Form\RegistrationFormType;
use App\Form\LoginFormType;
use App\Repository\ReclamationRepository;
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
    public function index(ReclamationRepository $reclamationRepository): Response
    {
        try {
            $this->logger->info('Début de la méthode index pour admin_dashboard');

            // Étape 1 : Récupérer le total des réclamations
            $totalReclamations = 0;
            try {
                $totalReclamations = $reclamationRepository->count([]);
                $this->logger->info('Total réclamations: ' . $totalReclamations);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors du comptage des réclamations: ' . $e->getMessage());
            }

            // Étape 2 : Récupérer les réclamations traitées
            $reclamationsTraitees = 0;
            $pourcentageTraitees = 0;
            $pourcentageNonTraitees = 0;
            try {
                $reclamationsTraitees = $reclamationRepository->count(['statut' => Reclamation::STATUT_RESOLU]);
                $pourcentageTraitees = $totalReclamations > 0 ? ($reclamationsTraitees / $totalReclamations) * 100 : 0;
                $pourcentageNonTraitees = 100 - $pourcentageTraitees;
                $this->logger->info('Réclamations traitées: ' . $reclamationsTraitees);
                $this->logger->info('Pourcentage traité: ' . $pourcentageTraitees);
                $this->logger->info('Pourcentage non traité: ' . $pourcentageNonTraitees);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors du calcul des réclamations traitées: ' . $e->getMessage());
            }

            // Étape 3 : Récupérer les réclamations par type
            $reclamationsParTypeFormatted = [];
            try {
                $reclamationsParType = $reclamationRepository->createQueryBuilder('r')
                    ->select('r.Type as type, COUNT(r.id) as count')
                    ->groupBy('r.Type')
                    ->getQuery()
                    ->getResult();
                $this->logger->info('Réclamations par type (brut): ' . json_encode($reclamationsParType));

                // S'assurer que tous les types sont inclus
                $allTypes = Reclamation::TYPES;
                $reclamationsParTypeFormatted = array_map(function ($type) use ($reclamationsParType) {
                    $found = array_filter($reclamationsParType, fn($item) => $item['type'] === $type);
                    return [
                        'type' => $type,
                        'count' => !empty($found) ? reset($found)['count'] : 0
                    ];
                }, $allTypes);
                $this->logger->info('Réclamations par type (formaté): ' . json_encode($reclamationsParTypeFormatted));
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la récupération des réclamations par type: ' . $e->getMessage());
            }

            // Étape 4 : Récupérer les réclamations par statut
            $reclamationsParStatut = [];
            try {
                $reclamationsParStatut = $reclamationRepository->createQueryBuilder('r')
                    ->select('r.statut, COUNT(r.id) as count')
                    ->groupBy('r.statut')
                    ->getQuery()
                    ->getResult();
                $this->logger->info('Réclamations par statut: ' . json_encode($reclamationsParStatut));
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la récupération des réclamations par statut: ' . $e->getMessage());
            }

            // Rendre le template avec les données, même si certaines requêtes ont échoué
            return $this->render('admin/dashboard.html.twig', [
                'total_reclamations' => $totalReclamations,
                'pourcentage_traitees' => $pourcentageTraitees,
                'pourcentage_non_traitees' => $pourcentageNonTraitees,
                'reclamations_par_type' => $reclamationsParTypeFormatted,
                'reclamations_par_statut' => $reclamationsParStatut,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur globale dans admin_dashboard: ' . $e->getMessage());
            throw $this->createNotFoundException('Une erreur est survenue lors du chargement du tableau de bord.');
        }
    }
}
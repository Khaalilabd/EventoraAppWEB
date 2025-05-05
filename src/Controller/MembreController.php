<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Form\MembreType;
use App\Repository\MembreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/admin/membres')]
class MembreController extends AbstractController
{
    private $entityManager;
    private $membreRepository;
    private $passwordHasher;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, MembreRepository $membreRepository, UserPasswordHasherInterface $passwordHasher, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->membreRepository = $membreRepository;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    #[Route('/', name: 'admin_membres', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 4;
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sort_by', 'nom');
        $sortOrder = $request->query->get('sort_order', 'asc');

        // Validate sort_by to prevent SQL injection
        $allowedSortFields = ['nom', 'email', 'dateOfBirth'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'nom';
        }

        $queryBuilder = $this->membreRepository->createQueryBuilder('m')
            ->orderBy("m.$sortBy", $sortOrder);

        if ($searchTerm) {
            $queryBuilder->andWhere('m.nom LIKE :search OR m.prenom LIKE :search OR m.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $totalMembres = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalMembres / $limit);

        $membres = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Calcul des statistiques de genre
        $genderStats = $this->membreRepository->createQueryBuilder('m')
            ->select('m.gender, COUNT(m.id) as count')
            ->groupBy('m.gender')
            ->getQuery()
            ->getResult();

        $stats = [
            'Homme' => 0,
            'Femme' => 0,
            'total' => $totalMembres
        ];

        foreach ($genderStats as $stat) {
            if ($stat['gender'] === 'Homme') {
                $stats['Homme'] = $stat['count'];
            } elseif ($stat['gender'] === 'Femme') {
                $stats['Femme'] = $stat['count'];
            }
        }

        return $this->render('admin/membres/index.html.twig', [
            'membres' => $membres,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'searchTerm' => $searchTerm,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'gender_stats' => $stats
        ]);
    }

    #[Route('/moderators', name: 'admin_moderators', methods: ['GET'])]
    public function moderators(): Response
    {
        $moderators = $this->membreRepository->findByRole('AGENT');

        return $this->render('admin/membres/moderators.html.twig', [
            'moderators' => $moderators,
        ]);
    }

    #[Route('/admins', name: 'admin_admins', methods: ['GET'])]
    public function admins(): Response
    {
        $admins = $this->membreRepository->findByRole('ADMIN');

        return $this->render('admin/membres/admins.html.twig', [
            'admins' => $admins,
        ]);
    }
    #[Route('/new', name: 'admin_membres_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $membre = new Membre();
        $form = $this->createForm(MembreType::class, $membre, ['is_edit' => false]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($membre, $plainPassword);
                $membre->setMotDePasse($hashedPassword);
            } else {
                throw new \LogicException('Le mot de passe est requis pour un nouveau membre.');
            }
            $this->entityManager->persist($membre);
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Membre créé avec succès.');
            return $this->redirectToRoute('admin_membres', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('admin/membres/new.html.twig', [
            'membre' => $membre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_membres_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Membre $membre): Response
    {
        $form = $this->createForm(MembreType::class, $membre, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($membre, $plainPassword);
                $membre->setMotDePasse($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Membre modifié avec succès.');

            return $this->redirectToRoute('admin_membres');
        }

        return $this->render('admin/membres/edit.html.twig', [
            'membre' => $membre,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_membres_delete', methods: ['POST'])]
    public function delete(Request $request, Membre $membre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $membre->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($membre);
            $this->entityManager->flush();
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Membre supprimé avec succès.']);
            }
            $this->addFlash('success', 'Membre supprimé avec succès.');
        } else {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_membres', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-confirm', name: 'admin_membres_toggle_confirm', methods: ['POST'])]
    public function toggleConfirm(Request $request, Membre $membre): JsonResponse
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');
        $tokenId = 'toggle' . $membre->getId();

        $this->logger->info('CSRF Token Validation', [
            'token_id' => $tokenId,
            'provided_token' => $csrfToken,
            'request_headers' => $request->headers->all(),
            'request_body' => $request->getContent(),
        ]);

        if (!$this->isCsrfTokenValid($tokenId, $csrfToken)) {
            $this->logger->warning('CSRF Token Invalid', [
                'token_id' => $tokenId,
                'provided_token' => $csrfToken,
            ]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Token CSRF invalide.',
                'debug' => 'Expected token for ' . $tokenId . ', received: ' . ($csrfToken ?? 'none')
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['isConfirmed']) || !is_bool($data['isConfirmed'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Données invalides : isConfirmed doit être un booléen.'
            ], 400);
        }

        $membre->setIsConfirmed($data['isConfirmed']);
        $this->entityManager->flush();

        $this->logger->info('Membre Confirmation Updated', [
            'membre_id' => $membre->getId(),
            'is_confirmed' => $data['isConfirmed'],
        ]);

        return new JsonResponse([
            'success' => true,
            'isConfirmed' => $membre->isConfirmed(),
            'message' => 'Statut de confirmation mis à jour avec succès.'
        ]);
    }

    #[Route('/{id}/details', name: 'admin_membres_details', methods: ['GET'])]
    public function details(Membre $membre): Response
    {
        return $this->render('admin/membres/details.html.twig', [
            'membre' => $membre,
        ]);
    }
}
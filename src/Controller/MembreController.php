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

#[Route('/admin/membres')]
class MembreController extends AbstractController
{
    private $entityManager;
    private $membreRepository;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, MembreRepository $membreRepository, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->membreRepository = $membreRepository;
        $this->passwordHasher = $passwordHasher;
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

        return $this->render('admin/membres/index.html.twig', [
            'membres' => $membres,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'searchTerm' => $searchTerm,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
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
    public function new(Request $request): Response
    {
        $membre = new Membre();
        $form = $this->createForm(MembreType::class, $membre, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($membre, $plainPassword);
                $membre->setMotDePasse($hashedPassword);
            }

            $this->entityManager->persist($membre);
            $this->entityManager->flush();

            $this->addFlash('success', 'Membre ajouté avec succès.');

            return $this->redirectToRoute('admin_membres');
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
        if ($this->isCsrfTokenValid('delete' . $membre->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($membre);
            $this->entityManager->flush();

            $this->addFlash('success', 'Membre supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_membres');
    }

    #[Route('/{id}/toggle-confirm', name: 'admin_membres_toggle_confirm', methods: ['POST'])]
    public function toggleConfirm(Request $request, Membre $membre): JsonResponse
    {
        if ($this->isCsrfTokenValid('toggle' . $membre->getId(), $request->request->get('_token'))) {
            $membre->setIsConfirmed(!$membre->isConfirmed());
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'isConfirmed' => $membre->isConfirmed(),
                'message' => 'Statut de confirmation mis à jour avec succès.'
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Token CSRF invalide.'
        ], 400);
    }
}
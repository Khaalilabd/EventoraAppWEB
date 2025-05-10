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

#[Route('/admin/agents')]
class AgentController extends AbstractController
{
    private $membreRepository;
    private $entityManager;

    public function __construct(MembreRepository $membreRepository, EntityManagerInterface $entityManager)
    {
        $this->membreRepository = $membreRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_agent_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 4;
        $searchTerm = $request->query->get('search', '');
        $sortBy = $request->query->get('sort_by', 'nom');
        $sortOrder = $request->query->get('sort_order', 'asc');
        $statusFilter = $request->query->get('status_filter', '');

        $queryBuilder = $this->membreRepository->createQueryBuilder('m')
            ->where('m.role = :role')
            ->setParameter('role', 'AGENT')
            ->orderBy("m.$sortBy", $sortOrder);

        if ($searchTerm) {
            $queryBuilder->andWhere('m.nom LIKE :search OR m.prenom LIKE :search OR m.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($statusFilter !== '') {
            $queryBuilder->andWhere('m.isConfirmed = :isConfirmed')
                ->setParameter('isConfirmed', $statusFilter === 'confirmed' ? true : false);
        }

        $totalAgents = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalAgents / $limit);

        $agents = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $statuses = [
            '' => 'Tous',
            'confirmed' => 'Confirmé',
            'not-confirmed' => 'Non Confirmé',
        ];

        return $this->render('admin/agents/index.html.twig', [
            'agents' => $agents,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'searchTerm' => $searchTerm,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'status_filter' => $statusFilter,
            'statuses' => $statuses,
        ]);
    }

    #[Route('/new', name: 'app_agent_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $agent = new Membre();
        $agent->setRole('AGENT');
        $form = $this->createForm(MembreType::class, $agent, ['is_edit' => false]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($agent, $plainPassword);
                $agent->setMotDePasse($hashedPassword);
            } else {
                throw new \LogicException('Le mot de passe est requis pour un nouvel agent.');
            }
            $this->entityManager->persist($agent);
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Agent créé avec succès.');
            return $this->redirectToRoute('app_agent_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('admin/agents/new.html.twig', [
            'agent' => $agent,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/{id}', name: 'app_agent_show', methods: ['GET'])]
    public function show(Membre $agent): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($agent->getRole() !== 'AGENT') {
            throw $this->createNotFoundException('Cet utilisateur n’est pas un agent.');
        }
        return $this->render('admin/agents/show.html.twig', [
            'agent' => $agent,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agent_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Membre $agent, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($agent->getRole() !== 'AGENT') {
            throw $this->createNotFoundException('Cet utilisateur n’est pas un agent.');
        }
        $form = $this->createForm(MembreType::class, $agent, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('motDePasse')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($agent, $plainPassword);
                $agent->setMotDePasse($hashedPassword);
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Agent modifié avec succès.');
            return $this->redirectToRoute('app_agent_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/agents/edit.html.twig', [
            'agent' => $agent,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_agent_delete', methods: ['POST'])]
    public function delete(Request $request, Membre $agent): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($agent->getRole() !== 'AGENT') {
            throw $this->createNotFoundException('Cet utilisateur n’est pas un agent.');
        }
        if ($this->isCsrfTokenValid('delete' . $agent->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($agent);
            $this->entityManager->flush();
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Agent supprimé avec succès.']);
            }
            $this->addFlash('success', 'Agent supprimé avec succès.');
        } else {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
            }
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_agent_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/toggle-confirmed', name: 'app_agent_toggle_confirmed', methods: ['POST'])]
    public function toggleConfirmed(Request $request, Membre $agent): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($agent->getRole() !== 'AGENT') {
            return new JsonResponse(['success' => false, 'message' => 'Cet utilisateur n’est pas un agent.'], 404);
        }

        if (!$this->isCsrfTokenValid('toggle' . $agent->getId(), $request->headers->get('X-CSRF-Token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        $agent->setIsConfirmed(!$agent->isConfirmed());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'isConfirmed' => $agent->isConfirmed(),
            'message' => 'Statut de confirmation mis à jour.'
        ]);
    }
}
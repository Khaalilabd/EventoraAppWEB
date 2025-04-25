<?php

namespace App\Controller\Admin;

use App\Entity\Reclamation;
use App\Entity\ReclamationRep;
use App\Form\ReclamationType;
use App\Form\ReclamationRepType;
use App\Repository\ReclamationRepository;
use App\Service\AiResponseGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reclamations')]
class ReclamationsController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_reclamations', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 6;
        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status_filter', '');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        $queryBuilder = $reclamationRepository->createQueryBuilder('r')
            ->leftJoin('r.membre', 'm')
            ->orderBy("r.$sortBy", $sortOrder);

        // Gestion des filtres par statut
        if ($statusFilter === 'non_traitees') {
            $queryBuilder->andWhere('r.statut IN (:statuses)')
                ->setParameter('statuses', ['En_Attente', 'En_Cours']);
        } elseif ($statusFilter === 'traitees') {
            $queryBuilder->andWhere('r.statut IN (:statuses)')
                ->setParameter('statuses', ['Resolue', 'Rejetée']);
        } elseif (in_array($statusFilter, ['en_attente', 'en_cours', 'resolue', 'rejetée'])) {
            $queryBuilder->andWhere('r.statut = :status')
                ->setParameter('status', str_replace('_', ' ', ucwords($statusFilter, '_')));
        }

        if ($searchTerm) {
            $queryBuilder->andWhere('r.titre LIKE :search OR r.description LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        $totalReclamations = count($queryBuilder->getQuery()->getResult());
        $totalPages = ceil($totalReclamations / $limit);

        $reclamations = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $statuses = [
            '' => 'Toutes',
            'non_traitees' => 'Non Traitées',
            'traitees' => 'Traitées',
            'en_attente' => 'En Attente',
            'en_cours' => 'En Cours',
            'resolue' => 'Résolue',
            'rejetée' => 'Rejetée',
        ];

        return $this->render('admin/reclamations/index.html.twig', [
            'reclamations' => $reclamations,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'searchTerm' => $searchTerm,
            'status_filter' => $statusFilter,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'statuses' => $statuses,
        ]);
    }

    #[Route('/new', name: 'admin_reclamations_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($reclamation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Réclamation créée avec succès.');
            return $this->redirectToRoute('admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reclamations/create.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'admin_reclamations_show_qr', methods: ['GET'])]
    public function showQr(Reclamation $reclamation): Response
    {
        return $this->render('admin/reclamations/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/traiter', name: 'admin_reclamations_traiter', methods: ['GET', 'POST'])]
    public function traiter(Request $request, Reclamation $reclamation, AiResponseGenerator $aiResponseGenerator): Response
    {
        $reclamationRep = new ReclamationRep();
        $reclamationRep->setReclamation($reclamation);

        // Générer une suggestion de réponse initiale
        $suggestedResponse = $aiResponseGenerator->generateResponse(
            $reclamation->getTitre(),
            $reclamation->getDescription(),
            $reclamation->getType()
        );
        $reclamationRep->setReponse($suggestedResponse);

        $form = $this->createForm(ReclamationRepType::class, $reclamationRep, [
            'reclamation' => $reclamation,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($reclamationRep);
            $this->entityManager->persist($reclamation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Réclamation traitée avec succès.');
            return $this->redirectToRoute('admin_reclamations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reclamations/traiter.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form->createView(),
            'suggested_response' => $suggestedResponse,
        ]);
    }

    #[Route('/{id}/generate-response', name: 'admin_reclamations_generate_response', methods: ['POST'])]
    public function generateResponse(Reclamation $reclamation, AiResponseGenerator $aiResponseGenerator): JsonResponse
    {
        try {
            $response = $aiResponseGenerator->generateResponse(
                $reclamation->getTitre(),
                $reclamation->getDescription(),
                $reclamation->getType()
            );
            return new JsonResponse(['success' => true, 'response' => $response]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la génération de la réponse : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/export-pdf', name: 'admin_reclamations_export_pdf', methods: ['GET'])]
    public function exportPdf(Reclamation $reclamation): Response
    {
        try {
            $html = $this->renderView('admin/reclamations/pdf.html.twig', [
                'reclamation' => $reclamation,
            ]);

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return new Response(
                $dompdf->output(),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="reclamation_%d.pdf"', $reclamation->getId()),
                ]
            );
        } catch (\Exception $e) {
            throw new \RuntimeException('Échec de la génération du PDF : ' . $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'admin_reclamations_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation): Response
    {
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($reclamation);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'message' => 'Réclamation supprimée avec succès.']);
            }

            $this->addFlash('success', 'Réclamation supprimée avec succès.');
        } else {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
            }

            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_reclamations', [], Response::HTTP_SEE_OTHER);
    }
}
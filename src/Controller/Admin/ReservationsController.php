<?php

namespace App\Controller\Admin;

use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Entity\Membre;
use App\Form\ReservationPackType;
use App\Form\ReservationPersonnaliseType;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations')]
class ReservationsController extends AbstractController
{
    #[Route('/pack', name: 'admin_reservations_pack', methods: ['GET'])]
    public function reservationsPack(
        ReservationpackRepository $reservationPackRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get query parameters
        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status_filter', '');
        $dateFilter = $request->query->get('date_filter', '');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        // Create a query builder for reservation packs
        $queryBuilder = $reservationPackRepository->createQueryBuilder('rp');

        // Apply search filter if a search term is provided
        if ($searchTerm) {
            $queryBuilder
                ->where('rp.nom LIKE :search')
                ->orWhere('rp.prenom LIKE :search')
                ->orWhere('rp.email LIKE :search')
                ->orWhere('rp.numtel LIKE :search')
                ->orWhere('rp.description LIKE :search')
                ->orWhere('rp.status LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Apply status filter
        if ($statusFilter) {
            $queryBuilder
                ->andWhere('rp.status = :status')
                ->setParameter('status', $statusFilter);
        }

        // Apply date filter
        if ($dateFilter) {
            $queryBuilder
                ->andWhere('rp.date = :date')
                ->setParameter('date', new \DateTime($dateFilter));
        }

        // Apply sorting
        $queryBuilder->orderBy('rp.' . $sortBy, $sortOrder);

        // Paginate the results (6 items per page)
        $reservationPacks = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1), // Current page from query parameter, default to 1
            6 // Items per page
        );

        return $this->render('admin/reservation/pack_index.html.twig', [
            'reservationPacks' => $reservationPacks,
            'searchTerm' => $searchTerm,
            'selected_status' => $statusFilter,
            'selected_date' => $dateFilter,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }

    #[Route('/personnalise', name: 'admin_reservations_personnalise', methods: ['GET'])]
    public function reservationsPersonnalise(
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get query parameters
        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status_filter', '');
        $dateFilter = $request->query->get('date_filter', '');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        // Create a query builder for personalized reservations
        $queryBuilder = $reservationPersonnaliseRepository->createQueryBuilder('rp');

        // Apply search filter if a search term is provided
        if ($searchTerm) {
            $queryBuilder
                ->where('rp.nom LIKE :search')
                ->orWhere('rp.prenom LIKE :search')
                ->orWhere('rp.email LIKE :search')
                ->orWhere('rp.numtel LIKE :search')
                ->orWhere('rp.description LIKE :search')
                ->orWhere('rp.status LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        // Apply status filter
        if ($statusFilter) {
            $queryBuilder
                ->andWhere('rp.status = :status')
                ->setParameter('status', $statusFilter);
        }

        // Apply date filter
        if ($dateFilter) {
            $queryBuilder
                ->andWhere('rp.date = :date')
                ->setParameter('date', new \DateTime($dateFilter));
        }

        // Apply sorting
        $queryBuilder->orderBy('rp.' . $sortBy, $sortOrder);

        // Paginate the results (6 items per page)
        $reservationPersonnalises = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1), // Current page from query parameter, default to 1
            6 // Items per page
        );

        return $this->render('admin/reservation/personnalise_index.html.twig', [
            'reservationPersonnalises' => $reservationPersonnalises,
            'searchTerm' => $searchTerm,
            'selected_status' => $statusFilter,
            'selected_date' => $dateFilter,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);
    }

    #[Route('/pack/new', name: 'admin_reservations_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = new Reservationpack();
        // Set a default membre
        $defaultMembre = $entityManager->getRepository(Membre::class)->findOneBy([]);
        if (!$defaultMembre) {
            throw $this->createNotFoundException('Aucun membre disponible.');
        }
        $reservation->setMembre($defaultMembre);
        $reservation->setStatus('En attente');

        $form = $this->createForm(ReservationPackType::class, $reservation, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Pack ajoutée avec succès.');
            return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('admin/reservation/pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/new', name: 'admin_reservations_personnalise_new', methods: ['GET', 'POST'])]
    public function newPersonnalise(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = new Reservationpersonnalise();
        // Set a default membre
        $defaultMembre = $entityManager->getRepository(Membre::class)->findOneBy([]);
        if (!$defaultMembre) {
            throw $this->createNotFoundException('Aucun membre disponible.');
        }
        $reservation->setMembre($defaultMembre);
        $reservation->setStatus('En attente');

        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Personnalisée ajoutée avec succès.');
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('admin/reservation/personnalise_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/pack/{id}/edit', name: 'admin_reservations_pack_edit', methods: ['GET', 'POST'])]
    public function editPack(
        Request $request,
        int $id,
        ReservationpackRepository $reservationPackRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $reservationPackRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation Pack non trouvée.');
        }

        $form = $this->createForm(ReservationPackType::class, $reservation, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Pack modifiée avec succès.');
            return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('admin/reservation/pack_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/{id}/edit', name: 'admin_reservations_personnalise_edit', methods: ['GET', 'POST'])]
    public function editPersonnalise(
        Request $request,
        int $id,
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $reservationPersonnaliseRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation Personnalisée non trouvée.');
        }

        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Personnalisée modifiée avec succès.');
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        } elseif ($form->isSubmitted()) {
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('admin/reservation/personnalise_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/{id}/details', name: 'admin_reservations_personnalise_details', methods: ['GET'])]
    public function detailsPersonnalise(int $id, ReservationpersonnaliseRepository $reservationpersonnaliseRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $reservationpersonnaliseRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée pour l\'ID ' . $id);
        }

        return $this->render('admin/reservation/personnalise_details.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/personnalise/{id}/pdf', name: 'admin_reservations_personnalise_pdf', methods: ['GET'])]
    public function generatePersonnalisePdf(Reservationpersonnalise $reservation, Pdf $knpSnappyPdf): Response
    {
        $html = $this->renderView('admin/reservation/pdf_personnalise_details.html.twig', [
            'reservation' => $reservation,
        ]);

        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reservation_personnalise_' . $reservation->getIDReservationPersonalise() . '.pdf"',
            ]
        );
    }

    #[Route('/pack/{id}/details', name: 'admin_reservations_pack_details', methods: ['GET'])]
    public function detailsPack(int $id, ReservationpackRepository $reservationpackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $reservationpackRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée pour l\'ID ' . $id);
        }

        return $this->render('admin/reservation/pack_details.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{type}/{id}/delete', name: 'admin_reservations_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        string $type,
        int $id,
        ReservationpackRepository $reservationPackRepository,
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($type === 'pack') {
            $reservation = $reservationPackRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation Pack non trouvée.');
            }
            $idField = $reservation->getIDReservationPack();
        } elseif ($type === 'personnalise') {
            $reservation = $reservationPersonnaliseRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation Personnalisée non trouvée.');
            }
            $idField = $reservation->getIdReservationPersonalise();
        } else {
            throw $this->createNotFoundException('Type de réservation invalide.');
        }

        if ($this->isCsrfTokenValid('delete' . $idField, $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        // Redirect based on the type of reservation
        if ($type === 'pack') {
            return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
        } elseif ($type === 'personnalise') {
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        }

        // Fallback in case type is invalid
        return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/pack/{id}/pdf', name: 'admin_reservations_pack_pdf', methods: ['GET'])]
    public function generatePdf(Reservationpack $reservation, Pdf $knpSnappyPdf): Response
    {
        $html = $this->renderView('admin/reservation/pdf_pack_details.html.twig', [
            'reservation' => $reservation,
        ]);

        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reservation_pack_' . $reservation->getIDReservationPack() . '.pdf"',
            ]
        );
    }
}
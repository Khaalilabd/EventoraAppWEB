<?php

namespace App\Controller\Admin;

use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Form\ReservationPackType;
use App\Form\ReservationPersonnaliseType;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reservations')]
class ReservationsController extends AbstractController
{
    // Page principale listant toutes les réservations
    #[Route('/', name: 'admin_reservations', methods: ['GET'])]
    public function index(
        ReservationpackRepository $reservationPackRepository,
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationPacks = $reservationPackRepository->findAll();
        $reservationPersonnalises = $reservationPersonnaliseRepository->findAll();

        return $this->render('admin/reservation/index.html.twig', [
            'reservationPacks' => $reservationPacks,
            'reservationPersonnalises' => $reservationPersonnalises,
        ]);
    }

    // Liste des réservations "Pack"
    #[Route('/pack', name: 'admin_reservations_pack', methods: ['GET'])]
    public function reservationsPack(ReservationpackRepository $reservationPackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationPacks = $reservationPackRepository->findAll();

        return $this->render('admin/reservation/pack_index.html.twig', [
            'reservationPacks' => $reservationPacks,
        ]);
    }

    // Liste des réservations "Personnalisées"
    #[Route('/personnalise', name: 'admin_reservations_personnalise', methods: ['GET'])]
    public function reservationsPersonnalise(ReservationpersonnaliseRepository $reservationPersonnaliseRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationPersonnalises = $reservationPersonnaliseRepository->findAll();

        return $this->render('admin/reservation/personnalise_index.html.twig', [
            'reservationPersonnalises' => $reservationPersonnalises,
        ]);
    }

    // Nouvelle réservation "Pack"
    #[Route('/pack/new', name: 'admin_reservations_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = new Reservationpack();
        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Pack ajoutée avec succès.');
            return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Nouvelle réservation "Personnalisée"
    #[Route('/personnalise/new', name: 'admin_reservations_personnalise_new', methods: ['GET', 'POST'])]
    public function newPersonnalise(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = new Reservationpersonnalise();
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Personnalisée ajoutée avec succès.');
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/personnalise_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

   // Dans App\Controller\Admin\ReservationsController

#[Route('/pack/{id}/edit', name: 'admin_reservations_pack_edit', methods: ['GET', 'POST'])]
public function editPack(
    Request $request,
    $id,
    ReservationpackRepository $reservationPackRepository,
    EntityManagerInterface $entityManager
): Response {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    // Récupération explicite sans jointure problématique
    $reservation = $reservationPackRepository->find($id);
    if (!$reservation) {
        throw $this->createNotFoundException('Réservation Pack non trouvée.');
    }

    $form = $this->createForm(ReservationPackType::class, $reservation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        $this->addFlash('success', 'Réservation Pack modifiée avec succès.');
        return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('admin/reservation/pack_edit.html.twig', [
        'reservation' => $reservation,
        'form' => $form->createView(),
    ]);
}

    // Édition d'une réservation "Personnalisée"
    #[Route('/personnalise/{id}/edit', name: 'admin_reservations_personnalise_edit', methods: ['GET', 'POST'])]
    public function editPersonnalise(
        Request $request,
        $id,
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation = $reservationPersonnaliseRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation Personnalisée non trouvée.');
        }

        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation Personnalisée modifiée avec succès.');
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/personnalise_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    // Suppression d'une réservation
    #[Route('/{type}/{id}/delete', name: 'admin_reservations_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        string $type,
        $id,
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

        return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
    }
}
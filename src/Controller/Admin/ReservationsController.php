<?php

namespace App\Controller\Admin;

use App\Entity\ReservationPack;
use App\Entity\ReservationPersonnalise;
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
    #[Route('/', name: 'admin_reservations', methods: ['GET'])]
    public function index(
        ReservationpackRepository $reservationPackRepository,
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationPacks = $reservationPackRepository->findAllCustom(); // Updated to custom method
        $reservationPersonnalises = $reservationPersonnaliseRepository->findAll();

        return $this->render('admin/reservation/index.html.twig', [
            'reservationPacks' => $reservationPacks,
            'reservationPersonnalises' => $reservationPersonnalises,
        ]);
    }

    #[Route('/new', name: 'admin_reservations_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $type = $request->query->get('type');

        if (!$type || !in_array($type, ['pack', 'personnalise'])) {
            return $this->render('admin/reservation/select_type.html.twig');
        }

        if ($type === 'pack') {
            $reservation = new ReservationPack();
            $form = $this->createForm(ReservationPackType::class, $reservation);
        } else {
            $reservation = new ReservationPersonnalise();
            $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation ajoutée avec succès.');
            return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/new.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

    #[Route('/{type}/{id}/edit', name: 'admin_reservations_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        string $type,
        $id,
        ReservationpackRepository $reservationPackRepository,
        ReservationpersonnaliseRepository $reservationPersonnaliseRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($type === 'pack') {
            $reservation = $reservationPackRepository->findCustom($id); // Updated to custom method
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation Pack non trouvée.');
            }
            $form = $this->createForm(ReservationPackType::class, $reservation);
        } elseif ($type === 'personnalise') {
            $reservation = $reservationPersonnaliseRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation Personnalisée non trouvée.');
            }
            $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        } else {
            throw $this->createNotFoundException('Type de réservation invalide.');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation modifiée avec succès.');
            return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

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
            $reservation = $reservationPackRepository->findCustom($id); // Updated to custom method
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
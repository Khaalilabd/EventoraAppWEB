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

    #[Route('/pack', name: 'admin_reservations_pack', methods: ['GET'])]
    public function reservationsPack(ReservationpackRepository $reservationPackRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationPacks = $reservationPackRepository->findAll();

        return $this->render('admin/reservation/pack_index.html.twig', [
            'reservationPacks' => $reservationPacks,
        ]);
    }

    #[Route('/personnalise', name: 'admin_reservations_personnalise', methods: ['GET'])]
    public function reservationsPersonnalise(ReservationpersonnaliseRepository $reservationPersonnaliseRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservationPersonnalises = $reservationPersonnaliseRepository->findAll();

        return $this->render('admin/reservation/personnalise_index.html.twig', [
            'reservationPersonnalises' => $reservationPersonnalises,
        ]);
    }

    #[Route('/pack/new', name: 'admin_reservations_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
        $reservation = new Reservationpack();
        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', 'Réservation Pack ajoutée avec succès.');
                return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
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
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', 'Réservation Personnalisée ajoutée avec succès.');
                return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
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

        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Réservation Pack modifiée avec succès.');
                return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
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

        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Réservation Personnalisée modifiée avec succès.');
                return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }

        return $this->render('admin/reservation/personnalise_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }


#[Route('/admin/reservations/personnalise/{id}/details', name:'admin_reservations_personnalise_details', methods: ['GET'])]
public function detailsPersonnalise(int $id, ReservationpersonnaliseRepository $reservationpersonnaliseRepository): Response
{
    $reservation = $reservationpersonnaliseRepository->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('Réservation non trouvée pour l\'ID ' . $id);
    }

    return $this->render('admin/reservation/personnalise_details.html.twig', [
        'reservation' => $reservation,
    ]);
}

#[Route('/admin/reservations/pack/{id}/details', name:'admin_reservations_pack_details', methods: ['GET'])]
public function detailsPack(int $id, ReservationpackRepository $reservationpackRepository): Response
{
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

    // Fallback in case type is invalid (though this shouldn't happen due to earlier validation)
    return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
}
}
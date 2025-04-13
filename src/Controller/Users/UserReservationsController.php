<?php

namespace App\Controller\Users;

use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Form\ReservationPackType;
use App\Entity\GService;
use App\Form\ReservationPersonnaliseType;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/reservations')]
class UserReservationsController extends AbstractController
{
    #[Route('/pack/new', name: 'admin_reservations_user_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MEMBRE');
    
        $reservation = new Reservationpack();
        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', 'Réservation Pack ajoutée avec succès.');
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }
            // If the form is invalid, do NOT redirect; let the form re-render with errors
        }
    
        return $this->render('admin/reservation/user_pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/new', name: 'admin_reservations_user_personnalise_new', methods: ['GET', 'POST'])]
    public function newPersonnalise(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MEMBRE');

        $reservation = new Reservationpersonnalise();
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', 'Réservation Personnalisée ajoutée avec succès.');
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }
            // If the form is invalid, do NOT redirect; let the form re-render with errors
        }

        return $this->render('admin/reservation/user_personnalise_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
<?php

namespace App\Controller\Users;

use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Entity\Membre;
use App\Form\ReservationPackType;
use App\Form\ReservationPersonnaliseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/reservations')]
class UserReservationsController extends AbstractController
{
    #[Route('/pack/new', name: 'admin_reservations_user_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur a les droits nécessaires
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour créer une réservation pack.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour créer une réservation pack.');
        }

        // Vérifier si l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non connecté ou non valide.'
                ], 401);
            }
            $this->addFlash('error', 'Vous devez être connecté pour créer une réservation pack.');
            return $this->redirectToRoute('app_auth');
        }

        $reservation = new Reservationpack();
        // Définir le membre avant de lier le formulaire
        $reservation->setMembre($user);
        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    // Remplir les champs avec les données du Membre
                    $reservation->setNom($user->getNom() ?? 'Inconnu');
                    $reservation->setPrenom($user->getPrenom() ?? 'Inconnu');
                    $reservation->setEmail($user->getEmail() ?? 'contact@example.com');
                    $reservation->setNumtel($user->getNumTel() ?? '+1234567890');

                    $entityManager->persist($reservation);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Votre réservation pack a été créée avec succès !'
                    ]);
                }

                // Récupérer les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[$error->getOrigin()->getName()][] = $error->getMessage();
                }

                return new JsonResponse([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Requête invalide.'
            ], 400);
        }

        // Gestion de la requête classique (non-AJAX)
        if ($form->isSubmitted() && $form->isValid()) {
            // Remplir les champs avec les données du Membre
            $reservation->setNom($user->getNom() ?? 'Inconnu');
            $reservation->setPrenom($user->getPrenom() ?? 'Inconnu');
            $reservation->setEmail($user->getEmail() ?? 'contact@example.com');
            $reservation->setNumtel($user->getNumTel() ?? '+1234567890');

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation pack a été créée avec succès !');
            return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
        }

        return $this->render('admin/reservation/user_pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/new', name: 'admin_reservations_user_personnalise_new', methods: ['GET', 'POST'])]
    public function newPersonnalise(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur a les droits nécessaires
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour créer une réservation personnalisée.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour créer une réservation personnalisée.');
        }

        // Vérifier si l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non connecté ou non valide.'
                ], 401);
            }
            $this->addFlash('error', 'Vous devez être connecté pour créer une réservation personnalisée.');
            return $this->redirectToRoute('app_auth');
        }

        $reservation = new Reservationpersonnalise();
        // Définir le membre avant de lier le formulaire
        $reservation->setMembre($user);
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    // Remplir les champs avec les données du Membre
                    $reservation->setNom($user->getNom() ?? 'Inconnu');
                    $reservation->setPrenom($user->getPrenom() ?? 'Inconnu');
                    $reservation->setEmail($user->getEmail() ?? 'contact@example.com');
                    $reservation->setNumtel($user->getNumTel() ?? '+1234567890');

                    $entityManager->persist($reservation);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Votre réservation personnalisée a été créée avec succès !'
                    ]);
                }

                // Récupérer les erreurs de validation
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[$error->getOrigin()->getName()][] = $error->getMessage();
                }

                return new JsonResponse([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Requête invalide.'
            ], 400);
        }

        // Gestion de la requête classique (non-AJAX)
        if ($form->isSubmitted() && $form->isValid()) {
            // Remplir les champs avec les données du Membre
            $reservation->setNom($user->getNom() ?? 'Inconnu');
            $reservation->setPrenom($user->getPrenom() ?? 'Inconnu');
            $reservation->setEmail($user->getEmail() ?? 'contact@example.com');
            $reservation->setNumtel($user->getNumTel() ?? '+1234567890');

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation personnalisée a été créée avec succès !');
            return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
        }

        return $this->render('admin/reservation/user_personnalise_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
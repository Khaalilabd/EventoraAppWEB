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
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

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
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        $reservationPacks = $reservationPackRepository->findAll();

        return $this->render('admin/reservation/pack_index.html.twig', [
            'reservationPacks' => $reservationPacks,
        ]);
    }

    #[Route('/personnalise', name: 'admin_reservations_personnalise', methods: ['GET'])]
    public function reservationsPersonnalise(ReservationpersonnaliseRepository $reservationPersonnaliseRepository): Response
    {
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        $reservationPersonnalises = $reservationPersonnaliseRepository->findAll();

        return $this->render('admin/reservation/personnalise_index.html.twig', [
            'reservationPersonnalises' => $reservationPersonnalises,
        ]);
    }

    #[Route('/pack/new', name: 'admin_reservations_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        // Vérifier que l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            $this->addFlash('error', 'Vous devez être connecté en tant que membre.');
            return $this->redirectToRoute('app_auth');
        }

        $reservation = new Reservationpack();
        // Associer automatiquement le membre connecté
        $reservation->setMembre($user);
        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Pré-remplir les champs avec les données du membre
                    $reservation->setNom($user->getNom() ?? $reservation->getNom() ?? 'Inconnu');
                    $reservation->setPrenom($user->getPrenom() ?? $reservation->getPrenom() ?? 'Inconnu');
                    $reservation->setEmail($user->getEmail() ?? $reservation->getEmail() ?? 'contact@example.com');
                    $reservation->setNumtel($user->getNumTel() ?? $reservation->getNumtel() ?? '+1234567890');

                    $entityManager->persist($reservation);
                    $entityManager->flush();
                    $this->addFlash('success', 'Réservation Pack ajoutée avec succès.');
                    return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Erreurs dans le formulaire : ' . implode(', ', $errors));
            }
        } elseif ($request->isMethod('POST')) {
            $this->addFlash('error', 'Le formulaire n\'a pas été soumis correctement. Vérifiez les champs.');
        }

        return $this->render('admin/reservation/pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/new', name: 'admin_reservations_personnalise_new', methods: ['GET', 'POST'])]
    public function newPersonnalise(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        // Vérifier que l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            $this->addFlash('error', 'Vous devez être connecté en tant que membre.');
            return $this->redirectToRoute('app_auth');
        }

        $reservation = new Reservationpersonnalise();
        // Associer automatiquement le membre connecté
        $reservation->setMembre($user);
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Pré-remplir les champs avec les données du membre
                    $reservation->setNom($user->getNom() ?? $reservation->getNom() ?? 'Inconnu');
                    $reservation->setPrenom($user->getPrenom() ?? $reservation->getPrenom() ?? 'Inconnu');
                    $reservation->setEmail($user->getEmail() ?? $reservation->getEmail() ?? 'contact@example.com');
                    $reservation->setNumtel($user->getNumTel() ?? $reservation->getNumtel() ?? '+1234567890');

                    $entityManager->persist($reservation);
                    $entityManager->flush();
                    $this->addFlash('success', 'Réservation Personnalisée ajoutée avec succès.');
                    return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Erreurs dans le formulaire : ' . implode(', ', $errors));
            }
        } elseif ($request->isMethod('POST')) {
            $this->addFlash('error', 'Le formulaire n\'a pas été soumis correctement. Vérifiez les champs.');
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
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        // Vérifier que l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            $this->addFlash('error', 'Vous devez être connecté en tant que membre.');
            return $this->redirectToRoute('app_auth');
        }

        $reservation = $reservationPackRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation Pack non trouvée.');
        }

        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Assurer que le membre reste associé
                    $reservation->setMembre($user);
                    // Pré-remplir les champs avec les données du membre
                    $reservation->setNom($user->getNom() ?? $reservation->getNom() ?? 'Inconnu');
                    $reservation->setPrenom($user->getPrenom() ?? $reservation->getPrenom() ?? 'Inconnu');
                    $reservation->setEmail($user->getEmail() ?? $reservation->getEmail() ?? 'contact@example.com');
                    $reservation->setNumtel($user->getNumTel() ?? $reservation->getNumtel() ?? '+1234567890');

                    $entityManager->flush();
                    $this->addFlash('success', 'Réservation Pack modifiée avec succès.');
                    return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Erreurs dans le formulaire : ' . implode(', ', $errors));
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
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        // Vérifier que l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            $this->addFlash('error', 'Vous devez être connecté en tant que membre.');
            return $this->redirectToRoute('app_auth');
        }

        $reservation = $reservationPersonnaliseRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation Personnalisée non trouvée.');
        }

        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    // Assurer que le membre reste associé
                    $reservation->setMembre($user);
                    // Pré-remplir les champs avec les données du membre
                    $reservation->setNom($user->getNom() ?? $reservation->getNom() ?? 'Inconnu');
                    $reservation->setPrenom($user->getPrenom() ?? $reservation->getPrenom() ?? 'Inconnu');
                    $reservation->setEmail($user->getEmail() ?? $reservation->getEmail() ?? 'contact@example.com');
                    $reservation->setNumtel($user->getNumTel() ?? $reservation->getNumtel() ?? '+1234567890');

                    $entityManager->flush();
                    $this->addFlash('success', 'Réservation Personnalisée modifiée avec succès.');
                    return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la modification : ' . $e->getMessage());
                }
            } else {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addFlash('error', 'Erreurs dans le formulaire : ' . implode(', ', $errors));
            }
        }

        return $this->render('admin/reservation/personnalise_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/personnalise/{id}/details', name: 'admin_reservations_personnalise_details', methods: ['GET'])]
    public function detailsPersonnalise(int $id, ReservationpersonnaliseRepository $reservationPersonnaliseRepository): Response
    {
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        $reservation = $reservationPersonnaliseRepository->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée pour l\'ID ' . $id);
        }

        return $this->render('admin/reservation/personnalise_details.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/pack/{id}/details', name: 'admin_reservations_pack_details', methods: ['GET'])]
    public function detailsPack(int $id, ReservationpackRepository $reservationPackRepository): Response
    {
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        $reservation = $reservationPackRepository->find($id);
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
        // Autoriser ROLE_MEMBRE ou ROLE_ADMIN
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur.');
        }

        // Vérifier que l'utilisateur est connecté et est une instance de Membre
        $user = $this->getUser();
        if (!$user instanceof Membre) {
            $this->addFlash('error', 'Vous devez être connecté en tant que membre.');
            return $this->redirectToRoute('app_auth');
        }

        if ($type === 'pack') {
            $reservation = $reservationPackRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation Pack non trouvée.');
            }
            // Vérifier que la réservation appartient au membre connecté
            if ($reservation->getMembre() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette réservation.');
            }
            $idField = $reservation->getIDReservationPack();
        } elseif ($type === 'personnalise') {
            $reservation = $reservationPersonnaliseRepository->find($id);
            if (!$reservation) {
                throw $this->createNotFoundException('Réservation Personnalisée non trouvée.');
            }
            // Vérifier que la réservation appartient au membre connecté
            if ($reservation->getMembre() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette réservation.');
            }
            $idField = $reservation->getIDReservationPersonalise();
        } else {
            throw $this->createNotFoundException('Type de réservation invalide.');
        }

        if ($this->isCsrfTokenValid('delete' . $idField, $request->request->get('_token'))) {
            try {
                $entityManager->remove($reservation);
                $entityManager->flush();
                $this->addFlash('success', 'Réservation supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        if ($type === 'pack') {
            return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
        } elseif ($type === 'personnalise') {
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
    }
}
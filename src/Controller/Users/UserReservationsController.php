<?php

namespace App\Controller\Users;

use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Entity\Membre;
use App\Entity\Pack;
use Knp\Snappy\Pdf;
use App\Form\ReservationPackType;
use App\Form\ReservationPersonnaliseType;
use App\Repository\ReservationpackRepository;
use App\Repository\ReservationpersonnaliseRepository;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/user/reservations')]
class UserReservationsController extends AbstractController
{
    #[Route('/pack/new', name: 'user_reservation_pack_new', methods: ['GET', 'POST'])]
    public function newPack(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour créer une réservation pack.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour créer une réservation pack.');
        }

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
        $reservation->setMembre($user);
        $reservation->setStatus('En attente');
        $form = $this->createForm(ReservationPackType::class, $reservation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $entityManager->persist($reservation);
                    $entityManager->flush();
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Votre réservation pack a été créée avec succès !'
                    ]);
                }

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

        if ($form->isSubmitted()) {
            $packValue = $form->get('pack')->getData();
            error_log('Submitted pack: ' . ($packValue ? $packValue->getId() . ' - ' . $packValue->getNomPack() : 'null'));
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réservation pack a été créée avec succès !');
            return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
        }

        return $this->render('admin/reservation/user_pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/pack/search', name: 'user_pack_search', methods: ['GET'])]
    public function searchPack(Request $request, PackRepository $packRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        $packs = $packRepository->createQueryBuilder('p')
            ->where('p.nomPack LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($packs as $pack) {
            $results[] = [
                'id' => $pack->getId(),
                'text' => $pack->getNomPack(),
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/personnalise/new', name: 'user_reservation_personnalise_new', methods: ['GET', 'POST'])]
    public function newPersonnalise(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour créer une réservation personnalisée.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour créer une réservation personnalisée.');
        }

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
        $reservation->setMembre($user);
        $reservation->setStatus('En attente');
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $entityManager->persist($reservation);
                    $entityManager->flush();

                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Votre réservation personnalisée a été créée avec succès !'
                    ]);
                }

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

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation personnalisée a été créée avec succès !');
            return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
        }

        return $this->render('admin/reservation/user_personnalise_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/pack/{id}/show', name: 'user_reservation_pack_show', methods: ['GET'])]
    public function showPack(Reservationpack $reservation): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof Membre) {
            throw $this->createAccessDeniedException('Utilisateur non connecté ou non valide.');
        }

        if ($reservation->getMembre()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas voir cette réservation.');
        }

        return $this->render('admin/reservation/reservation_pack_show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/personnalise/{id}/show', name: 'user_reservation_personnalise_show', methods: ['GET'])]
    public function showPersonnalise(Reservationpersonnalise $reservation): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof Membre) {
            throw $this->createAccessDeniedException('Utilisateur non connecté ou non valide.');
        }

        if ($reservation->getMembre()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas voir cette réservation.');
        }

        return $this->render('admin/reservation/reservation_personnalise_show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/personnalise/{id}/pdf', name: 'user_reservation_personnalise_pdf', methods: ['GET'])]
    public function generatePersonnalisePdf(Reservationpersonnalise $reservation, Pdf $knpSnappyPdf): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof Membre) {
            throw $this->createAccessDeniedException('Utilisateur non connecté ou non valide.');
        }

        if ($reservation->getMembre()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas télécharger cette réservation.');
        }

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

    #[Route('/pack/{id}/pdf', name: 'user_reservation_pack_pdf', methods: ['GET'])]
    public function generatePackPdf(Reservationpack $reservation, Pdf $knpSnappyPdf): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof Membre) {
            throw $this->createAccessDeniedException('Utilisateur non connecté ou non valide.');
        }

        if ($reservation->getMembre()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas télécharger cette réservation.');
        }

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
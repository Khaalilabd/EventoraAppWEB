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
use Twilio\Rest\Client;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use App\Service\GoogleCalendarService;

#[Route('/user/reservations')]
class UserReservationsController extends AbstractController
{
    private $twilioClient;
    private $twilioFromNumber;
    private $emailApiInstance;
    private $logger;
    private $brevoSenderEmail;
    private $brevoSenderName;
    private $googleCalendarService;

    public function __construct(
        string $twilioAccountSid,
        string $twilioAuthToken,
        string $twilioPhoneNumber,
        string $brevoApiKey,
        string $brevoSenderEmail,
        string $brevoSenderName,
        LoggerInterface $logger,
        GoogleCalendarService $googleCalendarService
    ) {
        $this->twilioClient = new Client($twilioAccountSid, $twilioAuthToken);
        $this->twilioFromNumber = $twilioPhoneNumber;
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $brevoApiKey);
        $this->emailApiInstance = new TransactionalEmailsApi(new GuzzleClient(), $config);
        $this->logger = $logger;
        $this->brevoSenderEmail = $brevoSenderEmail;
        $this->brevoSenderName = $brevoSenderName;
        $this->googleCalendarService = $googleCalendarService;
    }

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
                    try {
                        $numtel = $form->get('numtel')->getData();
                        $reservation->setNumtel('+216' . $numtel);
                        $recipientEmail = $form->get('email')->getData();
                        $entityManager->persist($reservation);
                        $entityManager->flush();

                        // Add Google Calendar event
                        $calendarLink = $this->googleCalendarService->addEvent($reservation, 'pack');
                        if (!$calendarLink) {
                            $this->logger->warning('Failed to add Google Calendar event for pack reservation: ' . $reservation->getId());
                        }

                        $packName = $reservation->getPack() ? $reservation->getPack()->getNomPack() : 'Non spécifié';
                        $eventDate = $reservation->getDate()->format('d/m/Y');
                        $userName = $reservation->getPrenom() ?: 'Client';
                        $smsMessage = sprintf(
                            'Cher(e) %s, votre réservation pour le pack "%s" du %s a été confirmée. %s Merci de choisir Eventora !',
                            $userName,
                            $packName,
                            $eventDate,
                            $calendarLink ? "Consultez votre événement : $calendarLink" : ''
                        );

                        $this->logger->info('Sending Brevo email to: ' . $recipientEmail);
                        $emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
                            'sender' => ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail],
                            'to' => [['email' => $recipientEmail, 'name' => $userName]],
                            'templateId' => 4,
                            'params' => [
                                'name' => $userName,
                                'date' => $eventDate,
                                'packName' => $packName,
                                'calendarLink' => $calendarLink ?: 'N/A'
                            ]
                        ]);

                        try {
                            $result = $this->emailApiInstance->sendTransacEmail($emailData);
                            $this->logger->info('Brevo API response: ' . json_encode($result));
                        } catch (ApiException $e) {
                            $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                        }

                        // Send SMS via Twilio
                        try {
                            $this->twilioClient->messages->create(
                                $reservation->getNumtel(),
                                [
                                    'from' => $this->twilioFromNumber,
                                    'body' => $smsMessage
                                ]
                            );
                            $this->logger->info('Twilio SMS sent to: ' . $reservation->getNumtel());
                        } catch (\Exception $e) {
                            $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                        }

                        return new JsonResponse([
                            'success' => true,
                            'message' => $smsMessage
                        ]);
                    } catch (ApiException $e) {
                        $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                        try {
                            $this->twilioClient->messages->create(
                                $reservation->getNumtel(),
                                [
                                    'from' => $this->twilioFromNumber,
                                    'body' => $smsMessage
                                ]
                            );
                        } catch (\Exception $e) {
                            $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                        }
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur lors de l\'envoi de l\'email.'
                        ], 500);
                    } catch (\Exception $e) {
                        $this->logger->error('Error saving pack reservation or sending SMS: ' . $e->getMessage(), ['exception' => $e]);
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur serveur lors de la création de la réservation.'
                        ], 500);
                    }
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
            $this->logger->info('Submitted pack: ' . ($packValue ? $packValue->getId() . ' - ' . $packValue->getNomPack() : 'null'));
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $numtel = $form->get('numtel')->getData();
                $reservation->setNumtel('+216' . $numtel);
                $recipientEmail = $form->get('email')->getData();
                $entityManager->persist($reservation);
                $entityManager->flush();

                // Add Google Calendar event
                $calendarLink = $this->googleCalendarService->addEvent($reservation, 'pack');
                if (!$calendarLink) {
                    $this->logger->warning('Failed to add Google Calendar event for pack reservation: ' . $reservation->getId());
                    $this->addFlash('warning', 'Événement non ajouté au calendrier Google.');
                }

                $packName = $reservation->getPack() ? $reservation->getPack()->getNomPack() : 'Non spécifié';
                $eventDate = $reservation->getDate()->format('d/m/Y');
                $userName = $reservation->getPrenom() ?: 'Client';
                $successMessage = sprintf(
                    'Cher(e) %s, votre réservation pour le pack "%s" du %s a été confirmée. %s Merci de choisir Eventora !',
                    $userName,
                    $packName,
                    $eventDate,
                    $calendarLink ? "Consultez votre événement : $calendarLink" : ''
                );

                $this->logger->info('Sending Brevo email to: ' . $recipientEmail);
                $emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
                    'sender' => ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail],
                    'to' => [['email' => $recipientEmail, 'name' => $userName]],
                    'templateId' => 4,
                    'params' => [
                        'name' => $userName,
                        'date' => $eventDate,
                        'packName' => $packName,
                        'calendarLink' => $calendarLink ?: 'N/A'
                    ]
                ]);

                try {
                    $result = $this->emailApiInstance->sendTransacEmail($emailData);
                    $this->logger->info('Brevo API response: ' . json_encode($result));
                } catch (ApiException $e) {
                    $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                    $this->addFlash('warning', 'Réservation confirmée, mais erreur lors de l\'envoi de l\'email.');
                }

                // Send SMS via Twilio
                try {
                    $this->twilioClient->messages->create(
                        $reservation->getNumtel(),
                        [
                            'from' => $this->twilioFromNumber,
                            'body' => $successMessage
                        ]
                    );
                    $this->logger->info('Twilio SMS sent to: ' . $reservation->getNumtel());
                } catch (\Exception $e) {
                    $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('warning', 'Réservation confirmée, mais erreur lors de l\'envoi du SMS.');
                }

                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
            } catch (ApiException $e) {
                $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                try {
                    $this->twilioClient->messages->create(
                        $reservation->getNumtel(),
                        [
                            'from' => $this->twilioFromNumber,
                            'body' => $successMessage
                        ]
                    );
                    $this->addFlash('warning', 'Réservation confirmée, mais erreur lors de l\'envoi de l\'email.');
                } catch (\Exception $e) {
                    $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email et SMS.');
                }
                return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
            } catch (\Exception $e) {
                $this->logger->error('Error saving pack reservation or sending SMS: ' . $e->getMessage(), ['exception' => $e]);
                $this->addFlash('error', 'Erreur serveur lors de la création de la réservation.');
            }
        }

        return $this->render('admin/reservation/user_pack_new.html.twig', [
            'form' => $form->createView(),
        ]);
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
                    try {
                        $numtel = $form->get('numtel')->getData();
                        $reservation->setNumtel('+216' . $numtel);
                        $recipientEmail = $form->get('email')->getData();
                        $entityManager->persist($reservation);
                        $entityManager->flush();

                        // Add Google Calendar event
                        $calendarLink = $this->googleCalendarService->addEvent($reservation, 'personnalise');
                        if (!$calendarLink) {
                            $this->logger->warning('Failed to add Google Calendar event for personnalise reservation: ' . $reservation->getId());
                        }

                        $services = $reservation->getServices();
                        $servicesList = ($services === null || $services->isEmpty()) ? 'Non spécifié' : implode(', ', array_map(fn($s) => $s->getTitre(), $services->toArray()));
                        $eventDate = $reservation->getDate()->format('d/m/Y');
                        $userName = $reservation->getPrenom() ?: 'Client';
                        $smsMessage = sprintf(
                            'Cher(e) %s, votre réservation personnalisée pour les services "%s" du %s a été confirmée. %s Eventora vous remercie !',
                            $userName,
                            $servicesList,
                            $eventDate,
                            $calendarLink ? "Consultez votre événement : $calendarLink" : ''
                        );

                        $this->logger->info('Sending Brevo email to: ' . $recipientEmail);
                        $emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
                            'sender' => ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail],
                            'to' => [['email' => $recipientEmail, 'name' => $userName]],
                            'templateId' => 4,
                            'params' => [
                                'name' => $userName,
                                'date' => $eventDate,
                                'servicesList' => $servicesList,
                                'calendarLink' => $calendarLink ?: 'N/A'
                            ]
                        ]);

                        try {
                            $result = $this->emailApiInstance->sendTransacEmail($emailData);
                            $this->logger->info('Brevo API response: ' . json_encode($result));
                        } catch (ApiException $e) {
                            $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                        }

                        // Send SMS via Twilio
                        try {
                            $this->twilioClient->messages->create(
                                $reservation->getNumtel(),
                                [
                                    'from' => $this->twilioFromNumber,
                                    'body' => $smsMessage
                                ]
                            );
                            $this->logger->info('Twilio SMS sent to: ' . $reservation->getNumtel());
                        } catch (\Exception $e) {
                            $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                        }

                        return new JsonResponse([
                            'success' => true,
                            'message' => $smsMessage
                        ]);
                    } catch (ApiException $e) {
                        $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                        try {
                            $this->twilioClient->messages->create(
                                $reservation->getNumtel(),
                                [
                                    'from' => $this->twilioFromNumber,
                                    'body' => $smsMessage
                                ]
                            );
                        } catch (\Exception $e) {
                            $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                        }
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur lors de l\'envoi de l\'email.'
                        ], 500);
                    } catch (\Exception $e) {
                        $this->logger->error('Error saving personnalise reservation or sending SMS: ' . $e->getMessage(), ['exception' => $e]);
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur serveur lors de la création de la réservation.'
                        ], 500);
                    }
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
            $this->logger->info('Submitted personnalise form data: ' . json_encode($form->getData()));
            $errors = $form->getErrors(true);
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $numtel = $form->get('numtel')->getData();
                $reservation->setNumtel('+216' . $numtel);
                $recipientEmail = $form->get('email')->getData();
                $entityManager->persist($reservation);
                $entityManager->flush();

                // Add Google Calendar event
                $calendarLink = $this->googleCalendarService->addEvent($reservation, 'personnalise');
                if (!$calendarLink) {
                    $this->logger->warning('Failed to add Google Calendar event for personnalise reservation: ' . $reservation->getId());
                    $this->addFlash('warning', 'Événement non ajouté au calendrier Google.');
                }

                $services = $reservation->getServices();
                $servicesList = ($services === null || $services->isEmpty()) ? 'Non spécifié' : implode(', ', array_map(fn($s) => $s->getTitre(), $services->toArray()));
                $eventDate = $reservation->getDate()->format('d/m/Y');
                $userName = $reservation->getPrenom() ?: 'Client';
                $successMessage = sprintf(
                    'Cher(e) %s, votre réservation personnalisée pour les services "%s" du %s a été confirmée. %s Eventora vous remercie !',
                    $userName,
                    $servicesList,
                    $eventDate,
                    $calendarLink ? "Consultez votre événement : $calendarLink" : ''
                );

                $this->logger->info('Sending Brevo email to: ' . $recipientEmail);
                $emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
                    'sender' => ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail],
                    'to' => [['email' => $recipientEmail, 'name' => $userName]],
                    'templateId' => 4,
                    'params' => [
                        'name' => $userName,
                        'date' => $eventDate,
                        'servicesList' => $servicesList,
                        'calendarLink' => $calendarLink ?: 'N/A'
                    ]
                ]);

                try {
                    $result = $this->emailApiInstance->sendTransacEmail($emailData);
                    $this->logger->info('Brevo API response: ' . json_encode($result));
                } catch (ApiException $e) {
                    $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                    $this->addFlash('warning', 'Réservation confirmée, mais erreur lors de l\'envoi de l\'email.');
                }

                // Send SMS via Twilio
                try {
                    $this->twilioClient->messages->create(
                        $reservation->getNumtel(),
                        [
                            'from' => $this->twilioFromNumber,
                            'body' => $successMessage
                        ]
                    );
                    $this->logger->info('Twilio SMS sent to: ' . $reservation->getNumtel());
                } catch (\Exception $e) {
                    $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('warning', 'Réservation confirmée, mais erreur lors de l\'envoi du SMS.');
                }

                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
            } catch (ApiException $e) {
                $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                try {
                    $this->twilioClient->messages->create(
                        $reservation->getNumtel(),
                        [
                            'from' => $this->twilioFromNumber,
                            'body' => $successMessage
                        ]
                    );
                    $this->addFlash('warning', 'Réservation confirmée, mais erreur lors de l\'envoi de l\'email.');
                } catch (\Exception $e) {
                    $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email et SMS.');
                }
                return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
            } catch (\Exception $e) {
                $this->logger->error('Error saving personnalise reservation or sending SMS: ' . $e->getMessage(), ['exception' => $e]);
                $this->addFlash('error', 'Erreur serveur lors de la création de la réservation.');
            }
        }

        return $this->render('admin/reservation/user_personnalise_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/pack/search', name: 'user_pack_search', methods: ['GET'])]
    public function searchPack(Request $request, PackRepository $packRepository): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->error('Error in pack search: ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['results' => []], 500);
        }
    }

    #[Route('/list', name: 'user_reservations', methods: ['GET'])]
    public function index(
        ReservationpackRepository $reservationpackRepository,
        ReservationpersonnaliseRepository $reservationpersonnaliseRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof Membre) {
            throw $this->createAccessDeniedException('Utilisateur non connecté ou non valide.');
        }

        $userId = $user->getId();

        $packReservations = $reservationpackRepository->findBy(['membre' => $userId]);
        $personaliseReservations = $reservationpersonnaliseRepository->findBy(['membre' => $userId]);

        $reservations = [];
        foreach ($packReservations as $reservation) {
            $reservations[] = [
                'type' => 'Pack',
                'IDReservationPack' => $reservation->getIDReservationPack(),
                'IDReservationPersonalise' => null,
                'nom' => $reservation->getNom(),
                'prenom' => $reservation->getPrenom(),
                'date' => $reservation->getDate(),
                'pack' => $reservation->getPack(),
                'services' => [],
                'status' => $reservation->getStatus(),
            ];
        }
        foreach ($personaliseReservations as $reservation) {
            $reservations[] = [
                'type' => 'Personnalisée',
                'IDReservationPack' => null,
                'IDReservationPersonalise' => $reservation->getIDReservationPersonalise(),
                'nom' => $reservation->getNom(),
                'prenom' => $reservation->getPrenom(),
                'date' => $reservation->getDate(),
                'pack' => null,
                'services' => $reservation->getServices(),
                'status' => $reservation->getStatus(),
            ];
        }

        $pagination = $paginator->paginate(
            $reservations,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('admin/reservation/reservations.html.twig', [
            'reservations' => $pagination,
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
<?php

namespace App\Controller\Users;


use App\Entity\GService;
use App\Entity\Reservationpack;
use App\Entity\Reservationpersonnalise;
use App\Entity\Membre;
use App\Entity\Pack;
use Knp\Snappy\Pdf;
use App\Form\ReservationPackType;
use App\Form\ReservationPersonnaliseType;
use App\Repository\GServiceRepository;
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
    
        // Préparer les données de l'utilisateur pour le formulaire
        $formOptions = [
            'user_data' => [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'numtel' => preg_replace('/^\+216/', '', $user->getNumTel()), // Supprimer le préfixe +216 si présent
            ]
        ];
    
        $form = $this->createForm(ReservationPackType::class, $reservation, $formOptions);
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
                            $this->logger->warning('Failed to add Google Calendar event for pack reservation: ' . $reservation->getIDReservationPack());
                        }
    
                        $pack = $reservation->getPack();
                        $packName = $pack ? $pack->getNomPack() : 'Non spécifié';
                        $eventDate = $reservation->getDate()->format('d/m/Y');
                        $userName = $reservation->getPrenom() ?: 'Client';
                        $successMessage = sprintf(
                            'Cher(e) %s, votre réservation pour le pack "%s" du %s a été enregistrée. Merci de vérifier votre panier et procéder au paiement.',
                            $userName,
                            $packName,
                            $eventDate
                        );
    
                        // Ajouter le pack au panier
                        $cartItems = $request->getSession()->get('cartItems', []);
                        
                        // Vider le panier d'abord pour ne pas avoir de duplication
                        $cartItems = [];
                        
                        if ($pack) {
                            $cartItems[] = [
                                'id' => $pack->getId(),
                                'title' => $pack->getNomPack(),
                                'price' => $pack->getPrix() . ' dt',
                                'location' => 'N/A',
                                'type' => 'Pack',
                                'quantity' => 1,
                                'image' => $pack->getImagePath() ?? 'https://via.placeholder.com/400x200',
                                'reservation_id' => $reservation->getIDReservationPack(),
                                'reservation_type' => 'pack'
                            ];
                        }
                        
                        $request->getSession()->set('cartItems', $cartItems);
                        $request->getSession()->set('reservation_data', [
                            'id' => $reservation->getIDReservationPack(),
                            'type' => 'pack',
                            'date' => $eventDate,
                            'client' => $userName
                        ]);
    
                        $this->logger->info('Sending Brevo email to: ' . $recipientEmail);
                        $emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
                            'sender' => ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail],
                            'to' => [['email' => $recipientEmail, 'name' => $userName]],
                            'templateId' => 1,
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
    
                        // Return redirect URL for AJAX - Always use history route
                        $redirectUrl = $this->generateUrl('app_user_history');

                        return new JsonResponse([
                            'success' => true,
                            'message' => $successMessage,
                            'redirectUrl' => $redirectUrl
                        ]);
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
                    'errors' => $errors,
                    'message' => 'Veuillez corriger les erreurs dans le formulaire.'
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
                    $this->logger->warning('Failed to add Google Calendar event for pack reservation: ' . $reservation->getIDReservationPack());
                }
    
                $pack = $reservation->getPack();
                $packName = $pack ? $pack->getNomPack() : 'Non spécifié';
                $eventDate = $reservation->getDate()->format('d/m/Y');
                $userName = $reservation->getPrenom() ?: 'Client';
                $successMessage = sprintf(
                    'Cher(e) %s, votre réservation pour le pack "%s" du %s a été enregistrée. Merci de vérifier votre panier et procéder au paiement.',
                    $userName,
                    $packName,
                    $eventDate
                );
    
                // Ajouter le pack au panier
                $cartItems = $request->getSession()->get('cartItems', []);
                
                // Vider le panier d'abord pour ne pas avoir de duplication
                $cartItems = [];
                
                if ($pack) {
                    $cartItems[] = [
                        'id' => $pack->getId(),
                        'title' => $pack->getNomPack(),
                        'price' => $pack->getPrix() . ' dt',
                        'location' => 'N/A',
                        'type' => 'Pack',
                        'quantity' => 1,
                        'image' => $pack->getImagePath() ?? 'https://via.placeholder.com/400x200',
                        'reservation_id' => $reservation->getIDReservationPack(),
                        'reservation_type' => 'pack'
                    ];
                }
                
                $request->getSession()->set('cartItems', $cartItems);
                $request->getSession()->set('reservation_data', [
                    'id' => $reservation->getIDReservationPack(),
                    'type' => 'pack',
                    'date' => $eventDate,
                    'client' => $userName
                ]);
    
                $this->addFlash('success', $successMessage);
                
                // Rediriger vers le panier
                return $this->redirectToRoute('app_panier_index');
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
                    $this->logger->info('Twilio SMS sent to: ' . $reservation->getNumtel());
                    $this->addFlash('warning', 'Réservation enregistrée, mais erreur lors de l\'envoi de l\'email.');
                } catch (\Exception $e) {
                    $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email et SMS.');
                }
                
                // Rediriger vers le panier même en cas d'erreur avec les e-mails/SMS
                return $this->redirectToRoute('app_panier_index');
            } catch (\Exception $e) {
                $this->logger->error('Error saving pack reservation or sending SMS: ' . $e->getMessage(), ['exception' => $e]);
                $this->addFlash('error', 'Erreur serveur lors de la création de la réservation.');
                
                // Rediriger vers la page de réservation en cas d'erreur
                return $this->redirectToRoute('user_reservation_pack_new');
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
    
        // Préparer les données de l'utilisateur pour le formulaire
        $formOptions = [
            'user_data' => [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'numtel' => preg_replace('/^\+216/', '', $user->getNumTel()), // Supprimer le préfixe +216 si présent
            ]
        ];
    
        $form = $this->createForm(ReservationPersonnaliseType::class, $reservation, $formOptions);
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
                            $this->logger->warning('Failed to add Google Calendar event for personnalise reservation: ' . $reservation->getIdReservationPersonalise());
                        }

                        $services = $reservation->getServices();
                        $servicesList = ($services === null || $services->isEmpty()) ? 'Non spécifié' : implode(', ', array_map(fn($s) => $s->getTitre(), $services->toArray()));
                        $eventDate = $reservation->getDate()->format('d/m/Y');
                        $userName = $reservation->getPrenom() ?: 'Client';
                        $successMessage = sprintf(
                            'Cher(e) %s, votre réservation personnalisée pour les services "%s" du %s a été enregistrée. Merci de vérifier votre panier et procéder au paiement.',
                            $userName,
                            $servicesList,
                            $eventDate
                        );

                        // Ajouter les services au panier
                        $cartItems = $request->getSession()->get('cartItems', []);
                        
                        // Vider le panier d'abord pour ne pas avoir de duplication
                        $cartItems = [];
                        
                        if ($services) {
                            foreach ($services as $service) {
                                $cartItems[] = [
                                    'id' => $service->getId(),
                                    'title' => $service->getTitre(),
                                    'price' => $service->getPrix() . ' dt',
                                    'location' => $service->getLocation(),
                                    'type' => $service->getTypeService(),
                                    'quantity' => 1,
                                    'image' => $service->getImage() ?? 'https://via.placeholder.com/400x200',
                                    'reservation_id' => $reservation->getIDReservationPersonalise(),
                                    'reservation_type' => 'personnalise'
                                ];
                            }
                        }
                        
                        $request->getSession()->set('cartItems', $cartItems);
                        $request->getSession()->set('reservation_data', [
                            'id' => $reservation->getIDReservationPersonalise(),
                            'type' => 'personnalise',
                            'date' => $eventDate,
                            'client' => $userName
                        ]);

                        $this->logger->info('Sending Brevo email to: ' . $recipientEmail);
                        // Envoyer e-mail de confirmation
                        $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail();
                        $sendSmtpEmail['to'] = [['email' => $recipientEmail, 'name' => $userName]];
                        $sendSmtpEmail['templateId'] = 1;
                        $sendSmtpEmail['params'] = [
                            'message' => $successMessage,
                            'userName' => $userName
                        ];
                        $sendSmtpEmail['subject'] = 'Confirmation de réservation - Eventora';
                        $sendSmtpEmail['sender'] = ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail];
                        
                        try {
                            $this->emailApiInstance->sendTransacEmail($sendSmtpEmail);
                        } catch (ApiException $e) {
                            $this->logger->error('Brevo API error: ' . $e->getMessage() . ' | Response: ' . $e->getResponseBody());
                        }

                        $this->addFlash('success', $successMessage);
                        
                        // Rediriger vers le panier
                        $redirectUrl = $this->generateUrl('app_panier_index');

                        return new JsonResponse([
                            'success' => true,
                            'message' => $successMessage,
                            'redirectUrl' => $redirectUrl
                        ]);
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
                    'errors' => $errors,
                    'message' => 'Veuillez corriger les erreurs dans le formulaire.'
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
                    $this->logger->warning('Failed to add Google Calendar event for personnalise reservation: ' . $reservation->getIdReservationPersonalise());
                }
    
                $services = $reservation->getServices();
                $servicesList = ($services === null || $services->isEmpty()) ? 'Non spécifié' : implode(', ', array_map(fn($s) => $s->getTitre(), $services->toArray()));
                $eventDate = $reservation->getDate()->format('d/m/Y');
                $userName = $reservation->getPrenom() ?: 'Client';
                $successMessage = sprintf(
                    'Cher(e) %s, votre réservation personnalisée pour les services "%s" du %s a été enregistrée. Merci de vérifier votre panier et procéder au paiement.',
                    $userName,
                    $servicesList,
                    $eventDate
                );

                // Ajouter les services au panier
                $cartItems = $request->getSession()->get('cartItems', []);
                
                // Vider le panier d'abord pour ne pas avoir de duplication
                $cartItems = [];
                
                if ($services) {
                    foreach ($services as $service) {
                        $cartItems[] = [
                            'id' => $service->getId(),
                            'title' => $service->getTitre(),
                            'price' => $service->getPrix() . ' dt',
                            'location' => $service->getLocation(),
                            'type' => $service->getTypeService(),
                            'quantity' => 1,
                            'image' => $service->getImage() ?? 'https://via.placeholder.com/400x200',
                            'reservation_id' => $reservation->getIDReservationPersonalise(),
                            'reservation_type' => 'personnalise'
                        ];
                    }
                }
                
                $request->getSession()->set('cartItems', $cartItems);
                $request->getSession()->set('reservation_data', [
                    'id' => $reservation->getIDReservationPersonalise(),
                    'type' => 'personnalise',
                    'date' => $eventDate,
                    'client' => $userName
                ]);

                $this->addFlash('success', $successMessage);
                
                // Rediriger vers le panier
                return $this->redirectToRoute('app_panier_index');
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
                    $this->logger->info('Twilio SMS sent to: ' . $reservation->getNumtel());
                    $this->addFlash('warning', 'Réservation enregistrée, mais erreur lors de l\'envoi de l\'email.');
                } catch (\Exception $e) {
                    $this->logger->error('Twilio SMS error: ' . $e->getMessage(), ['exception' => $e]);
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email et SMS.');
                }
                
                // Rediriger vers le panier même en cas d'erreur avec les e-mails/SMS
                return $this->redirectToRoute('app_panier_index');
            } catch (\Exception $e) {
                $this->logger->error('Error saving personnalise reservation or sending SMS: ' . $e->getMessage(), ['exception' => $e]);
                $this->addFlash('error', 'Erreur serveur lors de la création de la réservation.');
                
                // Rediriger vers la page de réservation en cas d'erreur
                return $this->redirectToRoute('user_reservation_personnalise_new');
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
    #[Route('/service/{id}/details', name: 'user_service_details', methods: ['GET'])]
    public function getServiceDetails(int $id, GServiceRepository $serviceRepository): JsonResponse
    {
        try {
            $service = $serviceRepository->find($id);
            if (!$service) {
                return new JsonResponse([
                    'success' => false,
                    'message' => sprintf('Service avec ID %d non trouvé.', $id)
                ], 404);
            }
            $imagePath = $service->getImage();
            $imageUrl = $imagePath ? $this->getParameter('app.base_url') . '/' . ltrim($imagePath, '/') : null;
            return new JsonResponse([
                'success' => true,
                'service' => [
                    'titre' => $service->getTitre() ?? 'Non spécifié',
                    'description' => $service->getDescription() ?? 'Non spécifiée',
                    'prix' => $service->getPrix() ?? 'Non spécifié',
                    'location' => $service->getLocation() ?? 'Non spécifiée',
                    'type_service' => $service->getTypeService() ?? 'Non spécifié',
                    'image' => $imageUrl,
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des détails du service ID ' . $id . ': ' . $e->getMessage(), ['exception' => $e]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    } 
    
}
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
use Psr\Log\LoggerInterface;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twilio\Rest\Client as TwilioClient;

#[Route('/admin/reservations')]
class ReservationsController extends AbstractController
{
    private $twilioClient;
    private $twilioFromNumber;
    private $emailApiInstance;
    private $logger;
    private $brevoSenderEmail;
    private $brevoSenderName;

    public function __construct(
        string $twilioAccountSid,
        string $twilioAuthToken,
        string $twilioPhoneNumber,
        string $brevoApiKey,
        string $brevoSenderEmail,
        string $brevoSenderName,
        LoggerInterface $logger
    ) {
        // Initialize Twilio client
        $this->twilioClient = new TwilioClient($twilioAccountSid, $twilioAuthToken);
        $this->twilioFromNumber = $twilioPhoneNumber;

        // Initialize Brevo email client
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $brevoApiKey);
        $this->emailApiInstance = new TransactionalEmailsApi(new GuzzleClient(), $config);
        $this->brevoSenderEmail = $brevoSenderEmail;
        $this->brevoSenderName = $brevoSenderName;

        $this->logger = $logger;
    }

    #[Route('/pack', name: 'admin_reservations_pack', methods: ['GET'])]
    public function reservationsPack(
        ReservationpackRepository $reservationPackRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status_filter', '');
        $dateFilter = $request->query->get('date_filter', '');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        $queryBuilder = $reservationPackRepository->createQueryBuilder('rp');

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

        if ($statusFilter) {
            $queryBuilder
                ->andWhere('rp.status = :status')
                ->setParameter('status', $statusFilter);
        }

        if ($dateFilter) {
            $queryBuilder
                ->andWhere('rp.date = :date')
                ->setParameter('date', new \DateTime($dateFilter));
        }

        $queryBuilder->orderBy('rp.' . $sortBy, $sortOrder);

        $reservationPacks = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6
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

        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status_filter', '');
        $dateFilter = $request->query->get('date_filter', '');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'desc');

        $queryBuilder = $reservationPersonnaliseRepository->createQueryBuilder('rp');

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

        if ($statusFilter) {
            $queryBuilder
                ->andWhere('rp.status = :status')
                ->setParameter('status', $statusFilter);
        }

        if ($dateFilter) {
            $queryBuilder
                ->andWhere('rp.date = :date')
                ->setParameter('date', new \DateTime($dateFilter));
        }

        $queryBuilder->orderBy('rp.' . $sortBy, $sortOrder);

        $reservationPersonnalises = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6
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
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Check if any fields have changed
                $uow = $entityManager->getUnitOfWork();
                $uow->computeChangeSets();
                $changeSet = $uow->getEntityChangeSet($reservation);
    
                if (!empty($changeSet)) {
                    $entityManager->flush();
    
                    // Envoyer une notification par email et SMS
                    $emailSent = true;
                    $smsSent = true;
                    try {
                        $this->sendUpdateNotification($reservation, 'pack');
                        $this->logger->info('Email de mise à jour envoyé avec succès à : ' . $reservation->getEmail());
                    } catch (ApiException $e) {
                        $this->logger->error('Erreur lors de l\'envoi de l\'email de mise à jour', [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'response' => $e->getResponseBody(),
                            'headers' => $e->getResponseHeaders(),
                        ]);
                        $emailSent = false;
                    }
    
                    try {
                        $this->sendUpdateSms($reservation, 'pack');
                        $this->logger->info('SMS de mise à jour envoyé avec succès à : ' . $reservation->getNumtel());
                    } catch (\Twilio\Exceptions\TwilioException $e) {
                        $this->logger->error('Erreur lors de l\'envoi du SMS de mise à jour : ' . $e->getMessage());
                        $smsSent = false;
                    }
    
                    $successMessage = 'Réservation Pack modifiée avec succès. Notifications envoyées au client.';
                    if (!$emailSent || !$smsSent) {
                        $successMessage = 'Réservation Pack modifiée, mais certaines notifications n\'ont pas pu être envoyées.';
                    }
    
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => $emailSent && $smsSent,
                            'message' => $successMessage,
                        ], $emailSent && $smsSent ? 200 : 500);
                    }
    
                    $this->addFlash($emailSent && $smsSent ? 'success' : 'warning', $successMessage);
                } else {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Aucune modification détectée.',
                        ], 200);
                    }
                    $this->addFlash('info', 'Aucune modification détectée.');
                }
    
                return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
            } else {
                // Gestion des erreurs du formulaire
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[$error->getOrigin()->getName()][] = $error->getMessage();
                }
    
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $errors,
                    ], 400);
                }
    
                foreach ($errors as $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->addFlash('error', $error);
                    }
                }
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
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Check if any fields have changed
                $uow = $entityManager->getUnitOfWork();
                $uow->computeChangeSets();
                $changeSet = $uow->getEntityChangeSet($reservation);
    
                if (!empty($changeSet)) {
                    $entityManager->flush();
    
                    // Envoyer une notification par email et SMS
                    $emailSent = true;
                    $smsSent = true;
                    try {
                        $this->sendUpdateNotification($reservation, 'personnalise');
                        $this->logger->info('Email de mise à jour envoyé avec succès à : ' . $reservation->getEmail());
                    } catch (ApiException $e) {
                        $this->logger->error('Erreur lors de l\'envoi de l\'email de mise à jour', [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'response' => $e->getResponseBody(),
                            'headers' => $e->getResponseHeaders(),
                        ]);
                        $emailSent = false;
                    }
    
                    try {
                        $this->sendUpdateSms($reservation, 'personnalise');
                        $this->logger->info('SMS de mise à jour envoyé avec succès à : ' . $reservation->getNumtel());
                    } catch (\Twilio\Exceptions\TwilioException $e) {
                        $this->logger->error('Erreur lors de l\'envoi du SMS de mise à jour : ' . $e->getMessage());
                        $smsSent = false;
                    }
    
                    $successMessage = 'Réservation Personnalisée modifiée avec succès. Notifications envoyées au client.';
                    if (!$emailSent || !$smsSent) {
                        $successMessage = 'Réservation Personnalisée modifiée, mais certaines notifications n\'ont pas pu être envoyées.';
                    }
    
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => $emailSent && $smsSent,
                            'message' => $successMessage,
                        ], $emailSent && $smsSent ? 200 : 500);
                    }
    
                    $this->addFlash($emailSent && $smsSent ? 'success' : 'warning', $successMessage);
                } else {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Aucune modification détectée.',
                        ], 200);
                    }
                    $this->addFlash('info', 'Aucune modification détectée.');
                }
    
                return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
            } else {
                // Gestion des erreurs du formulaire
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[$error->getOrigin()->getName()][] = $error->getMessage();
                }
    
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'errors' => $errors,
                    ], 400);
                }
    
                foreach ($errors as $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->addFlash('error', $error);
                    }
                }
            }
        }
    
        return $this->render('admin/reservation/personnalise_edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }
    /**
     * Envoie une notification par email au client avec les détails mis à jour de la réservation.
     *
     * @param Reservationpack|Reservationpersonnalise $reservation
     * @param string $type 'pack' or 'personnalise'
     * @throws ApiException
     */
    private function sendUpdateNotification($reservation, string $type): void
    {
        $recipientEmail = $reservation->getEmail();
        $userName = $reservation->getPrenom() ?: 'Client';
        $eventDate = $reservation->getDate()->format('d/m/Y');
        $status = $reservation->getStatus();

        // Préparer les paramètres spécifiques selon le type de réservation
        if ($type === 'pack') {
            $packName = $reservation->getPack() ? $reservation->getPack()->getNomPack() : 'Non spécifié';
            $emailParams = [
                'name' => $userName,
                'date' => $eventDate,
                'packName' => $packName,
                'status' => $status,
            ];
        } else {
            $services = $reservation->getServices();
            $servicesList = ($services === null || $services->isEmpty())
                ? 'Non spécifié'
                : '<li>' . implode('</li><li>', array_map(fn($s) => $s->getTitre(), $services->toArray())) . '</li>';
            $emailParams = [
                'name' => $userName,
                'date' => $eventDate,
                'servicesList' => $servicesList,
                'status' => $status,
            ];
        }

        // Configurer l'email via Brevo
        $emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
            'sender' => ['name' => $this->brevoSenderName, 'email' => $this->brevoSenderEmail],
            'to' => [['email' => $recipientEmail, 'name' => $userName]],
            'templateId' => 2, // Modèle pour les mises à jour
            'params' => $emailParams,
        ]);

        // Envoyer l'email
        $this->logger->info('Envoi de l\'email de mise à jour à : ' . $recipientEmail);
        $result = $this->emailApiInstance->sendTransacEmail($emailData);
        $this->logger->info('Réponse de l\'API Brevo : ' . json_encode($result));
    }

    /**
     * Envoie une notification par SMS au client avec les détails mis à jour de la réservation.
     *
     * @param Reservationpack|Reservationpersonnalise $reservation
     * @param string $type 'pack' or 'personnalise'
     */
    private function sendUpdateSms($reservation, string $type): void
    {
        $userName = $reservation->getPrenom() ?: 'Client';
        $eventDate = $reservation->getDate()->format('d/m/Y');
        $status = $reservation->getStatus();

        if ($type === 'pack') {
            $packName = $reservation->getPack() ? $reservation->getPack()->getNomPack() : 'Non spécifié';
            $message = sprintf(
                'Cher(e) %s, votre réservation pour le pack "%s" du %s a été mise à jour. Statut : %s. Merci de choisir Eventora !',
                $userName,
                $packName,
                $eventDate,
                $status
            );
        } else {
            $services = $reservation->getServices();
            $servicesList = ($services === null || $services->isEmpty())
                ? 'Non spécifié'
                : implode(', ', array_map(fn($s) => $s->getTitre(), $services->toArray()));
            $message = sprintf(
                'Cher(e) %s, votre réservation personnalisée du %s a été mise à jour. Services : %s. Statut : %s. Merci de choisir Eventora !',
                $userName,
                $eventDate,
                $servicesList,
                $status
            );
        }

        // Envoyer le SMS via Twilio
        $this->twilioClient->messages->create(
            $reservation->getNumtel(),
            [
                'from' => $this->twilioFromNumber,
                'body' => $message
            ]
        );

        $this->logger->info('SMS de mise à jour envoyé à : ' . $reservation->getNumtel());
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

        if ($type === 'pack') {
            return $this->redirectToRoute('admin_reservations_pack', [], Response::HTTP_SEE_OTHER);
        } elseif ($type === 'personnalise') {
            return $this->redirectToRoute('admin_reservations_personnalise', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('admin_reservations', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/pack/{id}/pdf', name: 'admin_reservations_pack_pdf', methods: ['GET'])]
    public function generatePdf(Reservationpack $reservation, Pdf $knpSnappyPdf, LoggerInterface $logger): Response
    {
        $logger->info('Début de generatePdf', ['id' => $reservation->getIDReservationPack()]);
    
        // Vérifiez les données de la réservation
        $logger->debug('Données de la réservation', [
            'id' => $reservation->getIDReservationPack(),
            'nom' => $reservation->getNom(),
            'prenom' => $reservation->getPrenom(),
            'date' => $reservation->getDate() ? $reservation->getDate()->format('Y-m-d H:i:s') : null,
            'status' => $reservation->getStatus(),
            'pack' => $reservation->getPack() ? $reservation->getPack()->getNomPack() : null,
        ]);
    
        try {
            $html = $this->renderView('admin/reservation/pdf_pack_details.html.twig', [
                'reservation' => $reservation,
            ]);
            $logger->info('Template rendu', ['html_length' => strlen($html)]);
    
            $pdf = $knpSnappyPdf->getOutputFromHtml($html);
            $logger->info('PDF généré avec succès');
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la génération du PDF', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'id' => $reservation->getIDReservationPack(),
            ]);
            throw new \RuntimeException('Impossible de générer le PDF : ' . $e->getMessage());
        }
    
        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reservationpack_' . $reservation->getIDReservationPack() . '.pdf"',
            ]
        );
    }   
}
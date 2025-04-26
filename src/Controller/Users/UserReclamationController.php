<?php

namespace App\Controller\Users;

use App\Entity\Reclamation;
use App\Entity\Membre;
use App\Form\ReclamationType;
use App\Service\BrevoEmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Knp\Snappy\Pdf;

#[Route('/reclamation')]
class UserReclamationController extends AbstractController
{
    private $logger;
    private $twig;
    private $emailSender;
    private $pdf;

    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        BrevoEmailSender $emailSender,
        Pdf $pdf
    ) {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->emailSender = $emailSender;
        $this->pdf = $pdf;
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator): Response
    {
        if (!$this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_ADMIN')) {
            $this->logger->warning('Accès refusé : utilisateur non autorisé', [
                'roles' => $this->getUser() ? $this->getUser()->getRoles() : 'aucun utilisateur',
            ]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Vous devez être un membre ou un administrateur pour soumettre une réclamation.'
                ], 403);
            }
            throw $this->createAccessDeniedException('Vous devez être un membre ou un administrateur pour soumettre une réclamation.');
        }
        $user = $this->getUser();
        if (!$user) {
            $this->logger->warning('Utilisateur non connecté');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur non connecté.'
                ], 401);
            }
            $this->addFlash('error', 'Vous devez être connecté pour soumettre une réclamation.');
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof Membre) {
            $this->logger->error('L\'utilisateur connecté n\'est pas une instance de Membre', [
                'user_class' => get_class($user),
                'user_id' => $user->getId(),
            ]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Utilisateur invalide pour soumettre une réclamation.'
                ], 400);
            }
            $this->addFlash('error', 'Utilisateur invalide pour soumettre une réclamation.');
            return $this->redirectToRoute('app_home');
        }

        $reclamation = new Reclamation();
        $reclamation->setMembre($user);
        $reclamation->setStatut(Reclamation::STATUT_EN_ATTENTE);
        $reclamation->setQrCodeUrl(null);

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    try {
                        if (!$reclamation->getDate()) {
                            $reclamation->setDate(new \DateTime());
                        }

                        // Générer l'URL du QR code après avoir persisté la réclamation
                        $entityManager->persist($reclamation);
                        $entityManager->flush();

                        // Générer l'URL pour télécharger le PDF avec ngrok (temporaire pour les tests locaux)
                        $ngrokBaseUrl = 'https://abcd1234.ngrok.io'; // Remplace par ton URL ngrok réelle
                        $reclamationUrl = $ngrokBaseUrl . $this->generateUrl('app_reclamation_pdf', ['id' => $reclamation->getId()]);
                        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($reclamationUrl);
                        $this->logger->info('URL du QR code générée', [
                            'reclamation_id' => $reclamation->getId(),
                            'reclamation_url' => $reclamationUrl,
                            'qr_code_url' => $qrCodeUrl,
                        ]);
                        $reclamation->setQrCodeUrl($qrCodeUrl);
                        $entityManager->flush();

                        $membreHtml = $this->twig->render('admin/reclamations/email_rec_membre.html.twig', [
                            'reclamation' => $reclamation,
                            'membre' => $user,
                        ]);
                        $emailSentMembre = $this->emailSender->sendEmail(
                            $user->getEmail(),
                            $user->getNom() . ' ' . $user->getPrenom(),
                            'Confirmation de votre réclamation - Eventora',
                            $membreHtml
                        );
                        $this->logger->info('Envoi email membre', [
                            'email' => $user->getEmail(),
                            'success' => $emailSentMembre,
                            'reclamation_id' => $reclamation->getId(),
                        ]);

                        $admins = $entityManager->getRepository(Membre::class)->findByRole('ADMIN');
                        $this->logger->info('Admins trouvés pour envoi email', [
                            'admin_emails' => array_map(fn($admin) => $admin->getEmail(), $admins),
                            'reclamation_id' => $reclamation->getId(),
                        ]);
                        $emailSentAdmins = true;
                        foreach ($admins as $admin) {
                            try {
                                $adminHtml = $this->twig->render('admin/reclamations/email_rec_admin.html.twig', [
                                    'reclamation' => $reclamation,
                                    'membre' => $user,
                                    'admin' => $admin,
                                ]);
                                $emailSent = $this->emailSender->sendEmail(
                                    $admin->getEmail(),
                                    $admin->getNom() . ' ' . $admin->getPrenom(),
                                    'Nouvelle réclamation soumise - Eventora',
                                    $adminHtml
                                );
                                $this->logger->info('Envoi email admin', [
                                    'email' => $admin->getEmail(),
                                    'success' => $emailSent,
                                    'reclamation_id' => $reclamation->getId(),
                                ]);
                                if (!$emailSent) {
                                    $emailSentAdmins = false;
                                    $this->logger->warning('Échec de l\'envoi de l\'email à l\'admin', [
                                        'email' => $admin->getEmail(),
                                        'reclamation_id' => $reclamation->getId(),
                                    ]);
                                }
                            } catch (\Exception $e) {
                                $this->logger->error('Erreur lors de l\'envoi de l\'email à l\'admin', [
                                    'email' => $admin->getEmail(),
                                    'reclamation_id' => $reclamation->getId(),
                                    'exception' => $e->getMessage(),
                                ]);
                                $emailSentAdmins = false;
                            }
                        }

                        $this->logger->info('Réclamation enregistrée avec succès', [
                            'reclamation_id' => $reclamation->getId(),
                            'email_sent_membre' => $emailSentMembre,
                            'email_sent_admins' => $emailSentAdmins,
                        ]);

                        return new JsonResponse([
                            'success' => true,
                            'message' => 'Votre réclamation a été soumise avec succès !'
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de la soumission de la réclamation', [
                            'exception' => $e->getMessage(),
                            'stack_trace' => $e->getTraceAsString(),
                        ]);
                        return new JsonResponse([
                            'success' => false,
                            'message' => 'Erreur lors de la soumission : ' . $e->getMessage()
                        ], 500);
                    }
                }

                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $fieldName = $error->getOrigin() ? $error->getOrigin()->getName() : 'reclamation';
                    $errors[$fieldName][] = $error->getMessage();
                }

                $this->logger->error('Erreurs de validation du formulaire', [
                    'errors' => $errors,
                ]);

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

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (!$reclamation->getDate()) {
                    $reclamation->setDate(new \DateTime());
                }

                // Générer l'URL du QR code après avoir persisté la réclamation
                $entityManager->persist($reclamation);
                $entityManager->flush();

                // Générer l'URL pour télécharger le PDF avec ngrok (temporaire pour les tests locaux)
                $ngrokBaseUrl = 'https://abcd1234.ngrok.io'; // Remplace par ton URL ngrok réelle
                $reclamationUrl = $ngrokBaseUrl . $this->generateUrl('app_reclamation_pdf', ['id' => $reclamation->getId()]);
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($reclamationUrl);
                $this->logger->info('URL du QR code générée', [
                    'reclamation_id' => $reclamation->getId(),
                    'reclamation_url' => $reclamationUrl,
                    'qr_code_url' => $qrCodeUrl,
                ]);
                $reclamation->setQrCodeUrl($qrCodeUrl);
                $entityManager->flush();

                $membreHtml = $this->twig->render('admin/reclamations/email_rec_membre.html.twig', [
                    'reclamation' => $reclamation,
                    'membre' => $user,
                ]);
                $emailSentMembre = $this->emailSender->sendEmail(
                    $user->getEmail(),
                    $user->getNom() . ' ' . $user->getPrenom(),
                    'Confirmation de votre réclamation - Eventora',
                    $membreHtml
                );
                $this->logger->info('Envoi email membre (non-AJAX)', [
                    'email' => $user->getEmail(),
                    'success' => $emailSentMembre,
                    'reclamation_id' => $reclamation->getId(),
                ]);

                if (!$emailSentMembre) {
                    $this->addFlash('warning', 'Réclamation soumise, mais l\'email de confirmation n\'a pas pu être envoyé.');
                }

                $admins = $entityManager->getRepository(Membre::class)->findByRole('ADMIN');
                $this->logger->info('Admins trouvés pour envoi email (non-AJAX)', [
                    'admin_emails' => array_map(fn($admin) => $admin->getEmail(), $admins),
                    'reclamation_id' => $reclamation->getId(),
                ]);
                $emailSentAdmins = true;
                foreach ($admins as $admin) {
                    try {
                        $adminHtml = $this->twig->render('admin/reclamations/email_rec_admin.html.twig', [
                            'reclamation' => $reclamation,
                            'membre' => $user,
                            'admin' => $admin,
                        ]);
                        $emailSent = $this->emailSender->sendEmail(
                            $admin->getEmail(),
                            $admin->getNom() . ' ' . $admin->getPrenom(),
                            'Nouvelle réclamation soumise - Eventora',
                            $adminHtml
                        );
                        $this->logger->info('Envoi email admin (non-AJAX)', [
                            'email' => $admin->getEmail(),
                            'success' => $emailSent,
                            'reclamation_id' => $reclamation->getId(),
                        ]);
                        if (!$emailSent) {
                            $emailSentAdmins = false;
                            $this->logger->warning('Échec de l\'envoi de l\'email à l\'admin (non-AJAX)', [
                                'email' => $admin->getEmail(),
                                'reclamation_id' => $reclamation->getId(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de l\'envoi de l\'email à l\'admin (non-AJAX)', [
                            'email' => $admin->getEmail(),
                            'reclamation_id' => $reclamation->getId(),
                            'exception' => $e->getMessage(),
                        ]);
                        $emailSentAdmins = false;
                    }
                }

                $this->logger->info('Réclamation enregistrée avec succès (non-AJAX)', [
                    'reclamation_id' => $reclamation->getId(),
                    'email_sent_membre' => $emailSentMembre,
                    'email_sent_admins' => $emailSentAdmins,
                ]);

                $this->addFlash('success', 'Votre réclamation a été soumise avec succès !');
                return $this->redirectToRoute('app_home', ['_fragment' => 'fh5co-started']);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la soumission de la réclamation (non-AJAX)', [
                    'exception' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'Erreur lors de la soumission : ' . $e->getMessage());
            }
        }
        return $this->render('admin/reclamations/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/public/reclamation/{id}', name: 'app_reclamation_public', methods: ['GET'])]
    public function showPublicReclamation(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/public.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/public/pdf/{id}', name: 'app_reclamation_pdf', methods: ['GET'])]
    public function downloadPdf(Reclamation $reclamation): Response
    {
        try {
            // Rendre le template HTML pour le PDF
            $html = $this->twig->render('reclamation/pdf.html.twig', [
                'reclamation' => $reclamation,
            ]);

            // Générer le PDF
            $pdfContent = $this->pdf->getOutputFromHtml($html);

            // Créer la réponse avec le PDF
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="reclamation_' . $reclamation->getId() . '.pdf"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            $this->logger->info('PDF généré pour la réclamation', [
                'reclamation_id' => $reclamation->getId(),
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du PDF', [
                'reclamation_id' => $reclamation->getId(),
                'exception' => $e->getMessage(),
            ]);
            throw $this->createNotFoundException('Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class GoogleCalendarController extends AbstractController
{
    #[Route('/google-auth', name: 'google_auth')]
    public function googleAuth(Request $request, GoogleCalendarService $googleCalendarService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Récupérer la page de retour depuis les paramètres
        $returnTo = $request->query->get('return_to');
        
        // Stocker la page de retour en session pour l'utiliser après le callback
        if ($returnTo) {
            $request->getSession()->set('google_auth_return_to', $returnTo);
        }
        
        $authUrl = $googleCalendarService->getAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/google-callback', name: 'google_callback')]
    public function googleCallback(Request $request, GoogleCalendarService $googleCalendarService, LoggerInterface $logger): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $code = $request->query->get('code');
        if (!$code) {
            $this->addFlash('error', 'Échec de l\'authentification Google Calendar.');
            $logger->error('Google Calendar callback: No code provided');
            return $this->redirectToRoute('app_home_page');
        }

        if ($googleCalendarService->handleCallback($code)) {
            $this->addFlash('success', 'Connexion à Google Calendar réussie !');
        } else {
            $this->addFlash('error', 'Échec de l\'authentification Google Calendar.');
        }

        // Récupérer la page de retour depuis la session
        $returnTo = $request->getSession()->get('google_auth_return_to');
        $request->getSession()->remove('google_auth_return_to'); // Nettoyer la session
        
        // Rediriger vers la page de retour si elle existe, sinon vers la page d'accueil
        if ($returnTo) {
            return $this->redirectToRoute($returnTo);
        }
        
        return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
    }
}
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
    public function googleAuth(GoogleCalendarService $googleCalendarService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
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

        return $this->redirectToRoute('app_home_page', ['_fragment' => 'fh5co-started']);
    }
}
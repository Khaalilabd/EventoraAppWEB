<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheck(): RedirectResponse
    {
        // This route will be called after Google authentication
        // The KnpUOAuth2ClientBundle will handle the authentication automatically
        // You can access the authenticated user here if needed

        // For now, redirect to the homepage
        return $this->redirectToRoute('app_home_page');
    }
}
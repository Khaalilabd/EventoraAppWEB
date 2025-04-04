<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSuccessListener
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $response = new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            $response->send();
            exit;
        }

        // Par défaut, redirection pour les membres normaux
        $response = new RedirectResponse($this->urlGenerator->generate('app_auth')); // Ou une autre route pour les membres
        $response->send();
        exit;
    }
}
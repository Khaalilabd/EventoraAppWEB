<?php

namespace App\EventSubscriber;

use App\Entity\Membre;
use App\Service\TwoFactorAuthService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class TwoFactorSubscriber implements EventSubscriberInterface
{
    private $twoFactorAuthService;
    private $urlGenerator;
    private $requestStack;

    public function __construct(
        TwoFactorAuthService $twoFactorAuthService,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack
    ) {
        $this->twoFactorAuthService = $twoFactorAuthService;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof Membre) {
            error_log('TwoFactorSubscriber: User is not an instance of Membre');
            return;
        }

        $session = $this->requestStack->getSession();

        error_log('TwoFactorSubscriber: Checking 2FA for user ' . $user->getEmail() . 
            ' | isTwoFactorEnabled: ' . ($user->isTwoFactorEnabled() ? 'true' : 'false') . 
            ' | 2fa_completed: ' . ($session->get('2fa_completed') ? 'true' : 'false'));

        if ($user->isTwoFactorEnabled() && !$session->get('2fa_completed')) {
            error_log('TwoFactorSubscriber: Triggering 2FA for user ' . $user->getEmail());
            $this->twoFactorAuthService->generateAndSendCode($user);
            
            $response = new RedirectResponse(
                $this->urlGenerator->generate('app_2fa_verify')
            );
            
            $session->save();
            $response->send();
            exit;
        }
    }
}
<?php

namespace App\Controller\Security;

use App\Entity\Membre;
use App\Service\TwoFactorAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/verify', name: 'app_2fa_verify')]
    public function verify(Request $request, TwoFactorAuthService $twoFactorAuthService, Security $security): Response
    {
        $user = $security->getUser();
        
        if (!$user instanceof Membre) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isTwoFactorEnabled()) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code');
            
            if ($twoFactorAuthService->verifyCode($user, $code)) {
                // Store in session that 2FA is completed
                $request->getSession()->set('2fa_completed', true);
                return $this->redirectToRoute('app_home');
            }

            $this->addFlash('error', 'Code de vérification invalide ou expiré.');
        }

        return $this->render('security/2fa_verify.html.twig');
    }

    #[Route('/2fa/toggle', name: 'app_2fa_toggle')]
    public function toggle(Request $request, TwoFactorAuthService $twoFactorAuthService, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = $security->getUser();
        
        if (!$user instanceof Membre) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $enable = $request->request->get('enable') === 'true';
            $user->setIsTwoFactorEnabled($enable);
            $entityManager->persist($user);
            $entityManager->flush();

            if ($enable) {
                $twoFactorAuthService->generateAndSendCode($user);
                $this->addFlash('success', 'La double authentification a été activée. Un code de vérification a été envoyé à votre email.');
            } else {
                $this->addFlash('success', 'La double authentification a été désactivée.');
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('security/2fa_toggle.html.twig', [
            'isEnabled' => $user->isTwoFactorEnabled()
        ]);
    }
} 
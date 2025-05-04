<?php

namespace App\Service;

use App\Entity\Membre;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\BrevoSmsSender;

class TwoFactorAuthService
{
    private $entityManager;
    private $smsSender;

    public function __construct(EntityManagerInterface $entityManager, BrevoSmsSender $smsSender)
    {
        $this->entityManager = $entityManager;
        $this->smsSender = $smsSender;
    }

    public function generateAndSendCode(Membre $user): void
    {
        if (!$user->isTwoFactorEnabled()) {
            error_log('TwoFactorAuthService: 2FA not enabled for user ' . $user->getEmail());
            return;
        }

        $code = sprintf('%06d', random_int(0, 999999));
        $expiresAt = new \DateTime('+15 minutes');

        $user->setTwoFactorCode($code);
        $user->setTwoFactorCodeExpiresAt($expiresAt);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoi du code par SMS
        $numTel = $user->getNumTel();
        // S'assurer que le numéro est au format international (ex: +216XXXXXXXX)
        if (strpos($numTel, '+') !== 0) {
            $numTel = '+216' . ltrim($numTel, '0');
        }
        $message = "Votre code de vérification Eventora : $code";
        $this->smsSender->sendSms($numTel, $message);
    }

    public function verifyCode(Membre $user, string $code): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return true;
        }

        if ($user->getTwoFactorCode() !== $code) {
            return false;
        }

        if ($user->getTwoFactorCodeExpiresAt() < new \DateTime()) {
            return false;
        }

        // Clear the code after successful verification
        $user->setTwoFactorCode(null);
        $user->setTwoFactorCodeExpiresAt(null);
        $this->entityManager->flush();

        return true;
    }
}
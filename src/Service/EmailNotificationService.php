<?php

namespace App\Service;

use App\Entity\Pack;
use App\Entity\Membre;
use App\Repository\MembreRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EmailNotificationService
{
    private MembreRepository $membreRepository;
    private TransactionalEmailSender $emailSender;
    private ParameterBagInterface $params;
    private Environment $twig;
    private LoggerInterface $logger;

    public function __construct(
        MembreRepository $membreRepository,
        TransactionalEmailSender $emailSender,
        ParameterBagInterface $params,
        Environment $twig,
        LoggerInterface $logger
    ) {
        $this->membreRepository = $membreRepository;
        $this->emailSender = $emailSender;
        $this->params = $params;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function notifyMembersOfNewPack(Pack $pack): void
    {
        // Fetch all members with role 'MEMBRE'
        $members = $this->membreRepository->findByRole('MEMBRE');
        $appName = $this->params->get('app.name', 'Eventora');

        // Debug: Log all members found
        $memberDetails = array_map(fn(Membre $member) => [
            'id' => $member->getId(),
            'email' => $member->getEmail(),
            'role' => $member->getRole(),
            'isConfirmed' => $member->isConfirmed(),
        ], $members);
        $this->logger->info('Members found with role MEMBRE', ['members' => $memberDetails]);

        if (empty($members)) {
            $this->logger->info('No members to notify for pack ' . $pack->getNomPack());
            return;
        }

        $htmlContent = $this->twig->render('emails/new_pack_notification.html.twig', [
            'pack' => $pack,
            'member' => null,
            'app_name' => $appName,
        ]);

        $recipients = array_map(function (Membre $member) {
            return [
                'email' => $member->getEmail(),
                'name' => $member->getPrenom() . ' ' . $member->getNom(),
            ];
        }, $members);

        $success = $this->emailSender->sendBatchEmails(
            recipients: $recipients,
            subject: sprintf('Nouveau Pack Ajouté : %s', $pack->getNomPack()),
            htmlContent: $htmlContent,
            senderEmail: 'eventoraeventora@gmail.com',
            senderName: $appName
        );

        if ($success) {
            $this->logger->info('Batch email sent for pack ' . $pack->getNomPack(), [
                'recipients' => array_column($recipients, 'email'),
            ]);
        } else {
            $this->logger->error('Failed to send batch email for pack ' . $pack->getNomPack());
        }
    }
}
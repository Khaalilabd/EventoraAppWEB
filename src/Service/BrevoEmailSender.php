<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class BrevoEmailSender
{
    private $apiKey;
    private $client;
    private $logger;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->apiKey = $params->get('brevo_api_key');
        $this->client = HttpClient::create();
        $this->logger = $logger;
    }

    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        string $senderEmail = 'eventoraeventora@gmail.com
',
        string $senderName = 'Eventora'
    ): bool {
        try {
            $response = $this->client->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'sender' => [
                        'name' => $senderName,
                        'email' => $senderEmail,
                    ],
                    'to' => [
                        [
                            'email' => $toEmail,
                            'name' => $toName,
                        ],
                    ],
                    'subject' => $subject,
                    'htmlContent' => $htmlContent,
                ],
            ]);

            if ($response->getStatusCode() === 201) {
                $this->logger->info('Email envoyé avec succès', [
                    'to' => $toEmail,
                    'subject' => $subject,
                ]);
                return true;
            }

            $this->logger->error('Échec de l\'envoi de l\'email', [
                'to' => $toEmail,
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email', [
                'to' => $toEmail,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
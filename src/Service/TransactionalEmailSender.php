<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class TransactionalEmailSender
{
    private $apiKey;
    private $client;
    private $logger;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->apiKey = $params->get('brevo_transactional_api_key');
        $this->client = HttpClient::create();
        $this->logger = $logger;
    }

    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlContent,
        string $senderEmail = 'no-reply@eventora.com',
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
                $this->logger->info('Transactional email sent successfully', [
                    'to' => $toEmail,
                    'subject' => $subject,
                ]);
                return true;
            }

            $this->logger->error('Failed to send transactional email', [
                'to' => $toEmail,
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error sending transactional email', [
                'to' => $toEmail,
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendBatchEmails(
        array $recipients,
        string $subject,
        string $htmlContent,
        string $senderEmail = 'eventoraeventora@gmail.com',
        string $senderName = 'Eventora'
    ): bool {
        try {
            $requestData = [
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
                    'to' => $recipients,
                    'subject' => $subject,
                    'htmlContent' => $htmlContent,
                ],
            ];
    
            // Log the request
            $this->logger->info('Sending batch email request', [
                'recipients' => array_column($recipients, 'email'),
                'request' => $requestData,
            ]);
    
            $response = $this->client->request('POST', 'https://api.brevo.com/v3/smtp/email', $requestData);
    
            if ($response->getStatusCode() === 201) {
                $this->logger->info('Batch transactional email sent successfully', [
                    'recipients' => array_column($recipients, 'email'),
                    'subject' => $subject,
                ]);
                return true;
            }
    
            $this->logger->error('Failed to send batch transactional email', [
                'recipients' => array_column($recipients, 'email'),
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error sending batch transactional email', [
                'recipients' => array_column($recipients, 'email'),
                'exception' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
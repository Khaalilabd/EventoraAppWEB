<?php

namespace App\Service;

use SendinBlue\Client\Api\TransactionalSMSApi;
use SendinBlue\Client\Configuration;
use GuzzleHttp\Client as GuzzleClient;

class BrevoSmsSender
{
    private $smsApi;
    private $sender;

    public function __construct(string $apiKey, string $sender)
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $apiKey);
        $this->smsApi = new TransactionalSMSApi(new GuzzleClient(), $config);
        $this->sender = $sender;
    }

    public function sendSms(string $to, string $content): bool
    {
        try {
            $this->smsApi->sendTransacSms([
                'sender' => $this->sender,
                'recipient' => $to,
                'content' => $content,
            ]);
            return true;
        } catch (\Exception $e) {
            // Log l'erreur si besoin
            return false;
        }
    }
} 
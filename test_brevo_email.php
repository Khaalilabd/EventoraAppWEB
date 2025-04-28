<?php
require 'vendor/autoload.php';
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use GuzzleHttp\Client as GuzzleClient;

$config = Configuration::getDefaultConfiguration()->setApiKey('api-key', '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');
$apiInstance = new TransactionalEmailsApi(new GuzzleClient(), $config);

$emailData = new \SendinBlue\Client\Model\SendSmtpEmail([
    'sender' => ['name' => 'Eventora', 'email' => 'rayen.isetch@gmail.com'],
    'to' => [['email' => 'ksaier.2003@gmail.com', 'name' => 'Test User']],
    'templateId' => 3, // Replace with your actual template ID
    'params' => [
        'name' => 'Test User',
        'date' => '26/04/2025',
        'packName' => 'Test Pack'
    ]
]);

try {
    $result = $apiInstance->sendTransacEmail($emailData);
    echo "Email sent successfully: " . json_encode($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
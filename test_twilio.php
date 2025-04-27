<?php
require 'vendor/autoload.php';
use Twilio\Rest\Client;

$sid = 'ACc57c0b58eff936708b3208d34fd03469';
$token = 'c041aac0a4b6c54d0280b74c416f2f89';
$client = new Client($sid, $token);

try {
    $message = $client->messages->create(
        '+21651863242', // Replace with a valid Tunisian number for testing
        [
            'from' => '+12513125202',
            'body' => 'Test SMS from Twilio'
        ]
    );
    echo "SMS sent! SID: " . $message->sid;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
<?php
require 'vendor/autoload.php';
use Twilio\Rest\Client;

$sid = 'AC1fd9324f60b212680b89ac448afb774f';
$token = '3f2b62816fb8c8848b60b0555a6d5030';
$client = new Client($sid, $token);

try {
    $message = $client->messages->create(
        '+21651863242', // Replace with a valid Tunisian number for testing
        [
            'from' => '+17156189464',
            'body' => 'Test SMS from Twilio'
        ]
    );
    echo "SMS sent! SID: " . $message->sid;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}


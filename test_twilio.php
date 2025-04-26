<?php
require 'vendor/autoload.php';
use Twilio\Rest\Client;

$sid = 'AC0d082c97cc7b802c969a1c2d3f79c172';
$token = '98ba77adf85b8670d1c18ee8c507cee2';
$client = new Client($sid, $token);

try {
    $message = $client->messages->create(
        '+21651863242', // Replace with a valid Tunisian number for testing
        [
            'from' => '+13198951977',
            'body' => 'Test SMS from Twilio'
        ]
    );
    echo "SMS sent! SID: " . $message->sid;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
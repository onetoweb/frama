<?php

require 'vendor/autoload.php';

session_start();

use Onetoweb\Frama\Client;
use Onetoweb\Frama\Token;

// client parameters
$username = 'username';
$password = 'password';
$testModus = true;

// setup client
$client = new Client($username, $password, $testModus);

// set token callback to store token
$client->setUpdateTokenCallback(function(Token $token) {
    
    $_SESSION['token'] = [
        'token' => $token->getToken(),
        'expires' => $token->getExpires(),
    ];
    
});

// load token from storage
if (isset($_SESSION['token'])) {
    
    $token = new Token(
        $_SESSION['token']['token'],
        $_SESSION['token']['expires']
    );
    
    $client->setToken($token);
}


// create shipment
$shipment = $client->createShipment([
    'reference' => 'test-zending',
    'carrierId' => 2,
    'barcode' => 'generatedbarcode',
    'trackTraceEmail' => null,
    'deliveryEmail' => null,
    'sendLabelAsEmail' => false,
    'address' => [
        [
            'countryCode' => 'NL',
            'company' => 'Frama ONTVANGER',
            'contact' => 'Jan van den Berg',
            'street' => 'Avelingen-West',
            'housenumber' => '9',
            'housenumberSuffix' => '',
            'zipCode' => '4202 MS',
            'city' => 'Gorinchem',
            'addressTypeId' => 1
        ], [
            'countryCode' => 'NL',
            'company' => 'Frama VERZENDER',
            'contact' => 'Jan Jansen',
            'street' => 'Avelingen-West',
            'housenumber' => '5',
            'housenumberSuffix' => '',
            'zipCode' => '4202 MS',
            'city' => 'Gorinchem',
            'addressTypeId' => 2
        ]
    ],
    'properties' => [
        'mailBox' => false,
        'weightInGrams' => '250',
        'signature' => true,
        'assurance' => null,
        'statedAddress' => false,
        'outputId' => 1
    ],
    'costCenter' => null,
    'link' => null,
    'customs' => null
]);


// get label
$shipmentId = 1;
$label = $client->getLabel($shipmentId);

// save file
$filename = '/path/to/filename.pdf';
file_put_contents($filename, $label);


// get labels
$labels = $client->getLabels();

// save file
$filename = '/path/to/filename.pdf';
file_put_contents($filename, $labels);


// get labels all types
$labels = $client->getLabelsAllTypes();

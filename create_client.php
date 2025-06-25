<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

// 1. Read incoming data (form-encoded or raw JSON)
$contentType = \$_SERVER['CONTENT_TYPE'] ?? '';
\$body = stripos(\$contentType, 'application/json') !== false
    ? json_decode(file_get_contents('php://input'), true)
    : \$_POST;

\$first = \$body['s_first_name'] ?? null;
\$last  = \$body['s_last_name']  ?? null;
\$email = \$body['s_email']      ?? null;

if (!\$first || !\$last || !\$email) {
    header('Content-Type: application/json', true, 422);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Missing s_first_name, s_last_name or s_email.'
    ]);
    exit;
}

// 2. Pull in your env vars
\$apiKey = getenv('WELLNESS_API_KEY');
\$bid    = getenv('WL_BUSINESS_ID');

// 3. Point Guzzle at the US production cluster per SDK docs:
//    “Our two production server clusters are us.wellnessliving.com (North Virginia, USA) and au.wellnessliving.com (Australia).”
//     [oai_citation:0‡wellnessliving.com](https://www.wellnessliving.com/developer-portal/getting-started/installing-the-sdk/installing-the-sdk/)
\$client = new Client([
    'base_uri' => 'https://us.wellnessliving.com/v1/',
    'timeout'  => 10,   // bail after 10s
]);

try {
    // 4. POST to /v1/businesses/{businessId}/clients
    \$resp = \$client->post("businesses/{\$bid}/clients", [
        'headers' => [
            'Authorization' => "Bearer {\$apiKey}",
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'firstName' => \$first,
            'lastName'  => \$last,
            'email'     => \$email,
        ],
    ]);

    \$data = json_decode(\$resp->getBody(), true);
    header('Content-Type: application/json', true, 201);
    echo json_encode(['status' => 'success', 'data' => \$data]);

} catch (\GuzzleHttp\Exception\RequestException \$e) {
    \$status = \$e->hasResponse()
        ? \$e->getResponse()->getStatusCode()
        : 500;
    \$body = \$e->hasResponse()
        ? (string)\$e->getResponse()->getBody()
        : \$e->getMessage();

    header('Content-Type: application/json', true, \$status);
    echo \$body;
    exit;
}

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

// 3. Create Guzzle client, force IPv4 & Google DNS
\$client = new Client([
    // Developer Portal “Getting Started” uses v1 base URI:
    // https://www.wellnessliving.com/developer-portal/getting-started/introduction/
    'base_uri' => 'https://api.wellnessliving.com/v1',
    'curl'     => [
        CURLOPT_IPRESOLVE   => CURL_IPRESOLVE_V4,
        CURLOPT_DNS_SERVERS => '8.8.8.8',
    ],
    'timeout'  => 10,
]);

try {
    // POST /businesses/{businessId}/clients per the WL SDK examples:
    // https://github.com/wellnessliving/wl-sdk
    \$resp = \$client->post("businesses/\$bid/clients", [
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
    echo json_encode(['status'=>'success','data'=>\$data]);

} catch (\\GuzzleHttp\\Exception\\RequestException \$e) {
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

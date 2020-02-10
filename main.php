<?php
require_once __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$publicIP = getPublicIP();
$currentDNSRecord = getCurrentDNSRecord();

if ($publicIP['origin'] === $currentDNSRecord['result'][0]['content']) {
    return;
}

$res = updateDNSRecord($publicIP['origin'], $currentDNSRecord['result'][0]['id']);

function getPublicIP() {
    $client = new Client();
    
    $res = $client->request('GET', 'http://httpbin.org/ip');

    return json_decode($res->getBody()->getContents(), true);
}

function getCurrentDNSRecord() {
    $client = new Client();

    $uri = getenv('CLOUDFLARE_API_URI') . '/zones/' . getenv('CLOUDFLARE_ZONE_ID') . '/dns_records?type=A&name=' . getenv('CLOUDFLARE_DNS_NAME') . '&order=type&direction=desc&match=all';
    $options = [
        'headers' => [
	    'Content-Type'  => 'application/json',
            'X-Auth-Email'  => getenv('CLOUDFLARE_EMAIL'),
            'Authorization' => 'Bearer ' . getenv('CLOUDFLARE_API_KEY'),
	]
    ];

    $res = $client->request('GET', $uri, $options);

    return json_decode($res->getBody()->getContents(), true);
}

function updateDNSRecord($publicIP, $id) {
    $client = new Client();

    $uri = getenv('CLOUDFLARE_API_URI') . '/zones/' . getenv('CLOUDFLARE_ZONE_ID') . '/dns_records/' . $id;
    $options = [
        'headers' => [
	    'Content-Type'  => 'application/json',
            'X-Auth-Email'  => getenv('CLOUDFLARE_EMAIL'),
            'Authorization' => 'Bearer ' . getenv('CLOUDFLARE_API_KEY'),
        ],
        'json' => [
            'type'    => 'A',
            'name'    => getenv('CLOUDFLARE_DNS_NAME'),
	    'content' => $publicIP,
	    'ttl'     => 1,
	    'proxied' => true,
        ]
    ];

    $res = $client->request('PUT', $uri, $options);

    return json_decode($res->getBody()->getContents(), true);
}

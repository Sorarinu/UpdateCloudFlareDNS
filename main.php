<?php

require_once __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$records = [
    'sorarinu.dev'   => true,
    'tv.sorarinu.dev'   => true,
    'home.sorarinu.dev' => false,
    'krishna-api.sorarinu.dev' => true,
    'ldap.sorarinu.dev' => false,
    'wiki.sorarinu.dev' => true,
    'grafana.sorarinu.dev' => true,
    'prometheus.sorarinu.dev' => true,
];

$publicIP = getPublicIP();
MyLog::$log->debug('Get Public IP', $publicIP);

foreach ($records as $record => $isProxy) {
    $currentDNSRecord = getCurrentDNSRecord($record);
    MyLog::$log->debug('Get Current DNS Record for ' . $record, $currentDNSRecord);
    
    if ($publicIP['origin'] === $currentDNSRecord['result'][0]['content']) {
        MyLog::$log->debug('No Updates for ' . $record);
        continue;
    }

    MyLog::$log->debug('Update Record : ' . $record);
    
    $res = updateDNSRecord($publicIP['origin'], $currentDNSRecord['result'][0]['id'], $record, $isProxy);
}

function getPublicIP() {
    $client = new Client();
    
    $res = $client->request('GET', 'http://httpbin.org/ip');

    return json_decode($res->getBody()->getContents(), true);
}

function getCurrentDNSRecord($record) {
    $client = new Client();

    $uri = getenv('CLOUDFLARE_API_URI') . '/zones/' . getenv('CLOUDFLARE_ZONE_ID') . '/dns_records?type=A&name=' . $record . '&order=type&direction=desc&match=all';
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

function updateDNSRecord($publicIP, $id, $record, $isProxy) {
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
            'name'    => $record,
	    'content' => $publicIP,
	    'ttl'     => 1,
	    'proxied' => $isProxy,
        ]
    ];

    $res = $client->request('PUT', $uri, $options);

    return json_decode($res->getBody()->getContents(), true);
}

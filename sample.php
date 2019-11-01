<?php

require_once( __DIR__ . '/vendor/autoload.php');

use Goutte\Client;

// Cookie 取得目的のアクセス
$url =   "https://mixway.ekispert.net/ports/";
$client = new Client();
$client->request('GET', $url);



$params = http_build_query([
    'lat' => '35.65840757',
    'lng' => '139.70093119',
    'count' => 10,
    'ofset' => 10,
]);
$getPortsUrl = 'https://mixway.ekispert.net/api/custom/ports?' .$params;


$client->setHeader('referer', 'https://mixway.ekispert.net/ports/');
$client->setHeader('x-requested-with', 'XMLHttpRequest');
$client->setHeader('user-agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36');
$client->request('GET', $getPortsUrl);

$portsInfo = json_decode($client->getResponse()->getContent(), true);

print_r($portsInfo);

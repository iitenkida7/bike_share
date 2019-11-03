<?php

require_once( __DIR__ . '/../vendor/autoload.php');
require_once( __DIR__ . '/../getPortsFromGeo.php');
use Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__ . '/..');
$dotenv->load();

$requestHeaders = getallheaders();
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

// リクエスト検証
$lineChannelSecret = getenv('LINE_CHANNEL_SECRET');
$signature =  base64_encode(hash_hmac('sha256', $requestBody, $lineChannelSecret, true));
if($signature !== $requestHeaders['X-Line-Signature']){
    header( "HTTP/1.1 404 Not Found" ) ;
    exit;
}
// Lineからのリクエストにmessage が含まれるか
if(empty($requestData['events'][0]['message']['type'])){
    header( "HTTP/1.1 415 Unsupported Media Type" ) ;
    exit;
}
// メッセージタイプは位置情か
if ( $requestData['events'][0]['message']['type'] !== 'location'){
    header( "HTTP/1.1 415 Unsupported Media Type" ) ;
    exit;
}

$locationData = $requestData['events'][0]['message'];



$ports  = (new getPortsFromGeo)->setPoint($locationData['latitude'],$locationData['longitude'])->getPorts();
file_put_contents('php://stdout', print_r($ports,true));

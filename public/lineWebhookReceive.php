<?php

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../getPortsFromGeo.php');
require_once(__DIR__ . '/../getPorts.php');
require_once(__DIR__ . '/../reserveBike.php');

use Dotenv\Dotenv;

$dotenv = Dotenv::create(__DIR__ . '/..');
$dotenv->load();

function replayMessage($replyToken, $msg):bool
{
    $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LINE_CHANNEL_ACCESS_TOKEN'));
    $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LINE_CHANNEL_SECRET')]);
    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($msg);
    $response = $bot->replyMessage($replyToken, $textMessageBuilder);

    // debug
    //file_put_contents('php://stdout', $response->getHTTPStatus() . ' ' . $response->getRawBody());
    if ($response->getHTTPStatus() == 200){
        return true;
    }
        return false;
}

$requestHeaders = getallheaders();
$requestBody = file_get_contents('php://input');
//file_put_contents('php://stdout', print_r(json_decode($requestBody, true),true));
$event = json_decode($requestBody, true)['events'][0];
$lineUserId = json_decode($requestBody, true)['events'][0]['source']['userId'];


// リクエスト検証
$lineChannelSecret = getenv('LINE_CHANNEL_SECRET');
$signature =  base64_encode(hash_hmac('sha256', $requestBody, $lineChannelSecret, true));
if($signature !== $requestHeaders['X-Line-Signature']){
    header( "HTTP/1.1 404 Not Found" ) ;
    exit;
}
// Lineからのリクエストにmessage が含まれるか
if(empty($event['message']['type'])){
    header( "HTTP/1.1 415 Unsupported Media Type" ) ;
    exit;
}
// メッセージタイプは位置情か
if ( $event['message']['type'] !== 'location'){
    header( "HTTP/1.1 415 Unsupported Media Type" ) ;
    exit;
}

$ports  = (new getPortsFromGeo)->setPoint($event['message']['latitude'],$event['message']['longitude'])->getPorts();
file_put_contents('php://stdout', print_r($ports,true));

foreach ($ports['ports'] as $port){
    $requestPorts[$port['code']] =  $port['name'];
}

$msg="";
$status = (new GetPorts)->status($requestPorts);

foreach ($status as $item) {
    $msg .= "\n[{$item['stockNum']}]{$item['portName']}";
}
$msg .= "\n";

$reserveBike = (new ReserveBike)->reserveNearbyBike($status);

$msg .= print_r($reserveBike, true);

replayMessage($event['replyToken'], $msg);

// 位置情報のPOST
file_put_contents('php://stdout', print_r($reserveBike,true));
foreach ($ports['ports'] as  $port){
    if ($port['code']  == $reserveBike['bikeInfo']['portCode']){
        $result = $port;
    }  
}
file_put_contents('php://stdout', print_r($result,true));

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LINE_CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LINE_CHANNEL_SECRET')]);

$locationMessageBuilder = new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder("位置情報", $result['name'], $result['lat'], $result['lng']);
$response = $bot->pushMessage($lineUserId,  $locationMessageBuilder);
file_put_contents('php://stdout', $response->getHTTPStatus() . ' ' . $response->getRawBody());

<?php

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/getPortsFromGeo.php');
require_once(__DIR__ . '/getPorts.php');
require_once(__DIR__ . '/reserveBike.php');

use Dotenv\Dotenv;


class reserveManager
{

    private $ports;
    private $status;
    private $reserveBike;
    private $bot; //botインスタンス

    function __construct()
    {
        $dotenv = Dotenv::create(__DIR__);
        $dotenv->load();
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('LINE_CHANNEL_ACCESS_TOKEN'));
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('LINE_CHANNEL_SECRET')]);
    }

    public function lineReceiver($event)
    {
        $this->setPortsFromGeo($event['message']['latitude'],$event['message']['longitude']);
        print_r($this->ports);

        // ポートのステータス確認を行う
        foreach ($this->ports as $port){
            $requestPorts[$port['code']] =  $port['name'];
        }
        $this->status = (new GetPorts)->status($requestPorts);
        file_put_contents('php://stdout', print_r($this->status,true));

        // 予約処理
        $this->reserveBike = (new ReserveBike)->reserveNearbyBike($this->status);
        file_put_contents('php://stdout', print_r($this->reserveBike,true));

        // Line送信  
        $this->replyMessage($event['replyToken'],$this->buildMessage());
        $this->pushLocation($event['source']['userId'],$this->searchPortByCode($this->reserveBike['bikeInfo']['portCode']));
    }

    private function buildMessage() :string
    {
        $msg="";
        foreach ($this->status as $item) {
            $msg .= "\n[{$item['stockNum']}]{$item['portName']}";
        }
        $msg .= "\n";
        $msg .= print_r($this->reserveBike, true);
        return $msg;
    }

    private function setPortsFromGeo($lat,$lng) :array
    {
        $this->ports = (new getPortsFromGeo)->setPoint($lat,$lng)->getPorts();
        return $this->ports;
    }

    private function replyMessage($replyToken,$msg) :bool
    {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($msg);
        $response = $this->bot->replyMessage($replyToken, $textMessageBuilder);
        file_put_contents('php://stdout', $response->getHTTPStatus() . ' ' . $response->getRawBody());
        if ($response->getHTTPStatus() == 200){
            return true;
        }
        return false;
    }

    private function pushLocation($userId,$port) :bool
    {
        $locationMessageBuilder = new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($port['name'], $port['name'], $port['lat'], $port['lng']);
        $response = $this->bot->pushMessage($userId,  $locationMessageBuilder);
        file_put_contents('php://stdout', $response->getHTTPStatus() . ' ' . $response->getRawBody());
        if ($response->getHTTPStatus() == 200){
            return true;
        }
        return false;
    }

    private function searchPortByCode($code) :array
    { 
        foreach ($this->ports as  $port){
            if ($port['code']  == $code) {
                return  $port;
            }  
        }
        return [];
    }
}

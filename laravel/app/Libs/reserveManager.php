<?php
namespace App\Libs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class ReserveManager
{
    private $ports;
    private $status;
    private $reserveBike;
    private $bot; //botインスタンス

    function __construct()
    {
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(Config::get('bike_share.line.channelAccessToken'));
        $this->bot = new \LINE\LINEBot($httpClient, ['channelSecret' => Config::get('bike_share.line.channelSecret')]);
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
        Log::debug(print_r($this->status,true));

        // 予約処理
        $this->reserveBike = (new ReserveBike)->reserveNearbyBike($this->status);
        Log::debug(print_r($this->reserveBike,true));

        // Line送信  
        $this->replyMessage($event['replyToken'],$this->buildMessage());
        $this->pushLocation($event['source']['userId'],$this->searchPortByCode($this->reserveBike['bikeInfo']['portCode']));
    }

    public function lineMessageDispatcher($event)
    {
        if(preg_match('/cancel/', $event['message']['text'])){
            if((new ReserveBike)->reserveCancel()){
                $this->replyMessage($event['replyToken'],"自転車の予約をキャンセルしました");
            }
        }elseif(preg_match('/akiba/', $event['message']['text'])){
                // TODO LibからController呼び出すのはご法度な気がするので後で直す
                app('App\Http\Controllers\ReserveController')->index();
        }else{
                $this->replyMessage($event['replyToken'],"位置情報をくれれば自転車予約するよ。cancel したい場合は、cancel と入力してね。");
        }
    }

    public function specifiedReserve($ports)
    {
            $this->status = (new GetPorts)->status($ports);

            // 予約処理
            $this->reserveBike = (new ReserveBike)->reserveNearbyBike($this->status);
            Log::debug(print_r($this->reserveBike,true));

            // Line送信
            $this->pushMessage(Config::get('bike_share.line.userId'),$this->buildMessage());
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
        Log::debug($response->getHTTPStatus() . ' ' . $response->getRawBody());
        if ($response->getHTTPStatus() == 200){
            return true;
        }
        return false;
    }

    private function pushMessage($lineUserId,$msg) :bool
    {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($msg);
        $response = $this->bot->pushMessage($lineUserId, $textMessageBuilder);
        Log::debug($response->getHTTPStatus() . ' ' . $response->getRawBody());
        if ($response->getHTTPStatus() == 200){
            return true;
        }
        return false;
    }
    private function pushLocation($userId,$port) :bool
    {
        $locationMessageBuilder = new \LINE\LINEBot\MessageBuilder\LocationMessageBuilder($port['name'], $port['name'], $port['lat'], $port['lng']);
        $response = $this->bot->pushMessage($userId,  $locationMessageBuilder);
        Log::debug($response->getHTTPStatus() . ' ' . $response->getRawBody());
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

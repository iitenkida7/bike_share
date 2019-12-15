<?php
namespace App\Libs;

use Illuminate\Support\Facades\Log;
use App\Libs\lineMessage;
use App\Libs\RegistUser;

class ReserveManager
{
    private $event;
    private $ports;
    private $status;
    private $reserveBike;
    private $chiyokuruId;
    private $chiyokuruPassword;

    public function __construct($event)
    {
        $this->event = $event;

        $ret = (new RegistUser)->getChiyokuruUser($this->event['source']['userId']);
        $this->chiyokuruId =  $ret['chiyokuruId'];
        $this->chiyokuruPassword = $ret['chiyokuruPassword'];
    }

    public function lineReceiver()
    {
        $this->setPortsFromGeo($this->event['message']['latitude'],$this->event['message']['longitude']);
 
        // ポートのステータス確認を行う
        foreach ($this->ports as $port){
            $requestPorts[$port['code']] =  $port['name'];
        }
        $this->status = (new GetPorts)->status($requestPorts);
        Log::debug(print_r($this->status,true));

        // 予約処理
        $this->reserveBike = (new ReserveBike($this->chiyokuruId, $this->chiyokuruPassword))->reserveNearbyBike($this->status);
        Log::debug(print_r($this->reserveBike,true));

        // 予約できたポート情報の詳細を引き出す。（このあと座標を利用する）
        $portInfo = $this->searchPortByCode($this->reserveBike['bikeInfo']['portCode']);

        // Line送信  
        (new LineMessage())->setReplayToken($this->event['replyToken'])->buildMessage($this->message())->post();
        (new LineMessage())->setUserId($this->event['source']['userId'])->buildLocation($portInfo['name'], $portInfo['lat'], $portInfo['lng'])->post();;
    }

    public function lineMessageDispatcher()
    {   
        if(preg_match('/cancel/', $this->event['message']['text'])){
            if((new ReserveBike($this->chiyokuruId, $this->chiyokuruPassword))->reserveCancel()){
                (new LineMessage())->setReplayToken($this->event['replyToken'])->buildMessage("自転車の予約をキャンセルしました")->post();
            }
        }elseif(preg_match('/akiba/', $this->event['message']['text'])){
                $this->specifiedReserve($this->akibaPorts());
                (new LineMessage())->setReplayToken($this->event['replyToken'])->buildMessage($this->message())->post();
        }else{
                (new LineMessage())->setReplayToken($this->event['replyToken'])->buildMessage("位置情報をくれれば自転車予約するよ。cancel したい場合は、cancel と入力してね。")->post();
        }
    }

    private function specifiedReserve($ports)
    {
            $this->status = (new GetPorts)->status($ports);
            $this->reserveBike = (new ReserveBike($this->chiyokuruId, $this->chiyokuruPassword))->reserveNearbyBike($this->status);
            Log::debug(print_r($this->reserveBike,true));
    }

    private function message() :string
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

    private function searchPortByCode($code) :array
    { 
        foreach ($this->ports as  $port){
            if ($port['code']  == $code) {
                return  $port;
            }  
        }
        return [];
    }

    private function akibaPorts(): array
    {
        return [ 
              '00010302' => 'ヨドバシカメラ前',
              '00010303' => '電気街口（西側交通広場）',
              '00010032' => 'UDX駐輪場前',
              '00010037' => '富士ソフト',
              '00010016' => '秋葉原公園',
        ];
    }
}

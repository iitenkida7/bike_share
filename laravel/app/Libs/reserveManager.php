<?php
namespace App\Libs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Libs\lineMessage;

class ReserveManager
{
    private $ports;
    private $status;
    private $reserveBike;

    public function lineReceiver($event)
    {
        $this->setPortsFromGeo($event['message']['latitude'],$event['message']['longitude']);
 
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
        (new LineMessage())->setReplayToken($event['replyToken'])->buildMessage($this->message())->postMessage();
        (new LineMessage())->setUserId($event['source']['userId'])->buildLocation($this->searchPortByCode($this->reserveBike['bikeInfo']['portCode']));
    }

    public function lineMessageDispatcher($event)
    {
        if(preg_match('/cancel/', $event['message']['text'])){
            if((new ReserveBike)->reserveCancel()){
                (new LineMessage())->setReplayToken($event['replyToken'])->buildMessage("自転車の予約をキャンセルしました")->postMessage();
            }
        }elseif(preg_match('/akiba/', $event['message']['text'])){
                // TODO LibからController呼び出すのはご法度な気がするので後で直す
                app('App\Http\Controllers\ReserveController')->index();
        }else{
                (new LineMessage())->setReplayToken($event['replyToken'])->buildMessage("位置情報をくれれば自転車予約するよ。cancel したい場合は、cancel と入力してね。")->postMessage();
        }
    }

    public function specifiedReserve($ports)
    {
            $this->status = (new GetPorts)->status($ports);

            // 予約処理
            $this->reserveBike = (new ReserveBike)->reserveNearbyBike($this->status);
            Log::debug(print_r($this->reserveBike,true));

            // Line送信
            // TODO UserID が固定になっているので、複数ユーザー対応のときに治す。
            (new LineMessage())->setUserId(Config::get('bike_share.line.userId'))->buildMessage($this->message())->postMessage();
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
}

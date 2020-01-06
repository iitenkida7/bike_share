<?php
namespace App\Libs;

use Illuminate\Support\Facades\Log;
use App\Libs\lineMessage;
use App\Libs\RegistUser;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use App\BikeStatus;

class ReserveManager
{
    private $event;
    private $ports;
    private $status;
    private $reserveBike;
    private $chiyokuruId;
    private $chiyokuruPassword;
    private $lineUserId;

    public function __construct($event)
    {
        $this->event = $event;

        $ret = (new RegistUser)->getChiyokuruUser($this->event['source']['userId']);
        $this->lineUserId  = $ret['id'];
        $this->chiyokuruId =  $ret['chiyokuruId'];
        $this->chiyokuruPassword = $ret['chiyokuruPassword'];
    }

    public function lineReceiver()
    {
        $this->setPortsFromGeo($this->event['message']['latitude'], $this->event['message']['longitude']);
 
        $portInfo = $this->reserveProcess();

        // Line送信
        (new LineMessage())->setReplayToken($this->event['replyToken'])->postMessage($this->message());
        (new LineMessage())->setUserId($this->event['source']['userId'])->postLocation($portInfo['Name'], $portInfo['GeoPoint']['lati_d'], $portInfo['GeoPoint']['longi_d']);
    }

    public function lineMessageDispatcher()
    {
        if (preg_match('/cancel/', $this->event['message']['text'])) {
            if ((new ReserveBike($this->chiyokuruId, $this->chiyokuruPassword))->reserveCancel()) {
                (new LineMessage())->setReplayToken($this->event['replyToken'])->postMessage("自転車の予約をキャンセルしました");
            }
        } elseif (preg_match('/akiba/', $this->event['message']['text'])) {
            $portInfo  = $this->specifiedReserve($this->akibaPoint());
            (new LineMessage())->setReplayToken($this->event['replyToken'])->postMessage($this->message());
            (new LineMessage())->setUserId($this->event['source']['userId'])->postLocation($portInfo['Name'], $portInfo['GeoPoint']['lati_d'], $portInfo['GeoPoint']['longi_d']);

        } else {
            (new LineMessage())->setReplayToken($this->event['replyToken'])->postMessage("位置情報をくれれば自転車予約するよ。cancel したい場合は、cancel と入力してね。");
        }
    }

    private function reserveProcess()
    {
        // ポートのステータス確認を行う
        foreach ($this->ports as $port) {
            $code = str_replace('DOCOMO.', '', $port['code']);
            $requestPorts[$code] =  $port['Name'];
        }

        // Log::debug(print_r($this->ports,true));
        $this->status = (new GetPorts)->status($requestPorts);
        Log::debug(print_r($this->status, true));

        // 予約処理
        $this->reserveBike = (new ReserveBike($this->chiyokuruId, $this->chiyokuruPassword))->reserveNearbyBike($this->status);
        Log::debug(print_r($this->reserveBike, true));

        $portInfo = $this->searchPortByCode($this->reserveBike['bikeInfo']['portCode']);
        if ($this->reserveBike['reserve'] == 'success') {
            BikeStatus::create([
                'line_user_id' => $this->lineUserId,
                'port_name' => $this->reserveBike['bikeInfo']['portName'],
                'bike_id' => $this->reserveBike['bikeInfo']['BikeName'],
                'bike_passcode' =>  $this->reserveBike['bikeInfo']['PassCode'],
                'point' => new Point($portInfo['GeoPoint']['lati_d'], $portInfo['GeoPoint']['longi_d']),	// (lat, lng)
                ]);
       }

        // おもに座標で利用
        return $portInfo;
    }

    private function specifiedReserve($point)
    {
        $this->setPortsFromGeo($point['latitude'], $point['longitude']);
        $portInfo = $this->reserveProcess();
        return $portInfo;
    }

    private function message() :string
    {
        $msg="";
        $msg .= "==利用可能自転車状況==\n";
        foreach ($this->status as $item) {
            $msg .= "[{$item['stockNum']}台]{$item['portName']}\n";
        }
        $msg .= "\n";
        $msg .= "==予約ステータス==\n";

        $reserveBike = $this->reserveBike;

        $msg .= "ポート名:" . $reserveBike['bikeInfo']['portName'] . "\n" ;
        $msg .= "自転車名:" . $reserveBike['bikeInfo']['BikeName'] . "\n" ;
        $msg .= "パスコード:" . $reserveBike['bikeInfo']['PassCode'] . "\n" ;
        return $msg;
    }

    private function setPortsFromGeo($lat, $lng) :array
    {
        $this->ports = (new getPortsFromGeo)->setPoint($lat, $lng)->getPorts();
        return $this->ports;
    }

    private function searchPortByCode($code) :array
    {
        foreach ($this->ports as  $port) {
            if (str_replace('DOCOMO.', '', $port['code'])  == $code) {
                return  $port;
            }
        }
        return [];
    }

    private function akibaPoint(): array
    {
        return [
            'latitude' => 35.699135490482355,
            'longitude' => 139.77446414530277,
        ];
    }
}

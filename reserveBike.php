<?php
require_once( __DIR__ . '/vendor/autoload.php');

use Illuminate\Support\Arr;
use Dotenv\Dotenv;
use Goutte\Client;

class ReserveBike
{

    function __construct()
    {
        // これは、もとの実行クラスがやるべきかな。
        $dotenv = Dotenv::create(__DIR__);
        $dotenv->load();

        $this->client  = new client;
        $this->memberId = getenv('MEMBER_ID');
        $this->password = getenv('PASSWORD');

        self::getLoginSession();
    }

    private $client;
    private $endpoint  = 'https://tcc.docomo-cycle.jp/cycle/TYO/cs_web_main.php';
    private $sessionId = "";
    private $memberId  = "";
    private $password  = "";
    private $reserved  = [];

    public function reserveNearbyBike($ports) :array
    {
        // 既に予約があったら終了
        if ($this->reserved != []){
            return $this->reserved;
        }

        $faildCnt = 0;
        foreach ($ports as $port){
            // 自転車があるポート以外はスキップ
            if($port['stockNum'] <= 0 ){
                continue;
            }
            $portBikes = $this->portBikes($port);
            // 再度ポートの自転車ストック状況を確認してない場合はスキップ
            if(count($portBikes) <= 0 ){
                continue;
            }

            // 予約を試みる
            foreach ($portBikes as $portBike){
                usleep(500000);
                // 3回トライしてだめだったら諦める
                if ($faildCnt >= 3){
                    return [ 'reserve' => false , 'bikeInfo' => null ];
                }
                if($this->reserveBike($portBike)){
                    return [
                        'reserve' => true,
                        'bikeInfo' => [
                            'portName' => $port['portName'],
                            'BikeName' => $portBike['BikeName'],
                        ]
                    ];
                }else{
                    $faildCnt ++ ;
                }
            }
            return [ 'reserve' => false , 'bikeInfo' => null ];
        }
    }

    private function getLoginSession() :bool
    {
        $login = $this->client->request('POST', $this->endpoint, [
            'EventNo'  => 21401, // ログイン後ページ
            'MemberID' => $this->memberId,
            'Password' => $this->password,
        ]);
        usleep(500000); // ちょっと待たないとうまく進めなかった

        if( ($login->filter('.mpt_inner_left p')->count() > 0)){
            // TODO bikeInfo にちゃんと情報入れる
            $this->reserved =  [ 'reserve' => true  , 'bikeInfo' => null , 'msg' => 'you havbe already reserved' ];
        }
        $this->sessionId = current($login->filter('form > input[name="SessionID"]')->first()->extract('value'));
        if( !$this->sessionId == "" ){
            return false; 
        }
    }

    private function portBikes($port) :array
    {
        $port = $this->client->request('POST', $this->endpoint, [
            'EventNo' => 25701, //バイク一覧
            'SessionID'=> $this->sessionId,
            'UserID'    => 'TYO',
            'MemberID' => $this->memberId,
            'GetInfoNum'=> 20,
            'GetInfoTopNum' => 1,
            'ParkingEntID' => 'TYO',
            'ParkingID' => $port['portId'],
            //    'ParkingLat' => '35.691456',
            //    'ParkingLon'=> '139.762228'
        ]);
        if ( $port->filter('.sp_view form')->count() == 0) {
            return [];
        }
        return $port->filter('.sp_view form')->each(function ($element) {
            $bike = [
                'BikeName'=> $element->filter('a')->text(), 
                'postData' => [
                    'EventNo' => $element->filter('input[name=EventNo]')->attr('value'),
                        'SessionID' => $element->filter('input[name=SessionID]')->attr('value'),
                        'UserID' => $element->filter('input[name=UserID]')->attr('value'),
                        'MemberID' => $element->filter('input[name=MemberID]')->attr('value'),
                        'CenterLat' => $element->filter('input[name=CenterLat]')->attr('value'),
                        'CenterLon' => $element->filter('input[name=CenterLon]')->attr('value'),
                        'CycLat' => $element->filter('input[name=CycLat]')->attr('value'),
                        'CycLon' => $element->filter('input[name=CycLon]')->attr('value'),
                        'CycleID' => $element->filter('input[name=CycleID]')->attr('value'),
                        'AttachID' => $element->filter('input[name=AttachID]')->attr('value'),
                        'CycleTypeNo' => $element->filter('input[name=CycleTypeNo]')->attr('value'),
                        'CycleEntID' => $element->filter('input[name=CycleEntID]')->attr('value'),
                    ]
                ];
            return  $bike;
        });
    }

    private function reserveBike($bike) :bool
    {
        $reserve = $this->client->request('POST', $this->endpoint, $bike['postData']);
        return preg_match('/Complete/', $reserve->filter('.tittle h1')->text());
    }
}

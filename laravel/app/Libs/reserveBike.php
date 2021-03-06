<?php
namespace App\Libs;

use Goutte\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ReserveBike
{
    public function __construct($memberId, $password)
    {
        $this->client  = new client;
        $this->memberId = $memberId;
        $this->password = $password;
        self::getLoginSession();
    }

    private $client;
    private $endpoint  = 'https://tcc.docomo-cycle.jp/cycle/TYO/cs_web_main.php';
    private $sessionId = '';
    private $memberId  = '';
    private $password  = '';
    private $reserved  = [];
    public $isLogin    = false;

    public function reserveNearbyBike($ports) :array
    {
        // 既に予約があったら終了
        if ($this->reserved != []) {
            return $this->reserved;
        }

        foreach ($ports as $port) {
            // 自転車があるポート以外はスキップ
            if ($port['stockNum'] <= 0) {
                continue;
            }
            $portBikes = $this->portBikes($port);
            // 再度ポートの自転車ストック状況を確認してない場合はスキップ
            if (count($portBikes) <= 0) {
                continue;
            }

            // 予約を試みる 故障車を連続で引かないようrandomで。
            $faildCnt = 0;
            while ($faildCnt <= 3) {
                usleep(50000);
                $portBike = Arr::random($portBikes);
                if ($this->reserveBike($portBike)) {
                    return [
                        'reserve' => true,
                        'bikeInfo' => [
                            'portCode' => $port['portId'],
                            'portName' => $port['portName'],
                            'BikeName' => $portBike['BikeName'],
                            'PassCode' => $this->getBikePassCode(),
                        ]
                    ];
                }
            }  
        }
        // 諦める
        return [ 'reserve' => false , 'bikeInfo' => null ];
    }

    private function getBikePassCode(): string
    {
        $login = $this->client->request('POST', $this->endpoint, [
            'EventNo'  => 21401, // ログイン後ページ
            'MemberID' => $this->memberId,
            'Password' => $this->password,
        ]);

        usleep(300000); // ちょっと待たないとうまく進めなかった
        if ($login->filter('.mpt_inner_left p')->count() > 0) {
            preg_match("/use:(.*)$/", $login->filter('.usr_stat')->text(), $passCode);
            return $passCode[1];
        }
        return false;
    }

    private function getLoginSession() :bool
    {
        $login = $this->client->request('POST', $this->endpoint, [
            'EventNo'  => 21401, // ログイン後ページ
            'MemberID' => $this->memberId,
            'Password' => $this->password,
        ]);
        //  usleep(500000); // ちょっと待たないとうまく進めなかった

        if ($login->filter('.mpt_inner_left p')->count() > 0) {
            preg_match("/use:(.*)$/", $login->filter('.usr_stat')->text(), $passCode);
            preg_match("/Reserved:(.*) 開/", $login->filter('.usr_stat')->text(), $bikeName);
            $this->reserved =  [ 'reserve' => 'already reserved',
                'bikeInfo' => [
                    'portCode' => 'unknown', //ポート情報の記載が無いためわからず。。 何かAPI叩く必要がありそう。
                    'portName' => 'unknown', //ポート情報の記載が無いためわからず。。 何かAPI叩く必要がありそう。
                    'BikeName' => $bikeName[1],
                    'PassCode' => $passCode[1],
                ] ,
                'msg' => 'you havbe already reserved' ];
        }
        // Log::debug($login->html()); // ログインできるか見るときに。
        $this->sessionId = current($login->filter('form > input[name="SessionID"]')->first()->extract(['value']));
        if ($this->sessionId == "") {
            return false;
        }
        $this->isLogin = true;
        return true;
    }

    public function reserveCancel(): bool
    {
        $cancel = $this->client->request('POST', $this->endpoint, [
            'EventNo' => 27901, //キャンセル
            'SessionID'=> $this->sessionId,
            'UserID'    => 'TYO',
            'MemberID' => $this->memberId,
        ]);
        if ($cancel->filter('.mpt_inner_left p')->count() == 0) {
            return true;
        }
        return false;
    }

    private function portBikes($port) :array
    {
        $portInfo = $this->client->request('POST', $this->endpoint, [
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
        if ($portInfo->filter('.sp_view form')->count() == 0) {
            return [];
        }
        return $portInfo->filter('.sp_view form')->each(function ($element) {
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

<?php

namespace App\Libs;

use App\LineUser;
use App\Libs\lineMessage;
use App\Libs\ReserveBike;

class RegistUser
{
    function __construct()
    {

    }

    public function isUser($lineId): bool
    {
        return LineUser::where('line_id', $lineId)
                ->whereNotNull('chiyokuru_id')
                ->whereNotNull('chiyokuru_password')
                ->exists();
    }

    // パスワードのパースさせるのは、責務が大きいと思うよ。
    public function registAnnounce($event): bool
    {
        //LineIDはしているけど、ちよくるパスワード知らないとき。
        if(! $this->isUser($event['source']['userId'])){
            list($chiyokuruId, $chiyokuruPassword) = array_pad(explode("\n", $event['message']['text']), 2, null);
            
            if((new ReserveBike($chiyokuruId, $chiyokuruPassword))->isLogin){
                LineUser::insert([
                        'line_id' => $event['source']['userId'],
                        'chiyokuru_id' => encrypt($chiyokuruId),
                        'chiyokuru_password' => encrypt($chiyokuruPassword)]);
                return (new LineMessage())->setUserId( $event['source']['userId'])->buildMessage("登録成功＼(^o^)／")->post();
            }
        }
        return (new LineMessage())->setReplayToken($event['replyToken'])->buildMessage("ちよくるの ID/PASS の登録をお願いします。\n１行目ID\n２行目PASS")->post();
    }

    public function getChiyokuruUser($lineId): array
    {
        $ret = LineUser::where('line_id', $lineId)->first();
        return [
            'chiyokuruId' => decrypt($ret->chiyokuru_id),
            'chiyokuruPassword' =>  decrypt($ret->chiyokuru_password),
        ];
    }

}
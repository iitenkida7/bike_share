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

    private function isTemporary($lineId): bool
    {
        return LineUser::where('line_id', $lineId)
        ->whereNull('chiyokuru_id')
        ->whereNull('chiyokuru_password')
        ->exists();
    }

    // パスワードのパースさせるのは、責務が大きいと思うよ。
    public function registAnnounce($event): bool
    {
        //LineIDはしているけど、ちよくるパスワード知らないとき。
        if($this->isTemporary($event['source']['userId'])){
            $ret = explode("\n", $event['message']['text']);
            
            if(new ReserveBike($ret[0],$ret[1])){
                LineUser::where('line_id',  $event['source']['userId'])
                    ->update([
                        'chiyokuru_id' => $ret[0],
                        'chiyokuru_password' => $ret[1]]);
                return (new LineMessage())->setUserId( $event['source']['userId'])->buildMessage("登録成功＼(^o^)／")->post();
            }else{
                return (new LineMessage())->setReplayToken($event['replyToken'])->buildMessage("ちよくるの ID/PASS の登録をお願いします。\n１行目ID\n２行目PASS")->post();
            }
        }            
          LineUser::insert(['line_id' => $event['source']['userId']]);
          return (new LineMessage())->setReplayToken($event['replyToken'])->buildMessage("ちよくるの ID/PASS の登録をお願いします。\n１行目ID\n２行目PASS")->post();
    }

    public function getChiyokuruUser($lineId): object
    {
        return LineUser::where('line_id', $lineId)->first();
    }

}
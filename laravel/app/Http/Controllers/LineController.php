<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Libs\reserveManager;
use App\Libs\RegistUser;


class LineController extends Controller
{
    public function index(Request $request)
    {
        $requestHeaders = getallheaders();
        Log::debug($request->getContent());
        // リクエスト検証
        $signature =  base64_encode(hash_hmac('sha256', $request->getContent(), Config::get('bike_share.line.channelSecret'), true));
        if($signature !== $requestHeaders['X-Line-Signature']){
            header( "HTTP/1.1 404 Not Found" ) ;
            exit;
        }

        foreach (json_decode($request->getContent(), true)['events'] as $event){
            if((new RegistUser)->isUser($event['source']['userId'])){
                Log::debug("DB登録あり");
                // Lineからのリクエストにmessage が含まれるか
                if(empty($event['message']['type'])){
                    header( "HTTP/1.1 415 Unsupported Media Type" ) ;
                    exit;
                }

                // メッセージタイプは位置情か
                if ( $event['message']['type'] == 'location'){
                    (new reserveManager($event))->lineReceiver();
                }

                // メッセージタイプはtextか
                if ( $event['message']['type'] == 'text'){
                    (new reserveManager($event))->lineMessageDispatcher();
                }

            }else{
                (new RegistUser())->registAnnounce($event);
            }


         }
    }
}

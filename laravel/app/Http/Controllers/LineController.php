<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Libs\reserveManager;
use App\Libs\RegistUser;


class LineController extends Controller
{
    public function index()
    {
        $requestHeaders = getallheaders();
        $requestBody = file_get_contents('php://input');
        Log::debug($requestBody);
        foreach (json_decode($requestBody, true)['events'] as $event){


            if((new RegistUser)->isUser($event['source']['userId'])){
                Log::debug("DB登録あり");
            }else{
                Log::debug("DBとうろくなし");
            }

            // リクエスト検証
            $signature =  base64_encode(hash_hmac('sha256', $requestBody, Config::get('bike_share.line.channelSecret'), true));
            if($signature !== $requestHeaders['X-Line-Signature']){
                header( "HTTP/1.1 404 Not Found" ) ;
                exit;
            }
            // Lineからのリクエストにmessage が含まれるか
            if(empty($event['message']['type'])){
                header( "HTTP/1.1 415 Unsupported Media Type" ) ;
                exit;
            }

            // メッセージタイプは位置情か
            if ( $event['message']['type'] == 'location'){
                (new reserveManager())->lineReceiver($event);
            }

            // メッセージタイプはtextか
            if ( $event['message']['type'] == 'text'){
                (new reserveManager())->lineMessageDispatcher($event);
            }
         }
    }
}

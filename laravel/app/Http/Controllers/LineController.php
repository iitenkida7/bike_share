<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\reserveManager;

class LineController extends Controller
{
    public function index()
    {
        $requestHeaders = getallheaders();
        $requestBody = file_get_contents('php://input');
        file_put_contents('php://stdout', $requestBody);
        $event = json_decode($requestBody, true)['events'][0];

        // リクエスト検証
        $lineChannelSecret = env('LINE_CHANNEL_SECRET');
        $signature =  base64_encode(hash_hmac('sha256', $requestBody, $lineChannelSecret, true));
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

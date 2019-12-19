<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Libs\reserveManager;
use App\Libs\RegistUser;


class LineController extends Controller
{
    public function index(Request $request)
    {
        foreach (json_decode($request->getContent(), true)['events'] as $event){
            if((new RegistUser)->isUser($event['source']['userId'])){
                Log::debug("DB登録ユーザー");
                // メッセージタイプは位置情か
                if ( $event['message']['type'] == 'location'){
                    (new reserveManager($event))->lineReceiver();
                }
                // メッセージタイプはtextか
                if ( $event['message']['type'] == 'text'){
                    (new reserveManager($event))->lineMessageDispatcher();
                }
            }else{
                Log::debug("DB未登録ユーザー");
                (new RegistUser())->registAnnounce($event);
            }
         }
    }
}

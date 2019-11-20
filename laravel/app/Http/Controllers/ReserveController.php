<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Libs\reserveManager;

class ReserveController extends Controller
{
    public function index()
    {
       // dd(get_declared_classes());
       // if( $now = (Carbon::now('Asia/Tokyo'))->format('G') >= 12 ){
       //     exit;
       // }

        (new ReserveManager())->specifiedReserve([
            '00010302' => 'ヨドバシカメラ前',
            '00010303' => '電気街口（西側交通広場）',
            '00010032' => 'UDX駐輪場前',
            '00010037' => '富士ソフト',
            '00010016' => '秋葉原公園',
        ]);
    }
}

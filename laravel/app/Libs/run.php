<?php

//require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../reserveManager.php');

use Carbon\Carbon;

// 出勤時だけ実行するよう。AMの場合のみ実行
if( $now = (Carbon::now('Asia/Tokyo'))->format('G') >= 12 ){
    exit;
} 

(new reserveManager())->specifiedReserve([
    '00010302' => 'ヨドバシカメラ前',
    '00010303' => '電気街口（西側交通広場）',
    '00010032' => 'UDX駐輪場前',
    '00010037' => '富士ソフト',
    '00010016' => '秋葉原公園',
]);

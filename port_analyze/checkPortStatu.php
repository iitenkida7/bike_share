<?php
require_once( __DIR__ . '/../vendor/autoload.php');
require_once( __DIR__ . '/bootstrap.php');
require_once( __DIR__ . '/../getPorts.php');

use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;

$ports  = [
    '00010302' => 'A3-27.秋葉原駅中央口（ヨドバシカメラ前）',
    '00010303' => 'A3-28.秋葉原駅電気街口(西側交通広場)',
    '00010194' => 'A3-25.神田駅北(神田警察通り)',
    '00010012' => 'A2-05.神田橋公園',
    '00010011' => 'A2-04.ちよだプラットフォームスクウェア',
    '00010382' => 'M3-01.渋谷マークシティ',
];

$status = (new GetPorts)->status($ports);

foreach ($status as $portStatus){
    Capsule::table('report')->insert([
        'created_at' => Carbon::now(),
        'port_code'  => $portStatus['portId'],
        'port_name'  => $portStatus['portName'],
        'bike_num'   => $portStatus['stockNum'],
    ]);
}

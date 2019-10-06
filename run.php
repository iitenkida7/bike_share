<?php

require_once(__DIR__ . "/getPorts.php");
require_once(__DIR__ . "/reserveBike.php");
require_once(__DIR__ . "/sendLineMessage.php");

$ports = [
    '00010302' => 'ヨドバシカメラ前',
    '00010303' => '電気街口（西側交通広場）',
    '00010032' => 'UDX駐輪場前',
    '00010037' => '富士ソフト',
    '00010016' => '秋葉原公園',
];

$msg="";
$status = (new GetPorts)->status($ports);

foreach ($status as $item) {
    $msg .= "\n[{$item['stockNum']}]{$item['portName']}";
}
$msg .= "\n";
$msg .= print_r((new ReserveBike)->reserveNearbyBike($status), true);

new sendLineMessage($msg);

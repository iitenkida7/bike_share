<?php

require_once(__DIR__ . "/getPorts.php");
require_once(__DIR__ . "/reserveBike.php");

$ports = [
    '00010302' => 'ヨドバシカメラ前',
    '00010303' => '電気街口（西側交通広場）',
    '00010032' => 'UDX駐輪場前',
    '00010037' => '富士ソフト',
    '00010016' => '秋葉原公園',
    //'00010468' => '高架下ドコモ',
    //'00010467' => '高架下マーチエキュート',
];

$status = (new GetPorts)->status($ports);


foreach ($status as $item) {
    echo "\n[{$item['stockNum']}]{$item['portName']}";
}
echo "\n";
print_r((new ReserveBike)->reserveNearbyBike($status));

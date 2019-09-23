<?php

require_once(__DIR__ . "/getPorts.php");
require_once(__DIR__ . "/reserveBike.php");

var_dump((new ReserveBike)->reserveNearbyBike((new GetPorts)->status()));

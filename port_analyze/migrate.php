<?php

require_once 'bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->create('report', function ($table) {
    $table->increments('id');
    $table->dateTime('created_at'); 
    $table->string('port_code');
    $table->string('port_name');
    $table->integer('bike_num');
});

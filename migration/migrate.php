<?php

require_once(__DIR__  .'/../libs/bootstrap.php');

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->create('users', function ($table) {
    $table->increments('id');
    $table->string('line_id');
    $table->string('chiyokuru_id');
    $table->integer('chiyokuru_pass');
    $table->timestamps();
});

Capsule::schema()->create('report', function ($table) {
    $table->increments('id');
    $table->dateTime('created_at'); 
    $table->string('port_code');
    $table->string('port_name');
    $table->integer('bike_num');
});

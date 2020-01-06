<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBikeStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bike_statuses', function (Blueprint $table) {
            $table->bigIncrements('id')->unique();
            $table->bigInteger('line_user_id');
            $table->string('bike_id');
            $table->string('bike_passcode');
            $table->string('port_name');
            $table->geometry('point');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bike_statuses');
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BikeStatus extends Model
{
    protected $fillable = ['line_id', 'bike_id', 'port_name'];
}

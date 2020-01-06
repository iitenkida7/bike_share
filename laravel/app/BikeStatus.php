<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;


class BikeStatus extends Model
{
    use SpatialTrait;

    protected $fillable = ['line_id', 'bike_id', 'port_name', 'line_user_id', 'bike_passcode', 'point' ];

    protected $spatialFields = [
        'point', 
    ];
}

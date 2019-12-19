<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LineUser extends Model
{
    protected $fillable = ['line_id', 'chiyokuru_id', 'chiyokuru_password'];
}

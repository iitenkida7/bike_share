<?php

namespace App\Libs;

use App\LineUser;

class RegistUser
{
    function __construct()
    {

    }

    public function isUser($lineId): bool
    {
        return LineUser::where('line_id', $lineId)->exists();
    }

}
<?php

return [
    'chiyokuru' => [
        'memberId' => env('MEMBER_ID'),
        'password' => env('PASSWORD'),
    ],

    'line' => [
        'channelAccessToken' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'channelSecret'      => env('LINE_CHANNEL_SECRET'),
        'userId'             => env('LINE_USER_ID'),
        ],
    ];
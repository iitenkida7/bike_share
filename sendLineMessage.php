<?php

require_once( __DIR__ . '/vendor/autoload.php');


use Dotenv\Dotenv;

class sendLineMessage
{
    private $lineApiUrl = 'https://notify-api.line.me/api/notify';
    private $lineApiToken;

    function __construct(string $msg)
    {
        // これは、もとの実行クラスがやるべきかな。
        $dotenv = Dotenv::create(__DIR__);
        $dotenv->load();
        $this->lineApiToken  = getenv('LINE_API_TOKEN');
        $this->post_message($msg);
    }

    function post_message($message) :bool
    {

        $data = http_build_query( [ 'message' => $message ], '', '&');

        $options = [
            'http'=> [
                'method'=>'POST',
                'header'=>'Authorization: Bearer ' . $this->lineApiToken . "\r\n"
                . "Content-Type: application/x-www-form-urlencoded\r\n"
                . 'Content-Length: ' . strlen($data)  . "\r\n" ,
                    'content' => $data,
                ]
            ];

        $context = stream_context_create($options);
        $resultJson = file_get_contents($this->lineApiUrl, false, $context);
        $resultArray = json_decode($resultJson, true);
        if($resultArray['status'] != 200)  {
            return false;
        }
        return true;
    }

}

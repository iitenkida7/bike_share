<?php

namespace App\Libs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use LINE\LINEBot;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineMessage
{
    public function __construct()
    {
        $httpClient = new CurlHTTPClient(Config::get('bike_share.line.channelAccessToken'));
        $this->bot = new LINEBot($httpClient, ['channelSecret' => Config::get('bike_share.line.channelSecret')]);
    }

    private $bot;
    private $replayToken = "";
    private $userId = "";
    private $builtMsg;

    public function setUserId($userId): object
    {
        $this->userId = $userId;
        return $this;
    }

    public function setReplayToken($replayToken): object
    {
        $this->replayToken = $replayToken;
        return $this;
    }

    public function postMessage($msg): bool
    {
        $this->builtMsg = new MessageBuilder\TextMessageBuilder($msg);
        return $this->post();
    }

    public function postLocation($title, $lat, $lng): bool
    {
        $this->builtMsg = new MessageBuilder\LocationMessageBuilder($title, $title, $lat, $lng);
        return $this->post();
    }

    private function post(): bool
    {
        if (!empty($this->replayToken)) {
            $response = $this->bot->replyMessage($this->replayToken, $this->builtMsg);
        } elseif (!empty($this->userId)) {
            $response = $this->bot->pushMessage($this->userId, $this->builtMsg);
        } else {
            return false;
        }

        Log::debug($response->getHTTPStatus() . ' ' . $response->getRawBody());
        if ($response->getHTTPStatus() == 200) {
            return true;
        }
        return false;
    }
}

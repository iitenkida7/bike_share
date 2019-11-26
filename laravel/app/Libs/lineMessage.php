<?php

namespace App\Libs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use LINE\LINEBot;
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineMessage
{

    function __construct()
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

    public function buildMessage($msg): object
    {
        $this->builtMsg = new MessageBuilder\TextMessageBuilder($msg);
        return $this;
    }

    public function buildLocation($port): object
    {
        $this->builtMsg = new MessageBuilder\LocationMessageBuilder($port['name'], $port['name'], $port['lat'], $port['lng']);
        return $this;
    }

    public function postMessage(): bool
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
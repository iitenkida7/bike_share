<?php
namespace App\Libs;
use Illuminate\Support\Facades\Log;
use Goutte\Client;

// 座標から一番最寄りのステーションを探す
Class getPortsFromGeo
{
    function __construct()
    {
        // Cookie 取得目的のアクセス
        $url =   "https://mixway.ekispert.net/ports/";
        $this->client = new Client();
        $this->client->request('GET', $url);
    }

    private $client;
    private $getPortsUrl = 'https://mixway.ekispert.net/api/geo/port?';
    private $lat;
    private $lng;
    
    public function setPoint($lat,$lng): self
    {
        $this->lat = $lat;
        $this->lng = $lng;
        return $this;
    }

    private function buildQuery(): string
    {
        return http_build_query([
            // request sample
            // https://mixway.ekispert.net/api/geo/port?geoPoint=35.692235113499905%2C139.76147826452643&limit=100000&language=ja
            'geoPoint' => $this->lat . ',' . $this->lng,
            'limit' => 20,
            'language' => 'ja'
        ]);
    }

    private function getPortsUrl(): string
    {
        return $this->getPortsUrl . $this->buildQuery();
    }

    public function getPorts(): array
    {
        $this->client->setHeader('referer', 'https://mixway.ekispert.net/ports/');
        $this->client->setHeader('x-requested-with', 'XMLHttpRequest');
        $this->client->setHeader('user-agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36');
        $this->client->request('GET', $this->getPortsUrl());
        
        return $this->postProcess($this->client->getResponse()->getContent());
    }

    private function postProcess($json): array
    {
        $filtered = collect(json_decode($json, true)['ResultSet']['Port'])->filter(function ($value) {
            return $value['Corporation']['code'] == 'DOCOMO' && $value['Availability']['stockNum'] >0;  
        });
        
        return $filtered->all();

    }
}

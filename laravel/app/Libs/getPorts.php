<?php
namespace App\Libs;

use Goutte\Client;

class GetPorts
{
    private $stockCheckUrl = 'https://tcc.docomo-cycle.jp/cgi-bin/csapi/csapiV1';

    private function createPostXml($portId) :string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><csreq><msgtype>3</msgtype><aplcode>B68A0F47E38937C7AC2C0051C4EC8C00</aplcode><park_id>' . $portId . '</park_id><get_num>100</get_num><get_start_no>1</get_start_no></csreq>';
    }

    public function status($ports = []) :array
    {
        if ($ports === []) {
            return [];
        }
        $portsStatus = [];
        foreach ($ports as $portId => $portName) {
            $stockCheck = (new Client())
                ->setHeader('Content-Type', 'application/xml; charset=utf-8')
                ->setHeader('User-Agent', '35_docomo_bikeshare/1.6.3.1 CFNetwork/978.0.7 Darwin/18.7.0')
                ->setHeader('Accept-Language', 'ja-jp')
                ->request('POST', $this->stockCheckUrl, [], [], [], $this->createPostXml($portId));

            $portsStatus[] = [
                'portId'   => $portId,
                'portName' => $portName,
                'stockNum' => $stockCheck->filter('total_num')->html(),
            ];

            usleep(5000);
        }
        return $portsStatus;
    }
}

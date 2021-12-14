<?php

namespace Bardiz12\ResiScrapper\Browser;

use Bardiz12\ResiScrapper\Constants;
use Bardiz12\ResiScrapper\Data\TrackData;
use Bardiz12\ResiScrapper\Exceptions\CurlException;
use Bardiz12\ResiScrapper\Contracts\CourierInterface;
use Bardiz12\ResiScrapper\Exceptions\AwbNotFoundException;
use Bardiz12\ResiScrapper\Exceptions\BrowserErrorException;

class JneBrowser implements CourierInterface{
    public function getResi($airwaybill) {
        $response = $this->browse($airwaybill);
        // dd($response);
        // echo $response;
        // die();
        return $this->generateTrackData($response, $airwaybill);
        // return $this->generateTrackData(json_decode(file_get_contents(__DIR__ ."/sicepat.json")));
    }
    
    private function browse($airwaybill){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://cekresi.jne.co.id/' . $airwaybill . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "_token=3ObSQ9zWDljI9ytH2geLBpm2IQQVXbJmIcEtp0n4&code=060230018571321&tracking=");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Connection: keep-alive';
        $headers[] = 'Cache-Control: max-age=0';
        $headers[] = 'Sec-Ch-Ua: \" Not A;Brand\";v=\"99\", \"Chromium\";v=\"96\", \"Google Chrome\";v=\"96\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Sec-Ch-Ua-Platform: \"macOS\"';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: ' . Constants::USER_AGENT;
        $headers[] = 'Origin: https://www.jne.co.id';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
        $headers[] = 'Sec-Fetch-Site: same-site';
        $headers[] = 'Sec-Fetch-Mode: navigate';
        $headers[] = 'Sec-Fetch-User: ?1';
        $headers[] = 'Sec-Fetch-Dest: document';
        $headers[] = 'Referer: https://www.jne.co.id/id/beranda';
        $headers[] = 'Accept-Language: en-US,en;q=0.9,id-ID;q=0.8,id;q=0.7,ar;q=0.6';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new CurlException(curl_error($ch));
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode !== 200){
            throw new AwbNotFoundException(Constants::COURIER_JNE, $airwaybill);
        }

        curl_close($ch);

        if(empty($result)){
            throw new BrowserErrorException('Empty response', Constants::COURIER_JNE);
        }

        if(stristr($result, 'Airwaybill is not found')){
            throw new AwbNotFoundException(Constants::COURIER_JNE, $airwaybill);
        }
        return $result;
    }

    private function generateTrackData(String $response, $airwaybill){
        $shipmentInfoRaw = [];

        preg_match_all('/<div style="height: 60px;overflow: auto;white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -pre-wrap;white-space: -o-pre-wrap; word-wrap: break-word;">(.*?)<\/div>/m', $response, $shipmentInfoRaw);
        
        $shipmentInfoArray = array_map('strip_tags',$shipmentInfoRaw[1]);
        [
            $service_code, 
            $sender_address,
            $receiver_address,
            $estimate_delivery,
            $delivery_date
        ] = $shipmentInfoArray;

        preg_match_all('/<div class="block">(\s.*?)+<\/div>(\s.*)<\/div>(\s.*)<\/div>/m', $response, $historyRaw);

        $histories = array_map(function($item){
            $item = str_replace(["  ","\t","\r","\n"], "", $item);
            preg_match_all("/<a>(.*?)<\/a>/m", $item, $status);
            preg_match_all("/<span>(.*?)<\/span>/m", $item, $datetimeRaw);
            $datetimeRaw = $datetimeRaw[1][0];

            return (Object) [
                'info' => $status[1][0],
                'datetime' => $this->convertDateToNormal($datetimeRaw),
                'status' => null
            ];
        },$historyRaw[0]);

        usort($histories, function($a, $b){
            return $a->datetime > $b->datetime;
        });

        preg_match_all('/<span>[a-zA-Z\s]{0,}<\/span>[\s|\n]{0,}<h4>(.*?)<\/h4>/m', $response, $shipmentDetailRaw);
        
        if(!isset($shipmentDetailRaw[1][0])){
            throw new BrowserErrorException('Scrapping error on shipmendetail', Constants::COURIER_JNE);
        }

        [
            $shipment_date_raw,
            $koli,
            $weight,
            $good_description,
            $shipper_name,
            $shipper_city,
            $receiver_name,
            $receiver_city
        ] = array_map('strip_tags', $shipmentDetailRaw[1]);

        $shipment_date = $this->convertDateToNormal($shipment_date_raw);


        

        $trackData = new TrackData();
        $trackData->airwaybill = $airwaybill;
        $trackData->courier = Constants::COURIER_JNE;
        $trackData->raw_data = null;
        $trackData->send_date = $shipment_date;
        $trackData->sender_name = $shipper_name;

        $trackData->sender_address = $shipper_city;
        $trackData->weight = $weight;
        $trackData->receiver_name = $receiver_name;
        $trackData->receiver_address = $receiver_city;
        $trackData->price = null;
        $trackData->received_date = $delivery_date;

        $trackData->history = $histories;
        $trackData->last_info = $histories[count($histories) - 1]->info;
        
        if(stristr($trackData->last_info, "DELIVERED TO")){
            $trackData->received_by = $receiver_name;
            $trackData->is_delivered = true;
        }
        
        return $trackData;
    }

    private function convertDateToNormal($datetimeRaw){
        [$date, $time] = explode(" ", $datetimeRaw ?? " ");
        [$day, $month, $year] = explode("-", $date);
        return "{$year}-{$month}-{$day} {$time}:00";
    }
}
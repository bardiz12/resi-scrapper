<?php

namespace Bardiz12\ResiScrapper\Browser;

use Bardiz12\ResiScrapper\Constants;
use Bardiz12\ResiScrapper\Data\TrackData;
use Bardiz12\ResiScrapper\Exceptions\CurlException;
use Bardiz12\ResiScrapper\Contracts\CourierInterface;
use Bardiz12\ResiScrapper\Exceptions\AwbNotFoundException;
use Bardiz12\ResiScrapper\Exceptions\BrowserErrorException;

class AnterajaBrowser implements CourierInterface{
    public function getResi($airwaybill) {
        $response = $this->browse($airwaybill);
        
        return $this->generateTrackData($response[0]);
    }
    
    private function browse($airwaybills){
        $ch = curl_init();
        if(!is_array($airwaybills)){
            $airwaybills = [$airwaybills];
        }
        $postParams = array_map(function($item){
            return [
                'awb' => $item
            ];
        }, $airwaybills);

        curl_setopt($ch, CURLOPT_URL, 'https://anteraja.id/api/api/tracking/trackparcel/getTrackStatus');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams));
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Sec-Ch-Ua: \" Not A;Brand\";v=\"99\", \"Chromium\";v=\"96\", \"Google Chrome\";v=\"96\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json, text/plain, */*';
        $headers[] = 'Referer: https://anteraja.id/tracking';
        $headers[] = 'Verification-Param: aamunthe';
        $headers[] = 'Sec-Ch-Ua-Platform: \"macOS\"';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new CurlException(curl_error($ch));
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode !== 200){
            throw new BrowserErrorException('Empty response', Constants::COURIER_ANTERAJA);
        }

        curl_close($ch);

        if(empty($result)){
            throw new BrowserErrorException('Empty response', Constants::COURIER_ANTERAJA);
        }
        $response = json_decode($result);
        
        $json_error = json_last_error();

        if($json_error !== JSON_ERROR_NONE){
            throw new BrowserErrorException("Json Error : ". $json_error, Constants::COURIER_ANTERAJA);
        }

        foreach($response as $key => $item){
            $item = json_decode($item);
        
            $json_error = json_last_error();

            if($json_error !== JSON_ERROR_NONE){
                throw new BrowserErrorException("Json Error : ". $json_error, Constants::COURIER_ANTERAJA);
            }
            
            $response[$key] = $item;
        }

        return $response;
    }

    private function generateTrackData(Object $response){
        $data = $response->content[0] ?? null;
        
        $trackData = new TrackData();
        $trackData->airwaybill = $data->awb;
        $trackData->courier = Constants::COURIER_ANTERAJA;
        $trackData->raw_data = $data;
        $trackData->send_date = null;
        $trackData->sender_name = $data->detail->sender->name;

        $trackData->sender_address = null;
        $trackData->weight = null;
        $trackData->receiver_name = $data->detail->receiver->name;
        $trackData->receiver_address = null;
        $trackData->price = null;
        $trackData->received_date = null;

        $historyRaw = array_map(function($item){
            return [
                'info' => $item->message->id ?? null,
                'timestamp' => strtotime($item->timestamp),
                'datetime' => $item->timestamp
            ];
        }, $data->history);
        usort($historyRaw, function($a, $b){
            return $a['timestamp'] > $b['timestamp'];
        });
        $history = [];
        
        foreach($historyRaw as $item){
            $history[] = (Object) [
                'datetime' => $item['datetime'],
                'status' => null,
                'info' => $item['info']
            ];
            $trackData->last_info = $item['info'];
        }

        $trackData->history = $history;
        
        if(stristr($trackData->last_info, 'Delivery sukses')){
            $trackData->status = 'DELIVERED';
            $trackData->is_delivered = true;
            preg_match("/diterima oleh (.*?)\./", $trackData->last_info, $matches);
            $trackData->received_by = $matches[1] ?? null;
        }
        
        return $trackData;
    }
}
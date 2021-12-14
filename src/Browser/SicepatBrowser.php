<?php

namespace Bardiz12\ResiScrapper\Browser;

use Bardiz12\ResiScrapper\Constants;
use Bardiz12\ResiScrapper\Data\TrackData;
use Bardiz12\ResiScrapper\Exceptions\CurlException;
use Bardiz12\ResiScrapper\Contracts\CourierInterface;
use Bardiz12\ResiScrapper\Exceptions\AwbNotFoundException;
use Bardiz12\ResiScrapper\Exceptions\BrowserErrorException;

class SicepatBrowser implements CourierInterface{
    public function getResi($airwaybill) {
        $response = $this->browse($airwaybill);
        return $this->generateTrackData($response);
        // return $this->generateTrackData(json_decode(file_get_contents(__DIR__ ."/sicepat.json")));
    }
    
    private function browse($airwaybill){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://content-main-api-production.sicepat.com/public/check-awb/' . $airwaybill);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Authority: content-main-api-production.sicepat.com';
        $headers[] = 'Sec-Ch-Ua: \" Not A;Brand\";v=\"99\", \"Chromium\";v=\"96\", \"Google Chrome\";v=\"96\"';
        $headers[] = 'Accept: application/json, text/plain, */*';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'User-Agent: '. Constants::URL_SICEPAT;
        $headers[] = 'Sec-Ch-Ua-Platform: \"macOS\"';
        $headers[] = 'Origin: https://www.sicepat.com';
        $headers[] = 'Sec-Fetch-Site: same-site';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Referer: https://www.sicepat.com/';
        $headers[] = 'Accept-Language: en-US,en;q=0.9,id-ID;q=0.8,id;q=0.7,ar;q=0.6';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new CurlException(curl_error($ch));
        }
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($httpcode !== 200){
            throw new AwbNotFoundException(Constants::COURIER_SICEPAT, $airwaybill);
        }

        curl_close($ch);

        if(empty($result)){
            throw new BrowserErrorException('Empty response', Constants::COURIER_SICEPAT);
        }
        $response = json_decode($result);
        
        $json_error = json_last_error();

        if($json_error !== JSON_ERROR_NONE){
            throw new BrowserErrorException("Json Error : ". $json_error, Constants::COURIER_SICEPAT);
        }

        if(($response->sicepat->status->code ?? null) !== 200){
            throw new AwbNotFoundException(Constants::COURIER_SICEPAT, $airwaybill);
        }

        return $response;
    }

    private function generateTrackData(Object $response){
        $data = $response->sicepat->result;

        $trackData = new TrackData();
        $trackData->airwaybill = $data->waybill_number;
        $trackData->courier = Constants::COURIER_SICEPAT;
        $trackData->raw_data = $data;
        $trackData->send_date = $data->send_date;
        $trackData->sender_name = $data->sender;

        if(isset($data->partner)){
            $trackData->sender_name .= " ({$data->partner})";
        }

        $trackData->sender_address = $data->sender_address;
        $trackData->weight = $data->weight;
        $trackData->receiver_name = $data->receiver_name;
        $trackData->receiver_address = $data->receiver_address;
        $trackData->price = $data->totalprice;
        $trackData->received_date = $data->POD_receiver_time;
        $history = [];
        
        foreach($data->track_history as $item){
            $history[] = (Object) [
                'datetime' => $item->date_time,
                'status' => $item->status,
                'info' => $item->city ?? ($item->receiver_name ?? '-')
            ];

            $trackData->status = $item->status;
        }

        $trackData->history = $history;
        $trackData->last_info = $data->last_status->city ?? ($data->last_status->receiver_name ?? '-');
        
        if($trackData->status === 'DELIVERED'){
            preg_match("/\[(.*?)\]/", $trackData->last_info, $matches);
            $trackData->received_by = $matches[1] ?? null;
            $trackData->is_delivered = true;
        }
        
        return $trackData;
    }
}
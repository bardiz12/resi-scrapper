<?php

namespace Bardiz12\ResiScrapper\Data;


class TrackData{
    public $courier;
    public $airwaybill;
    public $raw_data;
    public $weight;
    public $send_date;
    public $sender_name;
    public $sender_address;
    public $receiver_name;
    public $receiver_address;
    public $received_date;
    public $price;
    public $history;
    public $service_code = null;

    public $last_info;
    public $status;
    public $received_by = null;
    public $is_delivered = false;
}
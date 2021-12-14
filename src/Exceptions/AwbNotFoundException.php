<?php

namespace Bardiz12\ResiScrapper\Exceptions;

use Exception;

class AwbNotFoundException extends Exception{
    /**
     * courier
     *
     * @var [type]
     */
    private $courier;
    public function __construct($courier, $awb)
    {
        parent::__construct();
        $this->courier = $courier;
        $this->message = "AWB ($awb) Notfound";
    }

    public function getCourier(){
        return $this->courier;
    }
}
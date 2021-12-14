<?php

namespace Bardiz12\ResiScrapper\Exceptions;

use Exception;

class BrowserErrorException extends Exception{
    /**
     * courier
     *
     * @var [type]
     */
    private $courier;
    public function __construct($message, $courier)
    {
        parent::__construct();
        $this->courier = $courier;
        $this->message = "Browser Error : ". $message;
    }

    public function getCourier(){
        return $this->courier;
    }
}
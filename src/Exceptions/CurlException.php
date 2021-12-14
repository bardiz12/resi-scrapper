<?php

namespace Bardiz12\ResiScrapper\Exceptions;

use Exception;

class CurlException extends Exception{
    public function __construct($message)
    {
        parent::__construct();
        $this->message = "Error Curl : ". $message;
    }
}
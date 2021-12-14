<?php

namespace Bardiz12\ResiScrapper\Contracts;


interface CourierInterface {
    public function getResi($airwaybill);
}
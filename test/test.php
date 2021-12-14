<?php

use Bardiz12\ResiScrapper\Constants;
use Bardiz12\ResiScrapper\ResiTracker;

require_once __DIR__ ."/../vendor/autoload.php";


$awbSicepat = 'x';
$awbAnteraja = 'x';
$awbJne = 'x';

$resiTracker = new ResiTracker();
$trackJne = $resiTracker->getTrackData(Constants::COURIER_JNE, $awbJne);
$trackSicepat = $resiTracker->getTrackData(Constants::COURIER_SICEPAT, $awbSicepat);
$trackAnteraja = $resiTracker->getTrackData(Constants::COURIER_ANTERAJA, $awbAnteraja);

var_dump($trackJne, $trackSicepat, $trackAnteraja);
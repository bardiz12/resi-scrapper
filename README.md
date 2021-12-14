resi-scrapper
=============

A package to get Indonesian Courier package tracking. support : JNE, Sicepat, Anteraja


Usage
------------
```php
<?php
require_once __DIR__ ."/../vendor/autoload.php";

use Bardiz12\ResiScrapper\Constants;
use Bardiz12\ResiScrapper\ResiTracker;

$awbSicepat = 'x';
$awbAnteraja = 'x';
$awbJne = 'x';

$resiTracker = new ResiTracker();
$trackJne = $resiTracker->getTrackData(Constants::COURIER_JNE, $awbJne);
$trackSicepat = $resiTracker->getTrackData(Constants::COURIER_SICEPAT, $awbSicepat);
$trackAnteraja = $resiTracker->getTrackData(Constants::COURIER_ANTERAJA, $awbAnteraja);

var_dump($trackJne, $trackSicepat, $trackAnteraja);
```
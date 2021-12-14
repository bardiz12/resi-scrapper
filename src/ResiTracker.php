<?php

namespace Bardiz12\ResiScrapper;

use Bardiz12\ResiScrapper\Browser\AnterajaBrowser;
use Bardiz12\ResiScrapper\Browser\JneBrowser;
use Bardiz12\ResiScrapper\Browser\SicepatBrowser;
use Bardiz12\ResiScrapper\Contracts\CourierInterface;
use Bardiz12\ResiScrapper\Data\TrackData;
use Bardiz12\ResiScrapper\Exceptions\BrowserErrorException;

class ResiTracker{
    /**
     * Undocumented variable
     *
     * @var \Bardiz12\ResiScrapper\Contracts\CourierInterface[]
     */
    private $browsers = [];

    /**
     * String
     *
     * @var String[]
     */
    private $customBrowserClass = [];
    
    public function getTrackData($courier, $ariwaybill) : TrackData{
        $browser = $this->getBrowser($courier);
        return $browser->getResi($ariwaybill);
    }

    private function getBrowser($courier) : CourierInterface {
        if(!isset($this->browsers[$courier])){
            if(isset($this->customBrowserClass[$courier])){
                $this->browsers[$courier] = new $this->customBrowserClass[$courier]();
            }else if($courier === Constants::COURIER_JNE){
                $this->browsers[$courier] = new JneBrowser();
            }else if($courier === Constants::COURIER_ANTERAJA){
                $this->browsers[$courier] = new AnterajaBrowser();
            }else if($courier === Constants::COURIER_SICEPAT){
                $this->browsers[$courier] = new SicepatBrowser();
            }else{
                throw new BrowserErrorException('browser for courier '. $courier. ' not found', $courier);
            }
        }

        return $this->browsers[$courier];
    }

    public function addCustomBrowser($courier, $browserClassName){
        $this->customBrowserClass[$courier] = $browserClassName; 
    }
}
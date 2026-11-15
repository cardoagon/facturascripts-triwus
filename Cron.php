<?php

namespace FacturaScripts\Plugins\triwus;

//use FacturaScripts\Core\App\AppSettings;

use FacturaScripts\Core\Tools;


/**
 *  Cron for Triwus plugin
 *
 * @author Raul <raljopa@gmail.com>
 */
class Cron extends \FacturaScripts\Core\Template\CronClass {

    public function run(): void {
        /* $appSettings = new AppSettings();
        $sincronizar = $appSettings->get('triwus', 'activecron'); */

        $appSettings = new Tools();
        $sincronizar = $appSettings->settings('triwus', 'activecron');
        
        if (!$sincronizar) return;
        
        $comprobar = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
                
        $comprobar->productSincronization();

        $comprobar->getOrders();
    }
}

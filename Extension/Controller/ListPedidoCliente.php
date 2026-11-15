<?php

namespace FacturaScripts\Plugins\triwus\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\Where;

use FacturaScripts\Core\Tools;

/**
 * Description of ListPedido
 *
 * @author Raul <raljopa@gmail.com>
 */
class ListPedidoCliente {

    
    /**
     *
     * add Button to ListPedidoCliente
     */
    public function createViews() {
        return function () {

            $this->addButton('ListPedidoCliente', [
                'action' => 'downloadtriwusorders',
                'icon' => 'fas fa-globe',
                'label' => 'triwus-orders'
            ]);
        };
    }
    /**
     *
     * Process new action for Triwus
     */
    public function execAfterAction() {
        return function ($action) {
            switch ($action) {
                case 'downloadtriwusorders':
                    $this->downloadTriwusOrders();
                    break;
            }
        };
    }
    /**
     *
     * get Triwus orders
     */
    public function downloadTriwusOrders() {
        return function () {

            $twApi = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
            $twApi->getOrders();
   
            Tools::log()->notice('online-orders-updated');         
            //~ $this->toolBox()->i18nLog()->notice('online-orders-updated');

        };
    }

    
}

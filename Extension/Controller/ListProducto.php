<?php
namespace FacturaScripts\Plugins\triwus\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\Where;
use FacturaScripts\Core\Base\ToolBox;

use FacturaScripts\Core\Tools;

/**
 * Description of ListProducto
 *
 * @author Raúl <raljopa@gmail.com>
 */
class ListProducto {
    /** 
     * 
     * Funcionality for Triwus orders
     */
    public function createViews() {
        return function () {
            $this->addFilterCheckbox('ListProducto', 'noonline', 'no-online', 'twid', '=', '0');

            $this->addView('ListTwProduct', 'TwProduct', 'online-products', 'fas fa-globe');
            $sel = 'select twid from productos';
            $this->addFilterCheckbox('ListTwProduct', 'no-bind', 'no-bind', 'twid', 'NOT IN', $sel);
            $this->addSearchFields('ListTwProduct', ['name', 'description']);

            $this->addButton('ListProducto', [
                'action' => 'updateonlineproducts',
                'icon' => 'fas fa-upload',
                'label' => 'update-online-products'
            ]);
            // disable delete button
            $this->setSettings('ListTwProduct', 'btnDelete', false);
            $this->setSettings('ListTwProduct', 'btnNew', false);
        };
    }
    /*
     * Process actions for Triwus
     */
    public function execAfterAction()
    {
        return function($action){
            switch ($action) {
                case 'getonlineproducts':
                    $this->getTriwusProducts();
                    break;
                case 'updateonlineproducts':
                    $this->updateTriwusProducts();
                    break;
            }
        };
    }
    /**
     *
     * Download Triwus products catalog
     */
    public function getTriwusProducts() {
        return function () {
            $twApi = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
            $twApi->getTriwusProducts();
            Tools::log()->notice('online-products-updated');
        };
    }
    /**
     *
     * Update Triwus stocks
     */
    public function updateTriwusProducts() {
        return function () {
            $twApi = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
        $twApi->updateTriwusProducts();
        Tools::log()->notice('online-products-updated');
        };
    }

}

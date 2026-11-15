<?php
namespace FacturaScripts\Plugins\triwus\Controller;

use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditTwProduct
 *
 * @author Raúl <raljopa@gmail.com>
 */
class EditTwProduct extends EditController {

    /**
     * Returns the model name.
     *
     * @return string
     */
    public function getModelClassName():string {
        return 'Almacen';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData():array {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'warehouse';
        $data['icon'] = 'fas fa-warehouse';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Add tabs or views.
     * View for show basic Triwus product data
     */
    protected function createViews() {
        
        $this->addEditView('EditTwProduct', 'TwProduct', 'TwProduct', 'fas fa-globe');
        $this->setSettings('EditTwProduct', 'btnNew', false);
        $this->setSettings('EditTwProduct', 'btnSave', false);
        $this->setSettings('EditTwProduct', 'btnUndo', false);
        $this->setSettings('EditTwProduct', 'btnDelete', false);
    }

}

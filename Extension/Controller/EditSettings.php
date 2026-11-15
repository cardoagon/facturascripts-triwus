<?php

namespace FacturaScripts\Plugins\triwus\Extension\Controller;

use FacturaScripts\Core\Base\DataBase\Where;

/**
 * Description of EditSettings
 *
 * @author RaÃºl  <raljopa@gmail.com>
 */
class EditSettings {
    public function loadData() {

        return function ($viewName, $view) {

            switch ($viewName){
                case 'Settingstriwus':
                    $view->loadData();
                    $this->loadOrderStateValues($viewName);
                    break;
            }
        };
    }
    public function loadOrderStateValues() {
        return function(string $viewName)
        {
        $where = [
        //~ new DataBaseWhere('tipodoc', 'PedidoCliente')
        Where::eq('tipodoc', 'PedidoCliente')
        ];
        $estados = $this->codeModel->all('estados_documentos', 'idestado', 'nombre', false, $where);
            $columnState = $this->views[$viewName]->columnForName('defaultorderstate');
        if($columnState && $columnState->widget->getType() === 'select'){
        $columnState->widget->setValuesFromCodeModel($estados);
        }
        };
    }
}

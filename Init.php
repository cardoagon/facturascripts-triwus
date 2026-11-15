<?php

namespace FacturaScripts\Plugins\triwus;

use FacturaScripts\Core\Template\InitClass;
//~ use FacturaScripts\Core\Lib\Calculator;
/**
 * Load Extensions for Triwus plugin
 *
 * @author Raul <raljopa@gmail.com>
 */
class Init extends InitClass {

    public function init(): void
    {
        $this->loadExtension(new Extension\Controller\ListProducto());
        $this->loadExtension(new Extension\Controller\ListPedidoCliente());
        $this->loadExtension(new Extension\Controller\EditSettings());
        
        //~ Calculator::addMod(new Mod\CalculatorMod());
    }

    public function uninstall(): void
    {
        // Limpieza de datos o configuraciones al desinstalar el plugin
    }

     public function update(): void
    {
        // Ajustes al instalar o actualizar el plugin
    }
    
}

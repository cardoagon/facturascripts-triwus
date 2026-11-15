<?php
namespace FacturaScripts\Plugins\triwus\Model;

// use \FacturaScripts\Core\Model\Base\ModelClass;

use \FacturaScripts\Core\Template\ModelClass;

/**
 * Modl for TwProduct
 *
 * @author Raúl <raljopa@gmail.com>
 */
class TwProduct extends \FacturaScripts\Core\Template\ModelClass {

    use \FacturaScripts\Core\Template\ModelTrait;

    public $idproduct;
    public $twid;
    public $name;
    public $description;
    public $price;

    /**
     * Returns the name of the column that is the primary key of the model.
     *
     * @return string
     */
    public static function primaryColumn(): string {
        return 'idproduct';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string {
        return 'twproducts';
    }
    public function url(string $type = 'auto', string $list = 'ListProducto?activetab=List'): string {
        return parent::url($type, $list);
    }

}

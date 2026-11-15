<?php
namespace FacturaScripts\Plugins\triwus\Lib;

use FacturaScripts\Core\App\AppSettings;

use FacturaScripts\Core\Base\DataBase\Where;
use FacturaScripts\Core\Lib\Calculator;
//~ use FacturaScripts\Core\Base\ToolBox; //* mingo 20250903 - non é necesaria

use FacturaScripts\Core\Tools;

const TWTRANSFERENCIA = 'TRANSFERENCIA BANCARIA';
const TWREEMBOLSO = 'CONTRA REEMBOLSO';
const TWEFECTIVO = 'EFECTIVO (RECOGER EN TIENDA)';
const TWPAYPAL = 'PAYPAL';
const TWTPV = 'TPV VIRTUAL';
const TWBIZUM = 'BIZUM';

/**
 * functions to sincro width Triwus API
 *
 *
 * @author Raúl <raljopa@gmail.com>
 */
class TriwusAPI {

    private $url;
    private $apikey;
    private $apisecret;
    private $connection;

    /**
     * Init data in construct
     */
    public function __construct() {
       /*  $appSettings = new AppSettings;
        $this->url = $appSettings->get('triwus', 'urltriwus');
        $this->apikey = $appSettings->get('triwus', 'twclienteapikey');
        $this->twsecretapikey = $appSettings->get('triwus', 'twsecretapikey'); */

        $appSettings = new Tools();
        $this->url = $appSettings->settings('triwus', 'urltriwus');
        $this->apikey = $appSettings->settings('triwus', 'twclienteapikey');
        $this->twsecretapikey = $appSettings->settings('triwus', 'twsecretapikey');
    }

    /**
     * Connect with Remote API Server
     */
    public function connect() {
//~ Tools::log()->notice("TriwusAPI.connect(). ::" . $this->apikey . "::" . $this->twsecretapikey);Tools::log()->notice("TriwusAPI.connect(). ::" . $this->apikey . "::" . $this->twsecretapikey);

        $this->connection = stream_context_create(array(
            'http' => array(
                'header' => "Authorization: Basic " . base64_encode("$this->apikey:$this->twsecretapikey")
            )
        ));

    }

    /**
     *
     * @param type integer Id Product. optional
     * @return type array Product list
     */
    public function getProducts(int $productId = 0): array {
        $this->connect();
        $products = [];
        $url = $this->url . "/rpc/facturascripts/producto.php";


        if ($productId !== 0) {
            $queryData = [
                'op' => 'get',
                'id' => $productId
            ];
        }
        else {
            $data = date("Y-m-d", strtotime(date("Y-m-d") . "-1 day"));

            $queryData = [
                'op'   => 'get',
                'data' => $data
            ];
        }

        $query    = http_build_query($queryData);

Tools::Log()->notice( "TriwusAPI.getProducts(). ::" . $url . "?" . $query );



        $jsonData = file_get_contents($url . "?" . $query, false, $this->connection);
        $products = json_decode($jsonData, 1);
        if (is_null($products)) {
            return [];
        }
        return $products;
    }

    /**
     * add or update orders from  Triwus online shop
     * @param string $lastDate Date of last Sincronization
     */
    public function getOrders(string $lastDate = '') {

        $pedido = new \FacturaScripts\Dinamic\Model\PedidoCliente();
        $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
        $contacto = new \FacturaScripts\Dinamic\Model\Contacto();
        $estados = new \FacturaScripts\Dinamic\Model\EstadoDocumento();
       // $appSettings = new AppSettings;
        $appSettings = new Tools();
        $fecha = new \DateTime();
        if ($lastDate !== '') {
            $lastOrderDate = $lastDate;
        } else {
          //  $lastOrderDate = $appSettings->get('triwus', 'fechaultimopedido');
            $lastOrderDate = $appSettings->settings('triwus', 'fechaultimopedido');
        }
       /* if ($lastOrderDate === '') {
          $lastOrderDate = $fecha->format('Y-m-d');
          } */
        //~ $whereEstado = [
            //~ new DataBaseWhere('tipodoc', 'PedidoCliente')
            //~ , new DataBaseWhere('nombre', 'Cancelado')
        //~ ];
        $whereEstado = [
            Where::eq('tipodoc', 'PedidoCliente')
            , Where::eq('nombre', 'Cancelado')
        ];
        $estados->loadFromCode('', $whereEstado);
        if ($estados) {
            $estadoCancelado = $estados->idestado;
        }

        $dateQuery = \DateTime::createFromFormat('Y-m-d', $lastOrderDate);
        if ($dateQuery == false) {
            $dateQuery = new \DateTime();

        } else {
            $dateQuery->sub(new \DateInterval('P1D'));
        }


        $url = $this->url . "/rpc/facturascripts/pedido.php";

        if ($dateQuery) {

            $queryData = [
                "op" => "get"
                , "data" => $dateQuery->format("Ymd")
            ];
        } else {
            $queryData = [
                "op" => "get"
            ];
        }
        $query = http_build_query($queryData);

        $url = $url . "?" . $query;

Tools::log()->notice("TriwusAPI.getOrders(). ::" . $url);


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode("$this->apikey:$this->twsecretapikey")]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ordersData = curl_exec($ch);
        curl_close($ch);
        $ordersData = json_decode($ordersData, 1);

        if ($ordersData == null) return; //* mingo 20250902

        foreach ($ordersData as $order) {
//Cliente

            if ($order['id_cliente'] !== null) {
                $codcliente = $this->updateCliente($order['id_cliente']);
            } else {
                $codcliente = $appSettings->settings('triwus', 'codcliente');
            }

            $this->updateOrder($order, $codcliente, $estadoCancelado);
        }
    }
    /**
     * Get online shop catalog
     */
    public function getTriwusProducts() {

        $twApi = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
        $twProducto = new \FacturaScripts\Dinamic\Model\TwProduct();
        $producto = new \FacturaScripts\Dinamic\Model\Producto();
        $variante = new \FacturaScripts\Dinamic\Model\Variante();
        $productList = $twApi->getProducts();

        foreach ($productList as $product) {
            $where = [
                //~ new DataBaseWhere('twid', $product['id_articulo'])
                Where::eq('twid', $product['id_articulo'])
            ];
            $twProducto->loadFromCode('', $where);
            $twProducto->twid = $product['id_articulo'];
            $twProducto->name = $product['id_articulo'] . "-" . $product['nome'];
            $twProducto->description = $product['notas'];
            $twProducto->price = $product['prezo'];
            $twProducto->save();
/* actulizar según códigos de barras.
            $whereVariante = [new DataBaseWhere('codbarras', $product['ean'])];

            if ($variante->loadFromCode('', $whereVariante)) {
                $producto->loadFromCode($variante->idproducto);
                $producto->twid = $product['id_articulo'];
                $producto->save();
            }*/
        }

      //  ToolBox::i18nLog()->notice('online-products-updated');
          Tools::log()->notice('online-products-updated');
    }

    /**
     * Update stocks from Facturascripts to Triwus online store
     * @param type $lastDate
     * @param type $productId
     * @return boolean
     */
    public function updateTriwusProducts($lastDate = null, $productId = '') {
        //~ if ($lastDate == null) $lastDate = date("Y-m-d", strtotime(date("Y-m-d") . "-1 day")); //* mingo
        if ($lastDate == null) $lastDate = date("Y-m-d"); //* mingo

        $twApi    = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
        $products = new \FacturaScripts\Dinamic\Model\Producto();
        $stock    = new \FacturaScripts\Dinamic\Model\Stock();


        $url = $this->url . "/rpc/facturascripts/producto.php";

       // $appSettings = new AppSettings;

        $appSettings = new Tools();

       // $codalmacen = $appSettings->get('triwus', 'codalmacen');
      //  $this->url  = $appSettings->get('triwus', 'urltriwus');

        $codalmacen = $appSettings->settings('triwus', 'codalmacen');
        $this->url  = $appSettings->settings('triwus', 'urltriwus');


        //~ $whereProducts = [
            //~ new DataBaseWhere('twid', '0', '<>'),
            //~ new DataBaseWhere('actualizado', $lastDate, ">=")
        //~ ];
        $whereProducts = [
            Where::ne('twid', '0'),
            Where::gt('actualizado', $lastDate)
        ];


//ToolBox::i18nLog()->notice( $lastDate );
Tools::Log()->notice( $lastDate );

        $products = $products->all($whereProducts);
        $twApi->connect();
        foreach ($products as $product) {
            //~ $whereStock =[
                //~ New \FacturaScripts\Core\Base\DataBase\DataBaseWhere('idproducto',$product->idproducto),
                //~ new DataBaseWhere('codalmacen',$codalmacen)
            //~ ];
            $whereStock =[
                Where::eq('idproducto',$product->idproducto),
                Where::eq('codalmacen',$codalmacen)
            ];
            $stock->loadFromCode('',$whereStock);
            $queryData = [
                'op' => 'put'
                , 'id' => $product->twid
                , 'ud' => $stock->disponible
            ];
            $query = http_build_query($queryData);

//ToolBox::i18nLog()->notice( $url . "?" . $query );
Tools::Log()->notice( $url . "?" . $query );

            $result = file_get_contents($url . "?" . $query, false, $twApi->connection);
            $result = json_decode($result, 1);

            if ($result !== 'OK') {
                //ToolBox::i18nLog()->error('online-products-update-error' . $product->referencia . ' ' . $product->descripcion);
                Tools::Log()->error('online-products-update-error' . $product->referencia . ' ' . $product->descripcion);
            }
        }
   //     ToolBox::i18nLog()->notice('online-products-updated');
        Tools::Log()->notice('online-products-updated');
        return true;
    }

    /**
     * Update or add Client in Facturascripts from Triwus online store
     * @param type $twid Client id in Triwus online store
     * @return boolean
     */
    public function updateCliente($twid) {

            $twApi = new \FacturaScripts\Plugins\triwus\Lib\TriwusAPI();
            $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
            $contacto = new \FacturaScripts\Dinamic\Model\Contacto();
        $provincia = new \FacturaScripts\Dinamic\Model\Provincia();

        $url = $this->url . "/rpc/facturascripts/cliente.php";
        $queryData = [
                'op' => 'get'
                , 'id' => $twid
            ];
          $query = http_build_query($queryData);
          

Tools::log()->notice("TriwusAPI.updateCliente(). ::" . $url . "?" . $query);
          
        $twApi->connect();
            $data = json_decode(file_get_contents($url . "?" . $query, false, $twApi->connection), 1);
        if (count($data)) {
            $data = $data[0];
        } else {
            return false;
        }

//~ Tools::log()->notice("TriwusAPI.updateCliente(). ::" . print_r($data, 1));

        //~ $whereCliente = [
                //~ new DataBaseWhere('twid', $data['id_usuario'])
            //~ , new DataBaseWhere('cifnif', $data['cnif_d'], "=", 'OR')
            //~ , new DataBaseWhere('email', $data['email'], "=", 'OR')
        //~ ];

       $whereCliente = Where::sub([
              Where::eq('twid', $data['id_usuario'])
            , Where::eq('cifnif', $data['cnif_d'])
            , Where::eq('email', $data['email'])
          ], 'OR');

//~ Tools::log()->notice("TriwusAPI.updateCliente(). ::" .  print_r($whereCliente, 1));

            $cliente->loadFromCode('', $whereCliente);
            $cliente->nombre = $data['nome'];
        $cliente->razonsocial = $data['razon_social'];
        $cliente->email = $data['email'];
        $cliente->telefono1 = $data['mobil_p0'] . $data['mobil'];
        $cliente->telefono2 = $data['tlfn'];
        $cliente->twid = $data['id_usuario'];
        $cliente->cifnif = $data['cnif_d'] . '';
        $cliente->web = $data['web'];
        $cliente->observaciones = $data['notas'];
        $cliente->save();


        if ($cliente->idcontactofact !== null) {
                $contacto->loadFromCode($cliente->idcontactofact);
            }
            $contacto->apellidos = $cliente->nombre;
        $contacto->ciudad = $data['localidade'];
        $contacto->codcliente = $cliente->codcliente;
            $contacto->codpostal = $data['cp'];
        $contacto->codpais = $data['country_cod'];
        $contacto->email = $cliente->email;
        $contacto->telefono1 = $cliente->telefono1;
        $contacto->direccion = $data['codtipovia'] . ' ' . $data['via'] . ', ' . $data['num'] . ' ' . $data['resto'];
        $contacto->provincia = $provincia->loadFromCode((int) substr($data['cp'], 2)) ? $provincia->provincia : '';
        $contacto->save();

            $cliente->idcontactofact = $contacto->idcontacto;

            return $cliente->codcliente;

    }
    /**
     * Update Order data from Triwus online shop
     * @param type $order
     * @param type $codcliente
     * @param type $idCancelado
     */
    function updateOrder(array $order, string $codcliente, int $idCancelado = 0) {

        $pedido = new \FacturaScripts\Dinamic\Model\PedidoCliente();
            $cliente = new \FacturaScripts\Dinamic\Model\Cliente();
            $contacto = new \FacturaScripts\Dinamic\Model\Contacto();
          //  $appSettings = new AppSettings;
             $appSettings = new Tools();
        //~ $docTools = new \FacturaScripts\Core\Lib\BusinessDocumentTools();
            $wherePedido = [
                //~ new DataBaseWhere('twid', $order['ano'] . $order['numero'])
                Where::eq('twid', $order['ano'] . $order['numero'])
        ];
            $pedido->loadFromcode('', $wherePedido);

        $cliente->loadFromCode($codcliente);

        $contacto->loadFromCode($cliente->idcontactofact);

        $pedido->apartado = $contacto->apartado;
        $pedido->cifnif = $cliente->cifnif . '';
        $pedido->ciudad = $contacto->ciudad;
        $pedido->codalmacen = $appSettings->settings('triwus', 'codalmacen');
        //~ $pedido->coddivisa = $appSettings->settings('default', 'coddivisa');
        $pedido->coddivisa = 'EUR';
        $pedido->codpago = $appSettings->settings('default', 'codpago');
        $pedido->codpais = $contacto->codpais;
        $pedido->codpostal = $contacto->codpostal;
        $pedido->codserie = $appSettings->settings('triwus', 'codserie');
        $pedido->direccion = $contacto->direccion;

        $fecha = \DateTime::createFromFormat('Y-m-d H:i:s', $order['momento']);

        $pedido->fecha = $fecha->format('Y-m-d');
        $pedido->hora = $fecha->format('H:i:s');
        $pedido->codcliente = $codcliente;
        $pedido->nombrecliente = $cliente->nombre;
        $pedido->numero2 = $order['ref'];
        $pedido->observaciones = $order['notas'];
        $pedido->provincia = $contacto->provincia;
        //$pedido->nick = $this->user->nick;
        $pedido->twid = $order['ano'] . $order['numero'];
        $pedido->idcontactofact = $cliente->idcontactofact;
        $pedido->idcontactoenv = $cliente->idcontactoenv;

        switch (strtoupper($order['fpago'])) {
            case TWTRANSFERENCIA:
                $pedido->codpago = $appSettings->settings('triwus', 'codtransferencia');
                break;
            case TWREEMBOLSO:
                $pedido->codpago = $appSettings->settings('triwus', 'codreembolso');
                break;
            case TWEFECTIVO:
                $pedido->codpago = $appSettings->settings('triwus', 'codefectivo');
                break;
            case TWPAYPAL:
                $pedido->codpago = $appSettings->settings('triwus', 'codpaypal');
                break;
            case TWTPV:
                $pedido->codpago = $appSettings->settings('triwus', 'codcredito');
                break;
            case TWBIZUM:
                $pedido->codpago = $appSettings->settings('triwus', 'codbizum');
                break;
            default:
                $pedido->codpago = $appSettings->settings('default', 'codpago');
                break;
        }
        $pedido->dtopor1 = $order['dto'];
        
        $resultado = $pedido->save();

//~ Tools::log()->notice("TriwusAPI.updateOrder(). ::" . $pedido->idpedido); 
 
        
        if (!$resultado) return;  //** 20250820 mingo.
        
//~ Tools::log()->notice("TriwusAPI.updateOrder(). :: resultado OK"); 
  

        $this->saveLineOrders($order, $pedido->idpedido);


        $lines = $pedido->getLines();

        Calculator::calculate($pedido, $lines, true);

        //~ $docTools->recalculate($pedido);

        if (!$pedido->save()) {
            Tools::Log()->error('triwus-online-order ' . $order['ref'] . ' not-saved');
        }
        $pedido->idestado = $appSettings->settings('triwus', 'defaultorderstate');

        $pedido->save();
        Calculator::calculate($pedido, $lines, true);

        //~ $appSettings = new appSettings();
        $appSettings = new Tools();
        $appSettings->settingsSet('triwus', 'fechaultimopedido', $pedido->fecha);
        $appSettings->settingsSave();
    }
    /*
     * Update or add line orders from Triwus online shop
     */

    public function saveLineOrders(array $order, int $idpedido) {

        $pedido = new \FacturaScripts\Dinamic\Model\PedidoCliente();
        $linea = new \FacturaScripts\Dinamic\Model\LineaPedidoCliente();
        $producto = new \FacturaScripts\Dinamic\Model\Producto();
        $twProduct = new \FacturaScripts\Dinamic\Model\TwProduct();
        $variante = new \FacturaScripts\Dinamic\Model\Variante();
        $impuesto = new \FacturaScripts\Dinamic\Model\Impuesto();
        //~ $appSettings = new appSettings();
        $appSettings = new Tools();

        $twLines = $order['_detalle'];
//~ Tools::log()->notice("TriwusAPI.saveLineOrders(). ::" . print_r($twLines, 1)); 

        foreach ($twLines as $line) {
            $linea = new \FacturaScripts\Dinamic\Model\LineaPedidoCliente();
            $whereLinea = [
                //~ new DataBaseWhere('idpedido', $idpedido)
                //~ , new DataBaseWhere('twidlinea', $line['k_aux'])
                Where::eq('idpedido', $idpedido)
                , Where::eq('twidlinea', $line['k_aux'])
            ];
            $linea->loadFromCode('', $whereLinea);
            $whereProducto = [
                //~ new DataBaseWhere('twid', $line['id_articulo'])
                Where::eq('twid', $line['id_articulo'])
            ];
            if ($producto->loadFromCode('', $whereProducto)) {
                $whereVariante = [
                    //~ new DataBaseWhere('idproducto', $producto->idproducto)
                    Where::eq('idproducto', $producto->idproducto)
                ];
                if ($variante->loadFromCode('', $whereVariante)) {
                    $linea->referencia = $variante->referencia;
                }
                $linea->idproducto = $producto->idproducto;
                $linea->codimpuesto = $producto->codimpuesto;
                $impuesto->loadFromCode($producto->codimpuesto);
            } else {
                $impuesto->loadFromcode("IVA{$line["pive"]}");
            }
            $linea->iva         = $impuesto->iva;
            $linea->codimpuesto = $impuesto->codimpuesto;

            $linea->cantidad = $line['unidades'];
            $linea->descripcion = $line['nome'];
            $linea->idpedido = $idpedido;
            //$linea->pvpsindto = $line['tpvpive'] / (1 + ($linea->iva / 100));
            $linea->pvpunitario = $line['pvp'];
            $linea->dtopor = $line['desc_p'];
            $linea->pvptotal = $linea->pvpunitario * $linea->cantidad;
            $linea->pvpsindto = $linea->pvptotal;
            $linea->twidlinea = $line['k_aux'];

            $linea->save();

        }

        if (floatval($order['portes']) != 0) {
            $linea = new \FacturaScripts\Dinamic\Model\LineaPedidoCliente();
            $whereLinea = [
                //~ new DataBaseWhere('idpedido', $idpedido)
                //~ , new DataBaseWhere('twidlinea', 0)
                Where::eq('idpedido', $idpedido)
                , Where::eq('twidlinea', 0)
            ];
            $linea->loadFromCode('', $whereLinea);
            $idproductoportes = $appSettings->settings('triwus', 'idproducto');
            if ($producto->loadFromCode($idproductoportes)) {
                $whereVariante = [
                    //~ new DataBaseWhere('idproducto', $producto->idproducto)
                    Where::eq('idproducto', $producto->idproducto)
                ];
                if ($variante->loadFromCode('', $whereVariante)) {
                    $linea->referencia = $variante->referencia;
                }
                $linea->idproducto = $producto->idproducto;
                $linea->codimpuesto = $producto->codimpuesto;
                $impuesto->loadFromCode($producto->codimpuesto);
            } else {
                $impuesto->loadFromcode($appSettings->settings('default', 'codimpuesto'));
            }

            $linea->iva = $impuesto->iva;
            $importe = floatval($order['portes'] / (1 + ($impuesto->iva / 100)));
            $linea->codimpuesto = $impuesto->codimpuesto;

            $linea->cantidad = 1;
            $linea->descripcion = 'Gastos de envÃ­o';
            $linea->idpedido = $idpedido;
            $linea->pvpsindto = $importe;
            $linea->pvpunitario = $importe;
            $linea->pvptotal = $importe;
            $linea->twidlinea = 0;
            $linea->save();
        }

        if (floatval($order['cr']) != 0) {
            $linea = new \FacturaScripts\Dinamic\Model\LineaPedidoCliente();
            $whereLinea = [
                //~ new DataBaseWhere('idpedido', $idpedido)
                //~ , new DataBaseWhere('twidlinea', -1)
                Where::eq('idpedido', $idpedido)
                , Where::eq('twidlinea', -1)
            ];
            $linea->loadFromCode('', $whereLinea);
            $idproductoreembolso = $appSettings->settings('triwus', 'idproductoreembolso');
            if ($producto->loadFromCode($idproductoreembolso)) {
                $whereVariante = [
                    //~ new DataBaseWhere('idproducto', $producto->idproducto)
                    Where::eq('idproducto', $producto->idproducto);
                ];
                if ($variante->loadFromCode('', $whereVariante)) {
                    $linea->referencia = $variante->referencia;
                }
                $linea->idproducto = $producto->idproducto;
                $linea->codimpuesto = $producto->codimpuesto;
                $impuesto->loadFromCode($producto->codimpuesto);
            } else {
                $impuesto->loadFromcode("IVA21");
            }

            $linea->iva = $impuesto->iva;
            $importe = floatval($order['cr'] / (1 + ($impuesto->iva / 100)));
            $linea->codimpuesto = $impuesto->codimpuesto;

            $linea->cantidad = 1;
            $linea->descripcion = 'Contra Reembolso';
            $linea->idpedido = $idpedido;
            $linea->pvpsindto = $importe;
            $linea->pvpunitario = $importe;
            $linea->pvptotal = $importe;
            $linea->twidlinea = -1;
            $linea->save();
        }
    }

    /**
     * Sincronize products (get and put) for Triwus online shop
     */
    public function productSincronization() {
       // $appSettings = new AppSettings();

        $appSettings = new Tools();

      //  $lastDate = $appSettings->get('triwus', 'productlastupdate');
        $lastDate = $appSettings->settings('triwus', 'productlastupdate');

//~ ToolBox::i18nLog()->notice("TriwusAPI.productSincronization(). lastDate::" . $lastDate);

        $this->getTriwusProducts($lastDate); //* actualiza productos: Facturascripts <- Triwus.

        $this->updateTriwusProducts($lastDate); //* actualiza productos: Facturascripts -> Triwus.

        $hoy = new \DateTime;
        $appSettings->settingsSet('triwus', 'productlastupdate', $hoy->format('Y-m-d'));
        $appSettings->settingsSave();
    }

}

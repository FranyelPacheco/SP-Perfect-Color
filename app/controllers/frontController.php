<?php
namespace App\Controllers;

// FUNCIÓN: — (clase frontController)
// OBJETIVO: Enrutador principal — parsea la URL, carga el controlador correspondiente y define variables SEO (pageTitle, pageDescription)
// NOTA: Controlador forzado a minúsculas; solo caracteres a-z permitidos para prevenir path traversal
class frontController
{
    private $controlador;
    private $metodo;
    private $parametros;

    private $mapeoControladores = [
        'dashboard'    => 'dashboard',
        'cliente'      => 'cliente',
        'proveedor'    => 'proveedor',
        'inventario'   => 'inventario',
        'presupuesto'  => 'presupuesto',
        'notaentrega'  => 'notaEntrega',
        'cuentacobrar' => 'cuentaCobrar',
        'cuentapagar'  => 'cuentaPagar',
        'usuario'      => 'usuario',
        'login'        => 'login',
        'banco'        => 'banco',
        'tipopago'     => 'tipoPago',
        'reporte'      => 'reporte',
        'configpago'   => 'configPago',
    ];

    private $titulosPagina = [
        'dashboard'    => ['Panel de Control', 'Resumen general del sistema de gestión administrativa en SP Perfect Color'],
        'cliente'      => ['Clientes', 'Gestión de clientes - SP Perfect Color'],
        'proveedor'    => ['Proveedores', 'Gestión de proveedores - SP Perfect Color'],
        'inventario'   => ['Inventario', 'Control de inventario y existencias - SP Perfect Color'],
        'presupuesto'  => ['Presupuestos', 'Gestión de presupuestos y cotizaciones - SP Perfect Color'],
        'notaentrega'  => ['Notas de Entrega', 'Gestión de notas de entrega - SP Perfect Color'],
        'cuentacobrar' => ['Cuentas por Cobrar', 'Gestión de cuentas por cobrar - SP Perfect Color'],
        'cuentapagar'  => ['Cuentas por Pagar', 'Gestión de cuentas por pagar - SP Perfect Color'],
        'usuario'      => ['Usuarios', 'Gestión de usuarios - SP Perfect Color'],
        'login'        => ['Iniciar Sesión', 'Inicio de sesión - SP Perfect Color'],
        'banco'        => ['Bancos', 'Gestión de bancos - SP Perfect Color'],
        'tipopago'     => ['Tipos de Pago', 'Gestión de tipos de pago - SP Perfect Color'],
        'reporte'      => ['Reportes', 'Reportes de ventas, ingresos y egresos - SP Perfect Color'],
        'configpago'   => ['Config. de Pago', 'Configuración de bancos y tipos de pago - SP Perfect Color'],
    ];

    public function __construct()
    {
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $url = trim($url, '/');
        $partes = explode('/', $url);

        $controladorRaw = !empty($partes[0]) ? strtolower($partes[0]) : 'login';
        if (isset($this->mapeoControladores[$controladorRaw])) {
            $this->controlador = $this->mapeoControladores[$controladorRaw];
        } else {
            $this->controlador = preg_match('/^[a-z]+$/', $controladorRaw) ? $controladorRaw : 'login';
        }
        $this->metodo = !empty($partes[1]) ? $partes[1] : 'index';
        $this->parametros = array_slice($partes, 2);

        $this->disparador();
    }

    // FUNCIÓN: disparador (privado)
    // OBJETIVO: Incluye el archivo del controlador si existe; si no, redirige al login o muestra 404
    // NOTA: Las variables $controlador, $metodo, $parametros se heredan por alcance al hacer require_once
    private function disparador()
    {
        $rutaControlador = __DIR__ . '/' . $this->controlador . 'Controller.php';

        $tituloDefecto = 'SP Perfect Color - Sistema de Gestión';
        $descripcionDefecto = 'Sistema de gestión administrativa para SP Perfect Color';

        $claveTitulo = strtolower($this->controlador);
        if (isset($this->titulosPagina[$claveTitulo])) {
            $pageTitle = 'SP Perfect Color - ' . $this->titulosPagina[$claveTitulo][0];
            $pageDescription = $this->titulosPagina[$claveTitulo][1];
        } else {
            $pageTitle = $tituloDefecto;
            $pageDescription = $descripcionDefecto;
        }

        if (is_file($rutaControlador)) {
            $controlador = $this->controlador;
            $metodo = $this->metodo;
            $parametros = $this->parametros;

            require_once $rutaControlador;
            return;
        }

        if (!isset($_SESSION['id_usuario'])) {
            $controlador = 'login';
            $metodo = 'index';
            $parametros = [];
            $pageTitle = 'SP Perfect Color - Iniciar Sesión';
            $pageDescription = 'Inicio de sesión - SP Perfect Color';

            require_once __DIR__ . '/loginController.php';
            return;
        }

        $pageTitle = 'Página no encontrada - SP Perfect Color';
        $pageDescription = 'La página solicitada no está disponible - SP Perfect Color';
        require_once __DIR__ . '/../views/error404View.php';
    }
}

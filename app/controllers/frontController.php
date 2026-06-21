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

    private $titulosPagina = [
        'dashboard'    => ['Panel de Control', 'Resumen general del sistema de gestión administrativa en SP Perfect Color'],
        'cliente'      => ['Clientes', 'Gestión de clientes - SP Perfect Color'],
        'proveedor'    => ['Proveedores', 'Gestión de proveedores - SP Perfect Color'],
        'inventario'   => ['Inventario', 'Control de inventario y existencias - SP Perfect Color'],
        'presupuesto'  => ['Presupuestos', 'Gestión de presupuestos y cotizaciones - SP Perfect Color'],
        'notaEntrega'  => ['Notas de Entrega', 'Gestión de notas de entrega - SP Perfect Color'],
        'cuentaCobrar' => ['Cuentas por Cobrar', 'Gestión de cuentas por cobrar - SP Perfect Color'],
        'cuentaPagar'  => ['Cuentas por Pagar', 'Gestión de cuentas por pagar - SP Perfect Color'],
        'usuario'      => ['Usuarios', 'Gestión de usuarios - SP Perfect Color'],
        'login'        => ['Iniciar Sesión', 'Inicio de sesión - SP Perfect Color'],
        'banco'        => ['Bancos', 'Gestión de bancos - SP Perfect Color'],
        'tipoPago'     => ['Tipos de Pago', 'Gestión de tipos de pago - SP Perfect Color'],
        'reporte'      => ['Reportes', 'Reportes de ventas, ingresos y egresos - SP Perfect Color'],
    ];

    public function __construct()
    {
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $url = trim($url, '/');
        $partes = explode('/', $url);

        $controladorRaw = !empty($partes[0]) ? strtolower($partes[0]) : 'login';
        $this->controlador = preg_match('/^[a-z]+$/', $controladorRaw) ? $controladorRaw : 'login';
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

        if (isset($this->titulosPagina[$this->controlador])) {
            $pageTitle = 'SP Perfect Color - ' . $this->titulosPagina[$this->controlador][0];
            $pageDescription = $this->titulosPagina[$this->controlador][1];
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

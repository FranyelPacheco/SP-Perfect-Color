<?php
namespace App\Controllers;

class frontController
{
    private $controlador;
    private $metodo;
    private $parametros;

    private $titulosPagina = [
        'dashboard'    => ['Panel de Control', 'Resumen general del sistema de gestión SP Perfect Color'],
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

        // Forzamos el nombre del controlador a minúsculas para evitar caídas en Linux
        $this->controlador = !empty($partes[0]) ? strtolower($partes[0]) : 'login';
        $this->metodo = !empty($partes[1]) ? $partes[1] : 'index';
        $this->parametros = array_slice($partes, 2);

        $this->disparador();
    }

    private function disparador()
    {
        $rutaControlador = __DIR__ . '/' . $this->controlador . 'Controller.php';

        // Variables SEO por defecto (los controladores pueden sobrescribirlas)
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

            // Al requerir el archivo aquí, hereda las variables locales ($controlador, $metodo, $parametros)
            require_once $rutaControlador;
            return;
        }

        // Si el archivo no existe pero no hay sesión activa, redirige al login por defecto
        if (!isset($_SESSION['usuario_id'])) {
            $controlador = 'login';
            $metodo = 'index';
            $parametros = [];
            $pageTitle = 'SP Perfect Color - Iniciar Sesión';
            $pageDescription = 'Inicio de sesión - SP Perfect Color';

            require_once __DIR__ . '/loginController.php';
            return;
        }

        // Si está logueado pero la ruta está mala, muestra el error 404
        $pageTitle = 'Página no encontrada - SP Perfect Color';
        $pageDescription = 'La página solicitada no está disponible - SP Perfect Color';
        require_once __DIR__ . '/../views/error404View.php';
    }
}
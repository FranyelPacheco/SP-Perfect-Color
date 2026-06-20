<?php
namespace App\Controllers;

class frontController
{
    private $controlador;
    private $metodo;
    private $parametros;

    private $titulosPagina = [
        'dashboard'    => ['Panel de Control', 'Resumen general del sistema de gestiÃ³n SP Perfect Color'],
        'cliente'      => ['Clientes', 'GestiÃ³n de clientes - SP Perfect Color'],
        'proveedor'    => ['Proveedores', 'GestiÃ³n de proveedores - SP Perfect Color'],
        'inventario'   => ['Inventario', 'Control de inventario y existencias - SP Perfect Color'],
        'presupuesto'  => ['Presupuestos', 'GestiÃ³n de presupuestos y cotizaciones - SP Perfect Color'],
        'notaEntrega'  => ['Notas de Entrega', 'GestiÃ³n de notas de entrega - SP Perfect Color'],
        'cuentaCobrar' => ['Cuentas por Cobrar', 'GestiÃ³n de cuentas por cobrar - SP Perfect Color'],
        'cuentaPagar'  => ['Cuentas por Pagar', 'GestiÃ³n de cuentas por pagar - SP Perfect Color'],
        'usuario'      => ['Usuarios', 'GestiÃ³n de usuarios - SP Perfect Color'],
        'login'        => ['Iniciar SesiÃ³n', 'Inicio de sesiÃ³n - SP Perfect Color'],
        'banco'        => ['Bancos', 'GestiÃ³n de bancos - SP Perfect Color'],
        'tipoPago'     => ['Tipos de Pago', 'GestiÃ³n de tipos de pago - SP Perfect Color'],
        'reporte'      => ['Reportes', 'Reportes de ventas, ingresos y egresos - SP Perfect Color'],
    ];

    public function __construct()
    {
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        $url = trim($url, '/');
        $partes = explode('/', $url);

        // Forzamos el nombre del controlador a minÃºsculas para evitar caÃ­das en Linux
        $this->controlador = !empty($partes[0]) ? strtolower($partes[0]) : 'login';
        $this->metodo = !empty($partes[1]) ? $partes[1] : 'index';
        $this->parametros = array_slice($partes, 2);

        $this->disparador();
    }

    private function disparador()
    {
        $rutaControlador = __DIR__ . '/' . $this->controlador . 'Controller.php';

        // Variables SEO por defecto (los controladores pueden sobrescribirlas)
        $tituloDefecto = 'SP Perfect Color - Sistema de GestiÃ³n';
        $descripcionDefecto = 'Sistema de gestiÃ³n administrativa para SP Perfect Color';

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

            // Al requerir el archivo aquÃ­, hereda las variables locales ($controlador, $metodo, $parametros)
            require_once $rutaControlador;
            return;
        }

        // Si el archivo no existe pero no hay sesiÃ³n activa, redirige al login por defecto
        if (!isset($_SESSION['id_usuario'])) {
            $controlador = 'login';
            $metodo = 'index';
            $parametros = [];
            $pageTitle = 'SP Perfect Color - Iniciar SesiÃ³n';
            $pageDescription = 'Inicio de sesiÃ³n - SP Perfect Color';

            require_once __DIR__ . '/loginController.php';
            return;
        }

        // Si estÃ¡ logueado pero la ruta estÃ¡ mala, muestra el error 404
        $pageTitle = 'PÃ¡gina no encontrada - SP Perfect Color';
        $pageDescription = 'La pÃ¡gina solicitada no estÃ¡ disponible - SP Perfect Color';
        require_once __DIR__ . '/../views/error404View.php';
    }
}
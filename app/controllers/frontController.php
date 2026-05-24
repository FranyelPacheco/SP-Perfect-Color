<?php
class frontController
{
    private $controlador;
    private $metodo;
    private $parametros;

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

            require_once __DIR__ . '/loginController.php';
            return;
        }

        // Si está logueado pero la ruta está mala, muestra el error 404
        require_once __DIR__ . '/../views/error404View.php';
    }
}
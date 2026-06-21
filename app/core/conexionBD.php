<?php
// ARCHIVO: ConexionBD.php
// OBJETIVO: Implementar el patrón Singleton para la conexión PDO a MySQL

namespace App\Core;

use \PDO;

class ConexionBD
{
    private static $instancia = null;
    private $conexion;

    private $host = 'localhost';
    private $baseDatos = 'sp_perfect_color';
    private $usuario = 'root';
    private $clave = '';
    private $charset = 'utf8mb4';

    // FUNCIÓN: __construct (privado)
    // OBJETIVO: Crear la conexión PDO con las opciones configuradas y el collation utf8mb4_spanish2_ci
    // NOTA: El constructor es privado para evitar instancias externas (Singleton)
    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->baseDatos};charset={$this->charset}";

        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->conexion = new PDO($dsn, $this->usuario, $this->clave, $opciones);
        $this->conexion->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish2_ci'");
    }

    // FUNCIÓN: obtenerInstancia
    // OBJETIVO: Devolver la única instancia de la conexión, creándola si aún no existe
    public static function obtenerInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    // FUNCIÓN: obtenerConexion
    // OBJETIVO: Devolver el objeto PDO interno para realizar consultas
    public function obtenerConexion()
    {
        return $this->conexion;
    }

    // FUNCIÓN: __clone (privado)
    // OBJETIVO: Evitar que la instancia Singleton sea clonada
    private function __clone() {}
}

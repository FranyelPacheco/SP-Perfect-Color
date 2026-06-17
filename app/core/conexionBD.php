<?php
// Archivo: ConexionBD.php
// Clase para manejar la conexion a la base de datos con PDO (Singleton)

namespace App\Core;

use \PDO;

class ConexionBD
{
    private static $instancia = null;
    private $conexion;

    // Datos de conexion (ajustar segun configuracion de XAMPP)
    private $host = 'localhost';
    private $baseDatos = 'sp_perfect_color';
    private $usuario = 'root';
    private $clave = '';
    private $charset = 'utf8mb4';

    // Constructor privado para evitar instancias externas
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

    // Obtener la unica instancia de la conexion
    public static function obtenerInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    // Obtener el objeto PDO para consultas
    public function obtenerConexion()
    {
        return $this->conexion;
    }

    // Evitar clonacion del objeto
    private function __clone() {}
}
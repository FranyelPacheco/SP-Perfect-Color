<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\ConexionBD;
use PDO;

abstract class ModeloBase
{
    protected PDO $conexion;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD vía ConexionBD singleton
    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }
}

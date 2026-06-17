<?php

namespace App\Models;

use App\Core\ConexionBD;
use \PDO;

class BancoModel
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = ConexionBD::obtenerInstancia()->obtenerConexion();
    }

    public function listarTodos()
    {
        return $this->_listarTodos();
    }

    private function _listarTodos()
    {
        $consulta = "SELECT id_banco, nombre, activo FROM banco ORDER BY nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        return $this->_buscarPorId($id);
    }

    private function _buscarPorId($id)
    {
        $consulta = "SELECT id_banco, nombre, activo FROM banco WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function insertar($nombre)
    {
        return $this->_insertar($nombre);
    }

    private function _insertar($nombre)
    {
        $consulta = "INSERT INTO banco (nombre, activo) VALUES (:nombre, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function actualizar($id, $nombre, $activo)
    {
        return $this->_actualizar($id, $nombre, $activo);
    }

    private function _actualizar($id, $nombre, $activo)
    {
        $consulta = "UPDATE banco SET nombre = :nombre, activo = :activo WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function eliminar($id)
    {
        return $this->_eliminar($id);
    }

    private function _eliminar($id)
    {
        $consulta = "UPDATE banco SET activo = 0 WHERE id_banco = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

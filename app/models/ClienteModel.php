<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ClienteModel extends ModeloBase
{
    private ?int $id;
    private ?string $cedula;
    private ?string $nombres;
    private ?string $apellidos;
    private ?string $correo;
    private ?string $direccion;
    private ?int $idExcluir;
    private ?string $termino;
    private ?int $clienteId;
    private ?string $telefono;
    private ?string $tipo;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: contarTodos
    // OBJETIVO: Devuelve la cantidad de clientes activos
    public function contarTodos(): int
    {
        return $this->_ejecutarCountAll();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los clientes activos con sus teléfonos
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un cliente activo por su ID, incluye teléfonos
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;

        if ($this->id < 1) return false;

        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: insertarCliente
    // OBJETIVO: Crea un nuevo cliente; retorna el ID insertado o false
    public function insertarCliente(string $cedula, string $nombres, string $apellidos, ?string $correo = null, ?string $direccion = null): int|false
    {
        $this->cedula = $cedula;
        $this->nombres = $nombres;
        $this->apellidos = $apellidos;
        $this->correo = $correo;
        $this->direccion = $direccion;

        if ($this->cedula === '' || $this->nombres === '' || $this->apellidos === '') return false;

        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: actualizarCliente
    // OBJETIVO: Actualiza los datos de un cliente existente y lo marca activo
    public function actualizarCliente(int $id, string $cedula, string $nombres, string $apellidos, ?string $correo = null, ?string $direccion = null): bool
    {
        $this->id = $id;
        $this->cedula = $cedula;
        $this->nombres = $nombres;
        $this->apellidos = $apellidos;
        $this->correo = $correo;
        $this->direccion = $direccion;

        if ($this->id < 1 || $this->cedula === '' || $this->nombres === '' || $this->apellidos === '') return false;

        return $this->_ejecutarUpdate();
    }

    // FUNCIÓN: eliminarCliente
    // OBJETIVO: Soft-delete; bloquea si hay CxC pendientes
    public function eliminarCliente(int $id): bool
    {
        $this->id = $id;

        if ($this->id < 1) return false;

        $consulta = "SELECT COUNT(*) as total FROM cuentas_cobrar WHERE id_cliente = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetch()['total'] > 0) return false;

        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: insertarTelefono
    // OBJETIVO: Inserta un teléfono asociado a un cliente
    public function insertarTelefono(int $clienteId, string $telefono, ?string $tipo = null): bool
    {
        $this->clienteId = $clienteId;
        $this->telefono = $telefono;
        $this->tipo = $tipo;

        if ($this->clienteId < 1 || $this->telefono === '') return false;

        return $this->_ejecutarInsertTelefono();
    }

    // FUNCIÓN: eliminarTelefonos
    // OBJETIVO: Elimina todos los teléfonos de un cliente (reemplazo completo)
    public function eliminarTelefonos(int $clienteId): bool
    {
        $this->clienteId = $clienteId;

        if ($this->clienteId < 1) return false;

        return $this->_ejecutarDeleteTelefonos();
    }

    // FUNCIÓN: cedulaExiste
    // OBJETIVO: Verifica si una cédula ya está registrada (opcionalmente excluye un ID)
    public function cedulaExiste(string $cedula, ?int $idExcluir = null): bool
    {
        $this->cedula = $cedula;
        $this->idExcluir = $idExcluir;

        if ($this->cedula === '') return false;

        return $this->_ejecutarCheckCedula();
    }

    // FUNCIÓN: buscarInactivoPorCedula
    // OBJETIVO: Busca un cliente inactivo por cédula para reactivación
    public function buscarInactivoPorCedula(string $cedula): int|false
    {
        $this->cedula = $cedula;

        if ($this->cedula === '') return false;

        return $this->_ejecutarBuscarInactivo();
    }

    // FUNCIÓN: buscarClientes
    // OBJETIVO: Busca clientes activos por nombre, apellido o cédula (autocompletado)
    public function buscarClientes(string $termino): array
    {
        $this->termino = trim($termino);

        if ($this->termino === '') return [];

        return $this->_ejecutarSearch();
    }

    private function _ejecutarCountAll(): int
    {
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE activo = 1";
        $stmt = $this->conexion->query($consulta);
        return (int)$stmt->fetch()['total'];
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.activo = 1
                     GROUP BY c.id_cliente
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.id_cliente = :id AND c.activo = 1
                     GROUP BY c.id_cliente LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function _ejecutarInsert(): int|false
    {
        $consulta = "INSERT INTO clientes (cedula, nombres, apellidos, correo, direccion)
                     VALUES (:cedula, :nombres, :apellidos, :correo, :direccion)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $this->nombres, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $this->apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        if ($stmt->execute()) return (int)$this->conexion->lastInsertId();
        return false;
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE clientes
                     SET cedula = :cedula, nombres = :nombres, apellidos = :apellidos,
                         correo = :correo, direccion = :direccion, activo = 1
                     WHERE id_cliente = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $this->nombres, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $this->apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE clientes SET activo = 0 WHERE id_cliente = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarInsertTelefono(): bool
    {
        $consulta = "INSERT INTO telefono_cliente (id_cliente, telefono, tipo) VALUES (:id_cliente, :telefono, :tipo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_cliente', $this->clienteId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $this->telefono, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $this->tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    private function _ejecutarDeleteTelefonos(): bool
    {
        $consulta = "DELETE FROM telefono_cliente WHERE id_cliente = :id_cliente";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_cliente', $this->clienteId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarCheckCedula(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE cedula = :cedula AND activo = 1";
        if ($this->idExcluir !== null) {
            $consulta .= " AND id_cliente != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
        if ($this->idExcluir !== null) {
            $stmt->bindParam(':id', $this->idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch()['total'] > 0;
    }

    private function _ejecutarBuscarInactivo(): int|false
    {
        $consulta = "SELECT id_cliente FROM clientes WHERE cedula = :cedula AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_cliente'] : false;
    }

    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT c.*, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.activo = 1 AND (c.nombres LIKE :termino1
                         OR c.apellidos LIKE :termino2
                         OR c.cedula LIKE :termino3)
                     GROUP BY c.id_cliente
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino3', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

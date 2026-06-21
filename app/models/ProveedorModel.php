<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class ProveedorModel extends ModeloBase
{
    private ?int $id;
    private ?string $rif;
    private ?string $nombreEmpresa;
    private ?string $direccion;
    private ?string $contacto;
    private ?string $correo;
    private ?int $idExcluir;
    private ?string $termino;
    private ?int $proveedorId;
    private ?string $telefono;
    private ?string $tipo;
    private ?int $rubroId;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: contarTodos
    // OBJETIVO: Devuelve la cantidad de proveedores activos
    public function contarTodos(): int
    {
        return $this->_ejecutarCountAll();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los proveedores activos con teléfonos y rubros
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un proveedor activo por su ID, incluye teléfonos y rubros
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;

        if ($this->id < 1) return false;

        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: insertarProveedor
    // OBJETIVO: Crea un nuevo proveedor; retorna el ID insertado o false
    public function insertarProveedor(string $rif, string $nombreEmpresa, ?string $direccion = null, ?string $contacto = null, ?string $correo = null): int|false
    {
        $this->rif = $rif;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->direccion = $direccion;
        $this->contacto = $contacto;
        $this->correo = $correo;

        if ($this->rif === '' || $this->nombreEmpresa === '') return false;

        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: actualizarProveedor
    // OBJETIVO: Actualiza los datos de un proveedor existente y lo marca activo
    public function actualizarProveedor(int $id, string $rif, string $nombreEmpresa, ?string $direccion = null, ?string $contacto = null, ?string $correo = null): bool
    {
        $this->id = $id;
        $this->rif = $rif;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->direccion = $direccion;
        $this->contacto = $contacto;
        $this->correo = $correo;

        if ($this->id < 1 || $this->rif === '' || $this->nombreEmpresa === '') return false;

        return $this->_ejecutarUpdate();
    }

    // FUNCIÓN: eliminarProveedor
    // OBJETIVO: Soft-delete; bloquea si hay CxP pendientes o insumos asociados
    public function eliminarProveedor(int $id): bool
    {
        $this->id = $id;

        if ($this->id < 1) return false;

        $consulta = "SELECT COUNT(*) as total FROM cuentas_pagar WHERE id_proveedor = :id AND estado = 'pendiente'";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetch()['total'] > 0) return false;

        $consulta = "SELECT COUNT(*) as total FROM insumo_proveedor WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetch()['total'] > 0) return false;

        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: rifExiste
    // OBJETIVO: Verifica si un RIF ya está registrado (opcionalmente excluye un ID)
    public function rifExiste(string $rif, ?int $idExcluir = null): bool
    {
        $this->rif = $rif;
        $this->idExcluir = $idExcluir;

        if ($this->rif === '') return false;

        return $this->_ejecutarCheckRif();
    }

    // FUNCIÓN: buscarInactivoPorRIF
    // OBJETIVO: Busca un proveedor inactivo por RIF para reactivación
    public function buscarInactivoPorRIF(string $rif): int|false
    {
        $this->rif = $rif;

        if ($this->rif === '') return false;

        return $this->_ejecutarBuscarInactivo();
    }

    // FUNCIÓN: buscarProveedores
    // OBJETIVO: Busca proveedores activos por nombre de empresa o RIF (autocompletado)
    public function buscarProveedores(string $termino): array
    {
        $this->termino = trim($termino);

        if ($this->termino === '') return [];

        return $this->_ejecutarSearch();
    }

    // FUNCIÓN: insertarTelefono
    // OBJETIVO: Inserta un teléfono asociado a un proveedor
    public function insertarTelefono(int $proveedorId, string $telefono, ?string $tipo = null): bool
    {
        $this->proveedorId = $proveedorId;
        $this->telefono = $telefono;
        $this->tipo = $tipo;

        if ($this->proveedorId < 1 || $this->telefono === '') return false;

        return $this->_ejecutarInsertTelefono();
    }

    // FUNCIÓN: eliminarTelefonos
    // OBJETIVO: Elimina todos los teléfonos de un proveedor (reemplazo completo)
    public function eliminarTelefonos(int $proveedorId): bool
    {
        $this->proveedorId = $proveedorId;

        if ($this->proveedorId < 1) return false;

        return $this->_ejecutarDeleteTelefonos();
    }

    // FUNCIÓN: insertarRubro
    // OBJETIVO: Asigna un rubro a un proveedor
    public function insertarRubro(int $proveedorId, int $rubroId): bool
    {
        $this->proveedorId = $proveedorId;
        $this->rubroId = $rubroId;

        if ($this->proveedorId < 1 || $this->rubroId < 1) return false;

        return $this->_ejecutarInsertRubro();
    }

    // FUNCIÓN: eliminarRubros
    // OBJETIVO: Elimina todos los rubros de un proveedor (reemplazo completo)
    public function eliminarRubros(int $proveedorId): bool
    {
        $this->proveedorId = $proveedorId;

        if ($this->proveedorId < 1) return false;

        return $this->_ejecutarDeleteRubros();
    }

    private function _ejecutarCountAll(): int
    {
        $consulta = "SELECT COUNT(*) as total FROM proveedores WHERE activo = 1";
        $stmt = $this->conexion->query($consulta);
        return (int)$stmt->fetch()['total'];
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.activo = 1
                     GROUP BY p.id_proveedor
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.id_proveedor = :id AND p.activo = 1
                     GROUP BY p.id_proveedor LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function _ejecutarInsert(): int|false
    {
        $consulta = "INSERT INTO proveedores (rif, nombre_empresa, direccion, contacto, correo)
                     VALUES (:rif, :nombre_empresa, :direccion, :contacto, :correo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $this->nombreEmpresa, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(':contacto', $this->contacto, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        if ($stmt->execute()) return (int)$this->conexion->lastInsertId();
        return false;
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE proveedores
                     SET rif = :rif, nombre_empresa = :nombre_empresa, direccion = :direccion,
                         contacto = :contacto, correo = :correo, activo = 1
                     WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $this->nombreEmpresa, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(':contacto', $this->contacto, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE proveedores SET activo = 0 WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarCheckRif(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM proveedores WHERE rif = :rif AND activo = 1";
        if ($this->idExcluir !== null) {
            $consulta .= " AND id_proveedor != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        if ($this->idExcluir !== null) {
            $stmt->bindParam(':id', $this->idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch()['total'] > 0;
    }

    private function _ejecutarBuscarInactivo(): int|false
    {
        $consulta = "SELECT id_proveedor FROM proveedores WHERE rif = :rif AND activo = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_proveedor'] : false;
    }

    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT p.*,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telf_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.activo = 1 AND (p.nombre_empresa LIKE :termino1
                         OR p.rif LIKE :termino2)
                     GROUP BY p.id_proveedor
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function _ejecutarInsertTelefono(): bool
    {
        $consulta = "INSERT INTO telf_proveedor (id_proveedor, telefono, tipo) VALUES (:id_proveedor, :telefono, :tipo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $this->proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $this->telefono, PDO::PARAM_STR);
        $stmt->bindParam(':tipo', $this->tipo, PDO::PARAM_STR);
        return $stmt->execute();
    }

    private function _ejecutarDeleteTelefonos(): bool
    {
        $consulta = "DELETE FROM telf_proveedor WHERE id_proveedor = :id_proveedor";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $this->proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarInsertRubro(): bool
    {
        $consulta = "INSERT INTO rubro_proveedor (id_proveedor, id_rubro) VALUES (:id_proveedor, :id_rubro)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $this->proveedorId, PDO::PARAM_INT);
        $stmt->bindParam(':id_rubro', $this->rubroId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarDeleteRubros(): bool
    {
        $consulta = "DELETE FROM rubro_proveedor WHERE id_proveedor = :id_proveedor";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_proveedor', $this->proveedorId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

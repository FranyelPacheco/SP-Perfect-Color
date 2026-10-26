<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\ContactoTrait;
use PDO;

class ProveedorModel extends ModeloBase
{
    use ContactoTrait;

    protected function tablaContacto(): string { return 'proveedores'; }
    protected function pkContacto(): string { return 'id_proveedor'; }
    protected function tablaTelefonos(): string { return 'telefono_proveedor'; }
    protected function etiquetaContacto(): string { return 'proveedor'; }

    private ?int $id;
    private ?string $rif;
    private ?string $nombreEmpresa;
    private ?string $direccion;
    private ?string $contacto;
    private ?string $correo;
    private ?int $idExcluir;
    private ?string $termino;
    private ?int $proveedorId;
    private ?int $rubroId;

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

    // FUNCIÓN: procesarRifPayload
    // OBJETIVO: Compone el RIF en mayúsculas desde el arreglo de datos
    private function procesarRifPayload(array $datos): string
    {
        $tipoRif = strtoupper(trim($datos['tipo_rif'] ?? 'J'));
        $rifNum = trim($datos['rif_num'] ?? '');
        $rif = strtoupper(trim($datos['rif'] ?? ''));
        if (empty($rif) && !empty($rifNum)) {
            $rif = $tipoRif . '-' . $rifNum;
        }
        return $rif;
    }

    // FUNCIÓN: validarDatosProveedor
    // OBJETIVO: Valida campos y reglas de negocio para un proveedor
    public function validarDatosProveedor(array $datos, ?int $idActual = null): array
    {
        $rif = $this->procesarRifPayload($datos);
        $nombreEmpresa = trim($datos['nombre_empresa'] ?? '');
        $direccion = trim($datos['direccion'] ?? '');
        $correo = trim($datos['correo'] ?? '');
        $rubros = $datos['rubros'] ?? [];
        if (!is_array($rubros)) {
            $rubros = !empty($rubros) ? [$rubros] : [];
        }
        [$telefono, $telefono2] = $this->procesarTelefonosPayload($datos);

        if ($rif === '') {
            return ['valido' => false, 'mensaje' => 'El RIF es obligatorio'];
        }
        if (!preg_match('/^[JVEG]-\d{1,9}$/', $rif)) {
            return ['valido' => false, 'mensaje' => 'El RIF debe tener formato valido (Ej: J-123456789)'];
        }

        if ($nombreEmpresa === '') {
            return ['valido' => false, 'mensaje' => 'El nombre de la empresa es obligatorio'];
        }
        if (mb_strlen($nombreEmpresa) < 3 || mb_strlen($nombreEmpresa) > 60) {
            return ['valido' => false, 'mensaje' => 'El nombre de la empresa debe tener entre 3 y 60 caracteres'];
        }
        if (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,\-&]+$/u', $nombreEmpresa)) {
            return ['valido' => false, 'mensaje' => 'El nombre de la empresa contiene caracteres no permitidos (solo letras, números y signos . , - &)'];
        }

        if ($correo === '') {
            return ['valido' => false, 'mensaje' => 'El correo electrónico es obligatorio'];
        }
        if (!$this->validarCorreo($correo)) {
            return ['valido' => false, 'mensaje' => 'El correo electrónico no es válido'];
        }
        if ($this->correoExiste($correo, $idActual)) {
            return ['valido' => false, 'mensaje' => ($idActual ? 'Ya existe otro proveedor con ese correo electrónico' : 'Ya existe un proveedor con ese correo electrónico')];
        }

        $error = $this->validarTelefonos($telefono, $telefono2, $idActual);
        if ($error !== null) {
            return ['valido' => false, 'mensaje' => $error];
        }

        if ($this->rifExiste($rif, $idActual)) {
            return ['valido' => false, 'mensaje' => ($idActual ? 'Ya existe otro proveedor con ese RIF' : 'Ya existe un proveedor con ese RIF')];
        }

        return [
            'valido' => true,
            'datos' => [
                'rif' => $rif,
                'nombre_empresa' => $nombreEmpresa,
                'direccion' => $direccion,
                'correo' => $correo,
                'telefono' => $telefono,
                'telefono2' => $telefono2,
                'rubros' => $rubros
            ]
        ];
    }

    // FUNCIÓN: registrar
    // OBJETIVO: Valida y registra o reactiva un proveedor junto con sus teléfonos y rubros de forma atómica
    public function registrar(array $datos): array
    {
        $val = $this->validarDatosProveedor($datos, null);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        $d = $val['datos'];

        try {
            $this->conexion->beginTransaction();

            $inactivoId = $this->buscarInactivoPorRIF($d['rif']);
            if ($inactivoId) {
                if (!$this->actualizarProveedor($inactivoId, $d['rif'], $d['nombre_empresa'], $d['direccion'], null, $d['correo'])) {
                    $this->conexion->rollBack();
                    return ['exito' => false, 'mensaje' => 'Error al reactivar el proveedor'];
                }

                $this->eliminarTelefonos($inactivoId);
                $this->insertarTelefono($inactivoId, $d['telefono'], 'movil');
                if (!empty($d['telefono2'])) {
                    $this->insertarTelefono($inactivoId, $d['telefono2'], 'movil');
                }

                $this->eliminarRubros($inactivoId);
                foreach ($d['rubros'] as $rubroId) {
                    $this->insertarRubro($inactivoId, (int)$rubroId);
                }

                $this->conexion->commit();
                return ['exito' => true, 'mensaje' => 'Proveedor reactivado exitosamente'];
            }

            $nuevoId = $this->insertarProveedor($d['rif'], $d['nombre_empresa'], $d['direccion'], null, $d['correo']);
            if (!$nuevoId) {
                $this->conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Error al crear el proveedor'];
            }

            $this->insertarTelefono($nuevoId, $d['telefono'], 'movil');
            if (!empty($d['telefono2'])) {
                $this->insertarTelefono($nuevoId, $d['telefono2'], 'movil');
            }

            foreach ($d['rubros'] as $rubroId) {
                $this->insertarRubro($nuevoId, (int)$rubroId);
            }

            $this->conexion->commit();
            return ['exito' => true, 'mensaje' => 'Proveedor creado exitosamente'];
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => 'Error en base de datos: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: modificar
    // OBJETIVO: Valida y actualiza los datos, teléfonos y rubros de un proveedor existente
    public function modificar(int $id, array $datos): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de proveedor no valido'];
        }

        $val = $this->validarDatosProveedor($datos, $id);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        $d = $val['datos'];

        try {
            $this->conexion->beginTransaction();

            if (!$this->actualizarProveedor($id, $d['rif'], $d['nombre_empresa'], $d['direccion'], null, $d['correo'])) {
                $this->conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Error al actualizar el proveedor'];
            }

            $this->eliminarTelefonos($id);
            $this->insertarTelefono($id, $d['telefono'], 'movil');
            if (!empty($d['telefono2'])) {
                $this->insertarTelefono($id, $d['telefono2'], 'movil');
            }

            $this->eliminarRubros($id);
            foreach ($d['rubros'] as $rubroId) {
                $this->insertarRubro($id, (int)$rubroId);
            }

            $this->conexion->commit();
            return ['exito' => true, 'mensaje' => 'Proveedor actualizado exitosamente'];
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => 'Error en base de datos: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: eliminar
    // OBJETIVO: Valida que el proveedor no tenga cuentas pendientes ni insumos y ejecuta soft-delete
    public function eliminar(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de proveedor no valido'];
        }

        if ($this->eliminarProveedor($id)) {
            return ['exito' => true, 'mensaje' => 'Proveedor eliminado exitosamente'];
        }

        return ['exito' => false, 'mensaje' => 'No se puede eliminar el proveedor. Tiene cuentas por pagar pendientes o productos asociados.'];
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

        $consulta = "SELECT COUNT(*) as total 
                     FROM cuentas_pagar cp
                     INNER JOIN nota_entrega ne ON cp.id_nota_entrega = ne.id_nota_entrega
                     WHERE ne.id_proveedor = :id AND cp.situacion = 'pendiente' AND cp.estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetch()['total'] > 0) return false;

        $consulta = "SELECT COUNT(*) as total FROM producto_proveedor WHERE id_proveedor = :id";
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
        $consulta = "SELECT COUNT(*) as total FROM proveedores WHERE estado = 1";
        $stmt = $this->conexion->query($consulta);
        return (int)$stmt->fetch()['total'];
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT p.*, p.estado as activo,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telefono_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.estado = 1
                     GROUP BY p.id_proveedor
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT p.*, p.estado as activo,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telefono_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.id_proveedor = :id AND p.estado = 1
                     GROUP BY p.id_proveedor LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function _ejecutarInsert(): int|false
    {
        $consulta = "INSERT INTO proveedores (rif, nombre_empresa, direccion, correo, estado)
                     VALUES (:rif, :nombre_empresa, :direccion, :correo, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $this->nombreEmpresa, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        if ($stmt->execute()) return (int)$this->conexion->lastInsertId();
        return false;
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE proveedores
                     SET rif = :rif, nombre_empresa = :nombre_empresa, direccion = :direccion,
                         correo = :correo, estado = 1
                     WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        $stmt->bindParam(':nombre_empresa', $this->nombreEmpresa, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE proveedores SET estado = 0 WHERE id_proveedor = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarCheckRif(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM proveedores WHERE rif = :rif AND estado = 1";
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
        $consulta = "SELECT id_proveedor FROM proveedores WHERE rif = :rif AND estado = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':rif', $this->rif, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_proveedor'] : false;
    }

    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT p.*, p.estado as activo,
                            GROUP_CONCAT(DISTINCT tp.telefono SEPARATOR ', ') as telefonos,
                            GROUP_CONCAT(DISTINCT r.nombre SEPARATOR ', ') as rubros,
                            GROUP_CONCAT(DISTINCT rp.id_rubro SEPARATOR ',') as rubros_id
                     FROM proveedores p
                     LEFT JOIN telefono_proveedor tp ON tp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro_proveedor rp ON rp.id_proveedor = p.id_proveedor
                     LEFT JOIN rubro r ON rp.id_rubro = r.id_rubro
                     WHERE p.estado = 1 AND (p.nombre_empresa LIKE :termino1
                         OR p.rif LIKE :termino2)
                     GROUP BY p.id_proveedor
                     ORDER BY p.nombre_empresa ASC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':termino1', $terminoLike, PDO::PARAM_STR);
        $stmt->bindParam(':termino2', $terminoLike, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll();
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

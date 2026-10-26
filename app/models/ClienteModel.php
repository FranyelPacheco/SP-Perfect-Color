<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\ContactoTrait;
use PDO;

class ClienteModel extends ModeloBase
{
    use ContactoTrait;

    protected function tablaContacto(): string { return 'clientes'; }
    protected function pkContacto(): string { return 'id_cliente'; }
    protected function tablaTelefonos(): string { return 'telefono_cliente'; }
    protected function etiquetaContacto(): string { return 'cliente'; }

    private ?int $id;
    private ?string $cedula;
    private ?string $nombres;
    private ?string $apellidos;
    private ?string $correo;
    private ?string $direccion;
    private ?int $idExcluir;
    private ?string $termino;

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

    // FUNCIÓN: procesarCedulaPayload
    // OBJETIVO: Compone la cédula con formato V/E/J-12345678 desde el arreglo de datos
    private function procesarCedulaPayload(array $datos): string
    {
        $tipoDoc = trim($datos['tipo_doc'] ?? 'V');
        $cedulaNum = trim($datos['cedula_num'] ?? '');
        $cedula = trim($datos['cedula'] ?? '');
        if (empty($cedula) && !empty($cedulaNum)) {
            $cedula = $tipoDoc . '-' . $cedulaNum;
        }
        return $cedula;
    }

    // FUNCIÓN: validarDatosCliente
    // OBJETIVO: Ejecuta todas las validaciones de campos y reglas de negocio para un cliente
    public function validarDatosCliente(array $datos, ?int $idActual = null): array
    {
        $cedula = $this->procesarCedulaPayload($datos);
        $nombres = trim($datos['nombres'] ?? '');
        $apellidos = trim($datos['apellidos'] ?? '');
        $correo = trim($datos['correo'] ?? '');
        $direccion = trim($datos['direccion'] ?? '');
        [$telefono, $telefono2] = $this->procesarTelefonosPayload($datos);

        if ($cedula === '') {
            return ['valido' => false, 'mensaje' => 'La cedula es obligatoria'];
        }
        if (!preg_match('/^[VEJ]-\d{6,9}$/i', $cedula)) {
            return ['valido' => false, 'mensaje' => 'El documento debe tener formato V, E o J seguido de 6 a 9 digitos'];
        }

        $error = $this->validarNombrePersona($nombres, 40, 'El nombre') ?? $this->validarNombrePersona($apellidos, 40, 'El apellido');
        if ($error !== null) {
            return ['valido' => false, 'mensaje' => $error];
        }

        if ($correo === '') {
            return ['valido' => false, 'mensaje' => 'El correo electrónico es obligatorio'];
        }
        if (!$this->validarCorreo($correo)) {
            return ['valido' => false, 'mensaje' => 'El correo electrónico no es válido'];
        }
        if ($this->correoExiste($correo, $idActual)) {
            return ['valido' => false, 'mensaje' => ($idActual ? 'Ya existe otro cliente con ese correo electrónico' : 'Ya existe un cliente con ese correo electrónico')];
        }

        $error = $this->validarTelefonos($telefono, $telefono2, $idActual);
        if ($error !== null) {
            return ['valido' => false, 'mensaje' => $error];
        }

        if ($this->cedulaExiste($cedula, $idActual)) {
            return ['valido' => false, 'mensaje' => ($idActual ? 'Ya existe otro cliente con esa cedula' : 'Ya existe un cliente con esa cedula')];
        }

        return [
            'valido' => true,
            'datos' => [
                'cedula' => $cedula,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo' => $correo,
                'direccion' => $direccion,
                'telefono' => $telefono,
                'telefono2' => $telefono2
            ]
        ];
    }

    // FUNCIÓN: registrar
    // OBJETIVO: Valida y registra un nuevo cliente o reactiva uno inactivo de forma atómica en BD
    public function registrar(array $datos): array
    {
        $validacion = $this->validarDatosCliente($datos, null);
        if (!$validacion['valido']) {
            return ['exito' => false, 'mensaje' => $validacion['mensaje']];
        }

        $d = $validacion['datos'];

        try {
            $this->conexion->beginTransaction();

            $inactivoId = $this->buscarInactivoPorCedula($d['cedula']);
            if ($inactivoId) {
                if (!$this->actualizarCliente($inactivoId, $d['cedula'], $d['nombres'], $d['apellidos'], $d['correo'], $d['direccion'])) {
                    $this->conexion->rollBack();
                    return ['exito' => false, 'mensaje' => 'Error al reactivar el cliente'];
                }
                $this->eliminarTelefonos($inactivoId);
                $this->insertarTelefono($inactivoId, $d['telefono'], 'movil');
                if (!empty($d['telefono2'])) {
                    $this->insertarTelefono($inactivoId, $d['telefono2'], 'movil');
                }

                $this->conexion->commit();
                return ['exito' => true, 'mensaje' => 'Cliente reactivado exitosamente', 'id' => $inactivoId];
            }

            $nuevoId = $this->insertarCliente($d['cedula'], $d['nombres'], $d['apellidos'], $d['correo'], $d['direccion']);
            if (!$nuevoId) {
                $this->conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Error al crear el cliente'];
            }

            $this->insertarTelefono($nuevoId, $d['telefono'], 'movil');
            if (!empty($d['telefono2'])) {
                $this->insertarTelefono($nuevoId, $d['telefono2'], 'movil');
            }

            $this->conexion->commit();
            return ['exito' => true, 'mensaje' => 'Cliente creado exitosamente', 'id' => $nuevoId];
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => 'Error en base de datos: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: modificar
    // OBJETIVO: Valida y actualiza los datos y teléfonos de un cliente existente de forma atómica
    public function modificar(int $id, array $datos): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de cliente no valido'];
        }

        $validacion = $this->validarDatosCliente($datos, $id);
        if (!$validacion['valido']) {
            return ['exito' => false, 'mensaje' => $validacion['mensaje']];
        }

        $d = $validacion['datos'];

        try {
            $this->conexion->beginTransaction();

            if (!$this->actualizarCliente($id, $d['cedula'], $d['nombres'], $d['apellidos'], $d['correo'], $d['direccion'])) {
                $this->conexion->rollBack();
                return ['exito' => false, 'mensaje' => 'Error al actualizar el cliente'];
            }

            $this->eliminarTelefonos($id);
            $this->insertarTelefono($id, $d['telefono'], 'movil');
            if (!empty($d['telefono2'])) {
                $this->insertarTelefono($id, $d['telefono2'], 'movil');
            }

            $this->conexion->commit();
            return ['exito' => true, 'mensaje' => 'Cliente actualizado exitosamente'];
        } catch (\Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return ['exito' => false, 'mensaje' => 'Error en base de datos: ' . $e->getMessage()];
        }
    }

    // FUNCIÓN: eliminar
    // OBJETIVO: Valida restricciones de cuentas por cobrar y ejecuta soft-delete
    public function eliminar(int $id): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de cliente no valido'];
        }

        if ($this->eliminarCliente($id)) {
            return ['exito' => true, 'mensaje' => 'Cliente eliminado exitosamente'];
        }

        return ['exito' => false, 'mensaje' => 'No se puede eliminar el cliente. Tiene cuentas por cobrar pendientes.'];
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

        $consulta = "SELECT COUNT(*) as total 
                     FROM cuentas_cobrar cc
                     INNER JOIN ventas v ON cc.id_venta = v.id_venta
                     WHERE v.id_cliente = :id AND cc.situacion = 'pendiente' AND cc.estado = 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetch()['total'] > 0) return false;

        return $this->_ejecutarDelete();
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
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE estado = 1";
        $stmt = $this->conexion->query($consulta);
        return (int)$stmt->fetch()['total'];
    }

    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT c.*, c.estado as activo, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.estado = 1
                     GROUP BY c.id_cliente
                     ORDER BY c.apellidos ASC, c.nombres ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT c.*, c.estado as activo, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.id_cliente = :id AND c.estado = 1
                     GROUP BY c.id_cliente LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function _ejecutarInsert(): int|false
    {
        $consulta = "INSERT INTO clientes (cedula, nombres, apellidos, correo, direccion, estado)
                     VALUES (:cedula, :nombres, :apellidos, :correo, :direccion, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $this->nombres, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $this->apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $this->direccion, PDO::PARAM_STR);
        if ($stmt->execute()) {
            return (int)$this->conexion->lastInsertId();
        }
        return false;
    }

    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE clientes
                     SET cedula = :cedula, nombres = :nombres, apellidos = :apellidos,
                         correo = :correo, direccion = :direccion, estado = 1
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
        $consulta = "UPDATE clientes SET estado = 0 WHERE id_cliente = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function _ejecutarCheckCedula(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM clientes WHERE cedula = :cedula AND estado = 1";
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
        $consulta = "SELECT id_cliente FROM clientes WHERE cedula = :cedula AND estado = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_cliente'] : false;
    }

    private function _ejecutarSearch(): array
    {
        $terminoLike = '%' . $this->termino . '%';
        $consulta = "SELECT c.*, c.estado as activo, GROUP_CONCAT(tc.telefono SEPARATOR ', ') as telefonos
                     FROM clientes c
                     LEFT JOIN telefono_cliente tc ON tc.id_cliente = c.id_cliente
                     WHERE c.estado = 1 AND (c.nombres LIKE :termino1
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

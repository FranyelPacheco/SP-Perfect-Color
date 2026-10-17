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

    // FUNCIÓN: procesarTelefonosPayload
    // OBJETIVO: Extrae y compone los números de teléfono principal y secundario desde el arreglo de datos
    private function procesarTelefonosPayload(array $datos): array
    {
        $operadora = trim($datos['operadora'] ?? '');
        $telefonoNum = trim($datos['telefono_num'] ?? '');
        $telefono = trim($datos['telefono'] ?? '');
        if (empty($telefono) && !empty($telefonoNum)) {
            $telefono = $operadora . $telefonoNum;
        }

        $operadora2 = trim($datos['operadora2'] ?? '');
        $telefonoNum2 = trim($datos['telefono_num2'] ?? '');
        $telefono2 = trim($datos['telefono2'] ?? '');
        if (empty($telefono2) && !empty($telefonoNum2)) {
            $telefono2 = $operadora2 . $telefonoNum2;
        }

        return [$telefono, $telefono2];
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

        if ($nombres === '') {
            return ['valido' => false, 'mensaje' => 'El nombre es obligatorio'];
        }
        if (mb_strlen($nombres) > 40) {
            return ['valido' => false, 'mensaje' => 'El nombre no puede exceder 40 caracteres'];
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $nombres)) {
            return ['valido' => false, 'mensaje' => 'El nombre solo puede contener letras y espacios'];
        }

        if ($apellidos === '') {
            return ['valido' => false, 'mensaje' => 'El apellido es obligatorio'];
        }
        if (mb_strlen($apellidos) > 40) {
            return ['valido' => false, 'mensaje' => 'El apellido no puede exceder 40 caracteres'];
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $apellidos)) {
            return ['valido' => false, 'mensaje' => 'El apellido solo puede contener letras y espacios'];
        }

        if ($correo === '') {
            return ['valido' => false, 'mensaje' => 'El correo electronico es obligatorio'];
        }
        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            return ['valido' => false, 'mensaje' => 'El correo electronico no es valido'];
        }

        if ($telefono === '') {
            return ['valido' => false, 'mensaje' => 'El teléfono principal es obligatorio'];
        }
        if (!preg_match('/^\d{11}$/', $telefono)) {
            return ['valido' => false, 'mensaje' => 'El teléfono principal debe tener 11 dígitos'];
        }

        if ($telefono2 !== '') {
            if (!preg_match('/^\d{11}$/', $telefono2)) {
                return ['valido' => false, 'mensaje' => 'El segundo teléfono debe tener 11 dígitos'];
            }
            if ($telefono === $telefono2) {
                return ['valido' => false, 'mensaje' => 'El segundo teléfono no puede ser igual al teléfono principal'];
            }
        }

        if ($this->telefonoExiste($telefono, $idActual)) {
            return ['valido' => false, 'mensaje' => 'El teléfono principal ya se encuentra registrado en otro cliente'];
        }

        if ($telefono2 !== '' && $this->telefonoExiste($telefono2, $idActual)) {
            return ['valido' => false, 'mensaje' => 'El segundo teléfono ya se encuentra registrado en otro cliente'];
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

    // FUNCIÓN: telefonoExiste
    // OBJETIVO: Verifica si un número de teléfono ya está registrado en clientes activos (excluyendo opcionalmente a un cliente)
    public function telefonoExiste(string $telefono, ?int $idExcluir = null): bool
    {
        $consulta = "SELECT COUNT(*) as total 
                     FROM telefono_cliente tc 
                     INNER JOIN clientes c ON tc.id_cliente = c.id_cliente 
                     WHERE tc.telefono = :telefono AND c.estado = 1";
        if ($idExcluir !== null) {
            $consulta .= " AND tc.id_cliente != :id_excluir";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        if ($idExcluir !== null) {
            $stmt->bindParam(':id_excluir', $idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetch()['total'] > 0;
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

    private function _ejecutarInsertTelefono(): bool
    {
        $consulta = "INSERT INTO telefono_cliente (id_cliente, telefono, estado) VALUES (:id_cliente, :telefono, 1)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id_cliente', $this->clienteId, PDO::PARAM_INT);
        $stmt->bindParam(':telefono', $this->telefono, PDO::PARAM_STR);
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

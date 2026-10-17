<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class UsuarioModel extends ModeloBase
{
    private int $id_usuario;
    private string $nombre;
    private string $correo;
    private string $password_hash;
    private int $id_rol;
    private int $activo;
    private int $id;
    private ?int $idExcluir;

    // FUNCIÓN: Constructor
    // OBJETIVO: Inicializa la conexión a la BD
    public function __construct()
    {
        parent::__construct();
    }

    // FUNCIÓN: buscarPorCorreo
    // OBJETIVO: Busca un usuario activo por su correo electrónico
    // NOTA: Usado en el login para autenticación
    public function buscarPorCorreo(string $correo): array|false
    {
        $this->correo = $correo;
        return $this->_ejecutarSelectByCorreo();
    }

    // FUNCIÓN: _ejecutarSelectByCorreo
    // OBJETIVO: Ejecuta la consulta de búsqueda por correo
    private function _ejecutarSelectByCorreo(): array|false
    {
        $consulta = "SELECT u.*, u.estado as activo, r.nombre as rol_nombre, r.estado as rol_activo,
                            (SELECT GROUP_CONCAT(m.codigo SEPARATOR ',') 
                             FROM rol_modulo rm 
                             JOIN modulos m ON rm.id_modulo = m.id_modulo 
                             WHERE rm.id_rol = r.id_rol) as rol_modulos 
                     FROM usuarios u 
                     INNER JOIN roles r ON u.id_rol = r.id_rol 
                     WHERE u.correo = :correo AND u.estado = 1 
                     LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarPorId
    // OBJETIVO: Busca un usuario activo por su ID
    public function buscarPorId(int $id): array|false
    {
        $this->id = $id;
        return $this->_ejecutarSelectById();
    }

    // FUNCIÓN: _ejecutarSelectById
    // OBJETIVO: Ejecuta la consulta de búsqueda por ID
    private function _ejecutarSelectById(): array|false
    {
        $consulta = "SELECT *, estado as activo FROM usuarios WHERE id_usuario = :id AND estado = 1 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: buscarConRolPorId
    // OBJETIVO: Busca un usuario activo por su ID incluyendo el nombre del rol y módulos asociados
    public function buscarConRolPorId(int $id): array|false
    {
        $this->id = $id;
        return $this->_ejecutarSelectConRolById();
    }

    // FUNCIÓN: _ejecutarSelectConRolById
    // OBJETIVO: Ejecuta la consulta con JOIN a roles para datos de perfil
    private function _ejecutarSelectConRolById(): array|false
    {
        $consulta = "SELECT u.*, u.estado as activo, r.nombre as rol_nombre,
                            (SELECT GROUP_CONCAT(m.codigo SEPARATOR ',') 
                             FROM rol_modulo rm 
                             JOIN modulos m ON rm.id_modulo = m.id_modulo 
                             WHERE rm.id_rol = r.id_rol) as rol_modulos 
                     FROM usuarios u 
                     INNER JOIN roles r ON u.id_rol = r.id_rol 
                     WHERE u.id_usuario = :id AND u.estado = 1 
                     LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // FUNCIÓN: listarTodos
    // OBJETIVO: Obtiene todos los usuarios activos con el nombre del rol asociado
    public function listarTodos(): array
    {
        return $this->_ejecutarSelectAll();
    }

    // FUNCIÓN: _ejecutarSelectAll
    // OBJETIVO: Ejecuta la consulta que lista usuarios con JOIN a roles
    private function _ejecutarSelectAll(): array
    {
        $consulta = "SELECT u.*, u.estado as activo, r.nombre as rol_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON u.id_rol = r.id_rol
                     WHERE u.estado = 1
                     ORDER BY u.nombre ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: listarRoles
    // OBJETIVO: Retorna los roles activos disponibles para asignación a usuarios
    public function listarRoles(): array
    {
        return $this->_ejecutarSelectRoles();
    }

    // FUNCIÓN: _ejecutarSelectRoles
    // OBJETIVO: Ejecuta la consulta de roles activos ordenados por ID
    private function _ejecutarSelectRoles(): array
    {
        $consulta = "SELECT *, estado as activo FROM roles WHERE estado = 1 ORDER BY id_rol ASC";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll();
    }

    // FUNCIÓN: validarDatosUsuario
    // OBJETIVO: Valida nombre, correo, clave y rol para un usuario
    public function validarDatosUsuario(array $datos, ?int $idActual = null, bool $esCreacion = true): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $correo = trim($datos['correo'] ?? '');
        $idRol = intval($datos['id_rol'] ?? 0);
        $activo = isset($datos['activo']) ? intval($datos['activo']) : 1;

        if ($nombre === '') {
            return ['valido' => false, 'mensaje' => 'El nombre es obligatorio'];
        }
        if ($correo === '' || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            return ['valido' => false, 'mensaje' => 'El correo electronico no es valido'];
        }

        if ($esCreacion) {
            $clave = $datos['clave'] ?? '';
            $errorClave = null;
            if (!\App\Helpers\validarClave($clave, $errorClave)) {
                return ['valido' => false, 'mensaje' => $errorClave ?? 'Contraseña inválida'];
            }
        }

        if ($idRol < 1) {
            return ['valido' => false, 'mensaje' => 'Debe seleccionar un rol'];
        }

        // Verificar que el rol seleccionado esté activo
        $rolModel = new RolModel();
        $rolSeleccionado = $rolModel->buscarPorId($idRol);
        if (!$rolSeleccionado || (int)$rolSeleccionado['activo'] !== 1) {
            return ['valido' => false, 'mensaje' => 'El rol seleccionado no está activo'];
        }

        if ($this->correoExiste($correo, $idActual)) {
            return ['valido' => false, 'mensaje' => ($idActual ? 'El correo electronico ya esta registrado en otro usuario' : 'El correo electronico ya esta registrado')];
        }

        return [
            'valido' => true,
            'nombre' => $nombre,
            'correo' => $correo,
            'id_rol' => $idRol,
            'activo' => $activo,
            'clave' => $datos['clave'] ?? ''
        ];
    }

    // FUNCIÓN: registrar
    // OBJETIVO: Valida y registra o reactiva un usuario
    public function registrar(array $datos): array
    {
        $val = $this->validarDatosUsuario($datos, null, true);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        $passwordHash = password_hash($val['clave'], PASSWORD_DEFAULT);
        $inactivoId = $this->buscarInactivoPorCorreo($val['correo']);
        if ($inactivoId) {
            if ($this->actualizarUsuario($inactivoId, $val['nombre'], $val['correo'], $val['id_rol'], 1)) {
                $this->actualizarClave($inactivoId, $passwordHash);
                return ['exito' => true, 'mensaje' => 'Usuario reactivado exitosamente'];
            }
            return ['exito' => false, 'mensaje' => 'Error al reactivar el usuario'];
        }

        if ($this->insertarUsuario($val['nombre'], $val['correo'], $passwordHash, $val['id_rol'], 1)) {
            \App\Helpers\enviarCredencialesUsuario($val['correo'], $val['nombre'], $val['clave']);
            return ['exito' => true, 'mensaje' => 'Usuario creado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'Error al crear el usuario'];
    }

    // FUNCIÓN: modificar
    // OBJETIVO: Valida y actualiza los datos de un usuario existente, incluyendo cambio de clave opcional
    public function modificar(int $id, array $datos, bool $puedeGestionarRoles = true): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de usuario no valido'];
        }

        $usuarioActual = $this->buscarPorId($id);
        if (!$usuarioActual) {
            return ['exito' => false, 'mensaje' => 'Usuario no encontrado'];
        }

        if (!$puedeGestionarRoles) {
            $datos['id_rol'] = (int)$usuarioActual['id_rol'];
            $datos['activo'] = (int)$usuarioActual['activo'];
        }

        $val = $this->validarDatosUsuario($datos, $id, false);
        if (!$val['valido']) {
            return ['exito' => false, 'mensaje' => $val['mensaje']];
        }

        if (!$this->actualizarUsuario($id, $val['nombre'], $val['correo'], $val['id_rol'], $val['activo'])) {
            return ['exito' => false, 'mensaje' => 'Error al actualizar el usuario'];
        }

        $cambiarClave = isset($datos['cambiar_clave']) && $datos['cambiar_clave'] == '1';
        $nuevaClave = $datos['nueva_clave'] ?? '';

        if ($cambiarClave && \App\Helpers\validarRequerido($nuevaClave)) {
            $errorClave = null;
            if (!\App\Helpers\validarClave($nuevaClave, $errorClave)) {
                return ['exito' => false, 'mensaje' => $errorClave ?? 'La nueva clave no cumple los requisitos'];
            }
            if (password_verify($nuevaClave, $usuarioActual['password_hash'])) {
                return ['exito' => false, 'mensaje' => 'La nueva contraseña no puede ser igual a la contraseña actual'];
            }
            $this->actualizarClave($id, password_hash($nuevaClave, PASSWORD_DEFAULT));
        }

        return ['exito' => true, 'mensaje' => 'Usuario actualizado exitosamente'];
    }

    // FUNCIÓN: darDeBaja
    // OBJETIVO: Soft-delete seguro de un usuario evitando dejar al sistema sin administradores
    public function darDeBaja(int $id, int $idSesionActual): array
    {
        if ($id < 1) {
            return ['exito' => false, 'mensaje' => 'ID de usuario no valido'];
        }
        if ($id === $idSesionActual) {
            return ['exito' => false, 'mensaje' => 'No puede eliminar su propio usuario'];
        }

        if ($this->eliminarUsuario($id)) {
            return ['exito' => true, 'mensaje' => 'Usuario eliminado exitosamente'];
        }
        return ['exito' => false, 'mensaje' => 'No se puede eliminar al unico administrador del sistema'];
    }

    // FUNCIÓN: autenticar
    // OBJETIVO: Valida credenciales, seguridad contra inyecciones y estado del usuario y su rol
    public function autenticar(string $correo, string $clave): array
    {
        $correo = trim($correo);
        if ($correo === '' || $clave === '') {
            return ['exito' => false, 'mensaje' => 'Todos los campos son obligatorios'];
        }

        if (strlen($clave) > 20 || preg_match('/[\'"`;\\\\<>]/', $clave) || str_contains($clave, '--') || str_contains($clave, '/*') || str_contains($clave, '*/')) {
            return ['exito' => false, 'mensaje' => 'Correo o clave incorrectos'];
        }

        $usuario = $this->buscarPorCorreo($correo);
        if (!$usuario) {
            return ['exito' => false, 'mensaje' => 'Correo o clave incorrectos'];
        }

        if (!password_verify($clave, $usuario['password_hash'])) {
            return ['exito' => false, 'mensaje' => 'Correo o clave incorrectos'];
        }

        if (!$usuario['activo']) {
            return ['exito' => false, 'mensaje' => 'Usuario inactivo. Contacte al administrador'];
        }

        if (isset($usuario['rol_activo']) && (int)$usuario['rol_activo'] === 0) {
            return ['exito' => false, 'mensaje' => 'Su rol asignado se encuentra deshabilitado. Contacte al administrador'];
        }

        return ['exito' => true, 'usuario' => $usuario];
    }

    // FUNCIÓN: actualizarPerfil
    // OBJETIVO: Valida y actualiza los datos básicos (nombre y correo) del perfil propio
    public function actualizarPerfil(int $idUsuario, array $datos): array
    {
        $nombre = trim($datos['nombre'] ?? '');
        $correo = trim($datos['correo'] ?? '');

        if ($nombre === '') {
            return ['exito' => false, 'mensaje' => 'El nombre es obligatorio'];
        }
        if ($correo === '' || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            return ['exito' => false, 'mensaje' => 'El correo electrónico no es válido'];
        }

        if ($this->correoExiste($correo, $idUsuario)) {
            return ['exito' => false, 'mensaje' => 'El correo electrónico ya se encuentra registrado por otro usuario'];
        }

        $usuarioActual = $this->buscarPorId($idUsuario);
        if (!$usuarioActual) {
            return ['exito' => false, 'mensaje' => 'Usuario no encontrado'];
        }

        $idRol = (int)$usuarioActual['id_rol'];
        $activo = (int)$usuarioActual['activo'];

        if ($this->actualizarUsuario($idUsuario, $nombre, $correo, $idRol, $activo)) {
            return [
                'exito' => true,
                'mensaje' => 'Datos de perfil actualizados exitosamente',
                'datos' => ['nombre' => $nombre, 'correo' => $correo]
            ];
        }
        return ['exito' => false, 'mensaje' => 'Error al actualizar los datos de perfil'];
    }

    // FUNCIÓN: cambiarClavePerfil
    // OBJETIVO: Valida la contraseña actual, requisitos de la nueva y actualiza la clave del perfil
    public function cambiarClavePerfil(int $idUsuario, array $datos): array
    {
        $claveActual = $datos['clave_actual'] ?? '';
        $nuevaClave = $datos['nueva_clave'] ?? '';
        $confirmarClave = $datos['confirmar_clave'] ?? '';

        if ($claveActual === '') {
            return ['exito' => false, 'mensaje' => 'Debe ingresar su contraseña actual'];
        }
        if ($nuevaClave === '') {
            return ['exito' => false, 'mensaje' => 'Debe ingresar la nueva contraseña'];
        }

        $errorClave = null;
        if (!\App\Helpers\validarClave($nuevaClave, $errorClave)) {
            return ['exito' => false, 'mensaje' => $errorClave ?? 'La nueva contraseña no cumple los requisitos'];
        }

        if ($nuevaClave !== $confirmarClave) {
            return ['exito' => false, 'mensaje' => 'La confirmación de la nueva contraseña no coincide'];
        }

        $usuarioActual = $this->buscarPorId($idUsuario);
        if (!$usuarioActual) {
            return ['exito' => false, 'mensaje' => 'Usuario no encontrado'];
        }

        if (!password_verify($claveActual, $usuarioActual['password_hash'])) {
            return ['exito' => false, 'mensaje' => 'La contraseña actual ingresada es incorrecta'];
        }

        if ($nuevaClave === $claveActual || password_verify($nuevaClave, $usuarioActual['password_hash'])) {
            return ['exito' => false, 'mensaje' => 'La nueva contraseña no puede ser igual a la contraseña actual'];
        }

        $nuevoHash = password_hash($nuevaClave, PASSWORD_DEFAULT);
        if ($this->actualizarClave($idUsuario, $nuevoHash)) {
            \App\Helpers\enviarNotificacionCambioClave($usuarioActual['correo'], $usuarioActual['nombre']);
            return ['exito' => true, 'mensaje' => 'Contraseña modificada exitosamente. Se ha enviado un aviso a su correo.'];
        }
        return ['exito' => false, 'mensaje' => 'Error al actualizar la contraseña'];
    }

    // FUNCIÓN: restablecerClaveConToken
    // OBJETIVO: Valida el token de recuperación y actualiza la contraseña
    public function restablecerClaveConToken(string $token, string $nuevaClave, string $confirmarClave, string $tokenSecret): array
    {
        if (empty($token)) {
            return ['exito' => false, 'mensaje' => 'Token de recuperación no válido'];
        }

        if ($nuevaClave === '') {
            return ['exito' => false, 'mensaje' => 'La nueva contraseña es obligatoria'];
        }

        $errorClave = null;
        if (!\App\Helpers\validarClave($nuevaClave, $errorClave)) {
            return ['exito' => false, 'mensaje' => $errorClave ?? 'La nueva contraseña no cumple los requisitos'];
        }

        if ($nuevaClave !== $confirmarClave) {
            return ['exito' => false, 'mensaje' => 'Las contraseñas ingresadas no coinciden'];
        }

        $data = json_decode(base64_decode($token), true);
        if (!$data || !isset($data['id'], $data['exp'], $data['hash'])) {
            return ['exito' => false, 'mensaje' => 'Enlace de recuperación inválido'];
        }

        if (time() > $data['exp']) {
            return ['exito' => false, 'mensaje' => 'El enlace de recuperación ha expirado'];
        }

        $usuario = $this->buscarPorId((int)$data['id']);
        if (!$usuario || (int)$usuario['activo'] !== 1) {
            return ['exito' => false, 'mensaje' => 'Usuario no encontrado'];
        }

        $firmaEsperada = hash_hmac('sha256', $usuario['id_usuario'] . $usuario['password_hash'] . $data['exp'], $tokenSecret);
        if (!hash_equals($firmaEsperada, $data['hash'])) {
            return ['exito' => false, 'mensaje' => 'Este enlace ya fue utilizado o no es válido'];
        }

        if (password_verify($nuevaClave, $usuario['password_hash'])) {
            return ['exito' => false, 'mensaje' => 'La nueva contraseña no puede ser igual a la contraseña anterior'];
        }

        $nuevoHash = password_hash($nuevaClave, PASSWORD_DEFAULT);
        if ($this->actualizarClave((int)$usuario['id_usuario'], $nuevoHash)) {
            \App\Helpers\enviarNotificacionCambioClave($usuario['correo'], $usuario['nombre']);
            return ['exito' => true, 'mensaje' => 'Contraseña restablecida exitosamente. Redirigiendo...'];
        }
        return ['exito' => false, 'mensaje' => 'Error al guardar la nueva contraseña'];
    }

    // FUNCIÓN: insertarUsuario
    // OBJETIVO: Inserta un nuevo usuario con nombre, correo, contraseña hash y rol
    public function insertarUsuario(string $nombre, string $correo, string $passwordHash, int $idRol, int $activo = 1): bool
    {
        $this->nombre = $nombre;
        $this->correo = $correo;
        $this->password_hash = $passwordHash;
        $this->id_rol = $idRol;
        $this->activo = $activo;
        return $this->_ejecutarInsert();
    }

    // FUNCIÓN: _ejecutarInsert
    // OBJETIVO: Ejecuta el INSERT del usuario en la tabla usuarios
    private function _ejecutarInsert(): bool
    {
        $consulta = "INSERT INTO usuarios (nombre, correo, password_hash, id_rol, estado)
                     VALUES (:nombre, :correo, :password_hash, :id_rol, :activo)";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':id_rol', $this->id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizarUsuario
    // OBJETIVO: Actualiza los datos de un usuario (nombre, correo, rol, estado)
    // NOTA: No actualiza la contraseña; usar actualizarClave para eso
    public function actualizarUsuario(int $id, string $nombre, string $correo, int $idRol, int $activo): bool
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->correo = $correo;
        $this->id_rol = $idRol;
        $this->activo = $activo;
        return $this->_ejecutarUpdate();
    }

    // FUNCIÓN: _ejecutarUpdate
    // OBJETIVO: Ejecuta el UPDATE de datos generales del usuario
    private function _ejecutarUpdate(): bool
    {
        $consulta = "UPDATE usuarios SET nombre = :nombre, correo = :correo, id_rol = :id_rol, estado = :activo WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(':id_rol', $this->id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: actualizarClave
    // OBJETIVO: Actualiza únicamente la contraseña de un usuario
    public function actualizarClave(int $id, string $passwordHash): bool
    {
        $this->id = $id;
        $this->password_hash = $passwordHash;
        return $this->_ejecutarUpdateClave();
    }

    // FUNCIÓN: _ejecutarUpdateClave
    // OBJETIVO: Ejecuta el UPDATE exclusivo del campo password_hash
    private function _ejecutarUpdateClave(): bool
    {
        $consulta = "UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':password_hash', $this->password_hash, PDO::PARAM_STR);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: eliminarUsuario
    // OBJETIVO: Desactiva un usuario (soft delete)
    // NOTA: No permite eliminar el último administrador activo (rol = 1)
    public function eliminarUsuario(int $id): bool
    {
        $this->id = $id;
        $usuario = $this->buscarPorId($id);
        if ($usuario && $usuario['id_rol'] == 1) {
            $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE id_rol = 1 AND estado = 1";
            $stmt = $this->conexion->query($consulta);
            $totalAdmins = $stmt->fetch()['total'];
            if ($totalAdmins <= 1) return false;
        }
        return $this->_ejecutarDelete();
    }

    // FUNCIÓN: _ejecutarDelete
    // OBJETIVO: Ejecuta el UPDATE que marca el usuario como inactivo
    private function _ejecutarDelete(): bool
    {
        $consulta = "UPDATE usuarios SET estado = 0 WHERE id_usuario = :id";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // FUNCIÓN: correoExiste
    // OBJETIVO: Verifica si un correo ya está registrado entre usuarios activos
    public function correoExiste(string $correo, ?int $idExcluir = null): bool
    {
        $this->correo = $correo;
        $this->idExcluir = $idExcluir;
        return $this->_ejecutarCheckCorreo();
    }

    // FUNCIÓN: _ejecutarCheckCorreo
    // OBJETIVO: Ejecuta la consulta COUNT para verificar unicidad del correo
    private function _ejecutarCheckCorreo(): bool
    {
        $consulta = "SELECT COUNT(*) as total FROM usuarios WHERE correo = :correo AND estado = 1";
        if ($this->idExcluir !== null) {
            $consulta .= " AND id_usuario != :id";
        }
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        if ($this->idExcluir !== null) {
            $stmt->bindParam(':id', $this->idExcluir, PDO::PARAM_INT);
        }
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado['total'] > 0;
    }

    // FUNCIÓN: buscarInactivoPorCorreo
    // OBJETIVO: Busca un usuario inactivo por correo para reactivación
    // NOTA: Usado antes de insertar para evitar duplicados por soft delete
    public function buscarInactivoPorCorreo(string $correo): int|false
    {
        if (empty($correo)) return false;
        $this->correo = $correo;
        return $this->_ejecutarBuscarInactivo();
    }

    // FUNCIÓN: _ejecutarBuscarInactivo
    // OBJETIVO: Retorna el ID de un usuario inactivo con el correo dado, o false si no existe
    private function _ejecutarBuscarInactivo(): int|false
    {
        $consulta = "SELECT id_usuario FROM usuarios WHERE correo = :correo AND estado = 0 LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':correo', $this->correo, PDO::PARAM_STR);
        $stmt->execute();
        $fila = $stmt->fetch();
        return $fila ? (int)$fila['id_usuario'] : false;
    }
}

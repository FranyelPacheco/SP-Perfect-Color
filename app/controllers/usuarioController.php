<?php
// Archivo: usuarioController.php
// Controlador procedimental para la gestion de usuarios y roles

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\RolModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolAdmin;
use function App\Helpers\tienePermiso;
use function App\Helpers\verificarPermiso;
use function App\Helpers\validarRequerido;
use function App\Helpers\validarCorreo;
use function App\Helpers\verificarPropietario;

$usuarioModel = new UsuarioModel();
$rolModel = new RolModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de perfil / usuarios / roles
// NOTA: Quienes tienen permiso al módulo 'usuario' (o admin) pueden gestionar usuarios y roles
if ($metodo === 'index') {
    verificarAutenticacion();

    if (!isset($_SESSION['usuario_correo'])) {
        $usuarioActual = $usuarioModel->buscarPorId($_SESSION['id_usuario']);
        if ($usuarioActual) {
            $_SESSION['usuario_correo'] = $usuarioActual['correo'];
        }
    }

    $puedeGestionar = tienePermiso('usuario');

    if ($puedeGestionar) {
        $usuarios = $usuarioModel->listarTodos();
        $roles = $rolModel->listarActivos();
        $todosRoles = $rolModel->listarTodos();
    } else {
        $usuarios = [];
        $roles = [];
        $todosRoles = [];
    }

    $pageTitle = $puedeGestionar ? 'SP Perfect Color - Gestión de Usuarios y Roles' : 'SP Perfect Color - Mi Perfil';
    $pageDescription = 'Gestión de perfil, usuarios y roles del sistema - SP Perfect Color';
    $contenidoVista = __DIR__ . '/../views/usuarioListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
    exit;

// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene usuarios y roles activos en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();

    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    $usuarios = $usuarioModel->listarTodos();
    $roles = $rolModel->listarActivos();

    respuestaJson('exito', 'Usuarios obtenidos correctamente', [
        'usuarios' => $usuarios,
        'roles' => $roles
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo usuario o reactiva uno inactivo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $id_rol = intval($_POST['id_rol'] ?? 0);

    if (!validarRequerido($nombre)) respuestaJson('error', 'El nombre es obligatorio');
    if (!validarCorreo($correo)) respuestaJson('error', 'El correo electronico no es valido');
    if (!validarRequerido($clave) || strlen($clave) < 6) respuestaJson('error', 'La clave debe tener al menos 6 caracteres');
    if ($id_rol < 1) respuestaJson('error', 'Debe seleccionar un rol');

    // Verificar que el rol seleccionado esté activo
    $rolSeleccionado = $rolModel->buscarPorId($id_rol);
    if (!$rolSeleccionado || (int)$rolSeleccionado['activo'] !== 1) {
        respuestaJson('error', 'El rol seleccionado no está activo');
    }

    if ($usuarioModel->correoExiste($correo)) respuestaJson('error', 'El correo electronico ya esta registrado');

    $passwordHash = password_hash($clave, PASSWORD_DEFAULT);

    $inactivoId = $usuarioModel->buscarInactivoPorCorreo($correo);
    if ($inactivoId) {
        if ($usuarioModel->actualizarUsuario($inactivoId, $nombre, $correo, $id_rol, 1)) {
            $usuarioModel->actualizarClave($inactivoId, $passwordHash);
            respuestaJson('exito', 'Usuario reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el usuario');
        }
    }

    if ($usuarioModel->insertarUsuario($nombre, $correo, $passwordHash, $id_rol, 1)) {
        respuestaJson('exito', 'Usuario creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el usuario');
    }

// FUNCIÓN: obtener
// OBJETIVO: Obtiene un usuario por ID (oculta password_hash) para edición
} elseif ($metodo === 'obtener') {
    verificarAutenticacion();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de usuario no valido');

    if (!tienePermiso('usuario')) {
        verificarPropietario($id);
    }

    $usuario = $usuarioModel->buscarPorId($id);

    if ($usuario) {
        unset($usuario['password_hash']);
        respuestaJson('exito', 'Usuario obtenido correctamente', $usuario);
    } else {
        respuestaJson('error', 'Usuario no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza un usuario existente, con opción de cambio de clave
} elseif ($metodo === 'actualizar') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if ($id < 1) respuestaJson('error', 'ID de usuario no valido');
    if (!validarRequerido($nombre)) respuestaJson('error', 'El nombre es obligatorio');
    if (!validarCorreo($correo)) respuestaJson('error', 'El correo electronico no es valido');

    if (!tienePermiso('usuario')) {
        verificarPropietario($id);
        $usuarioActual = $usuarioModel->buscarPorId($id);
        $id_rol = (int)$usuarioActual['id_rol'];
        $activo = (int)$usuarioActual['activo'];
    } else {
        $id_rol = intval($_POST['id_rol'] ?? 0);
        $activo = intval($_POST['activo'] ?? 1);
        if ($id_rol < 1) respuestaJson('error', 'Debe seleccionar un rol');

        // Verificar que el rol seleccionado esté activo
        $rolSeleccionado = $rolModel->buscarPorId($id_rol);
        if (!$rolSeleccionado || (int)$rolSeleccionado['activo'] !== 1) {
            respuestaJson('error', 'El rol seleccionado no está activo');
        }
    }

    if ($usuarioModel->correoExiste($correo, $id)) {
        respuestaJson('error', 'El correo electronico ya esta registrado en otro usuario');
    }

    if ($usuarioModel->actualizarUsuario($id, $nombre, $correo, $id_rol, $activo)) {
        if ($id == $_SESSION['id_usuario']) {
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_correo'] = $correo;
        }

        $cambiarClave = isset($_POST['cambiar_clave']) && $_POST['cambiar_clave'] == '1';
        $nuevaClave = $_POST['nueva_clave'] ?? '';

        if ($cambiarClave && validarRequerido($nuevaClave)) {
            if (strlen($nuevaClave) < 6) {
                respuestaJson('error', 'La nueva clave debe tener al menos 6 caracteres');
            }
            $usuarioModel->actualizarClave($id, password_hash($nuevaClave, PASSWORD_DEFAULT));
        }

        respuestaJson('exito', 'Usuario actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el usuario');
    }

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un usuario (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de usuario no valido');
    if ($id == $_SESSION['id_usuario']) respuestaJson('error', 'No puede eliminar su propio usuario');

    if ($usuarioModel->eliminarUsuario($id)) {
        respuestaJson('exito', 'Usuario eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar al unico administrador del sistema');
    }

// ============================================================
// ENDPOINTS PARA ROLES Y MÓDULOS
// ============================================================

// FUNCIÓN: listarRolesAjax
// OBJETIVO: Retorna la lista completa de roles con estado y módulos
} elseif ($metodo === 'listarRolesAjax') {
    verificarAutenticacion();
    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    $roles = $rolModel->listarTodos();
    respuestaJson('exito', 'Roles obtenidos correctamente', $roles);

// FUNCIÓN: obtenerRol
// OBJETIVO: Retorna los datos de un rol para su edición
} elseif ($metodo === 'obtenerRol') {
    verificarAutenticacion();
    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de rol no válido');

    $rol = $rolModel->buscarPorId($id);
    if ($rol) {
        respuestaJson('exito', 'Rol obtenido correctamente', $rol);
    } else {
        respuestaJson('error', 'Rol no encontrado');
    }

// FUNCIÓN: guardarRol
// OBJETIVO: Crea un nuevo rol con sus módulos autorizados
} elseif ($metodo === 'guardarRol') {
    verificarAutenticacion();
    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $modulos = $_POST['modulos'] ?? [];

    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre del rol es obligatorio');
    }

    if (empty($modulos)) {
        respuestaJson('error', 'Debe seleccionar al menos un módulo para el rol');
    }

    // Comprobar si existe un rol inactivo con el mismo nombre para reactivarlo
    $inactivoId = $rolModel->buscarInactivoPorNombre($nombre);
    if ($inactivoId) {
        if ($rolModel->actualizarRol($inactivoId, $nombre, 1, $modulos)) {
            respuestaJson('exito', 'Rol reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el rol');
        }
    }

    if ($rolModel->nombreExiste($nombre)) {
        respuestaJson('error', 'Ya existe un rol con ese nombre');
    }

    if ($rolModel->insertarRol($nombre, $modulos, 1)) {
        respuestaJson('exito', 'Rol creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el rol');
    }

// FUNCIÓN: actualizarRol
// OBJETIVO: Actualiza el nombre, módulos y estado de un rol
} elseif ($metodo === 'actualizarRol') {
    verificarAutenticacion();
    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
    $modulos = $_POST['modulos'] ?? [];

    if ($id < 1) respuestaJson('error', 'ID de rol no válido');
    if (!validarRequerido($nombre)) respuestaJson('error', 'El nombre del rol es obligatorio');
    if (empty($modulos)) respuestaJson('error', 'Debe seleccionar al menos un módulo para el rol');

    if ($rolModel->nombreExiste($nombre, $id)) {
        respuestaJson('error', 'Ya existe otro rol con ese nombre');
    }

    // Si es Administrador raíz (ID 1), asegurar que conserve el módulo 'usuario' y permanezca activo
    if ($id === 1) {
        $activo = 1;
        if (is_array($modulos) && !in_array('usuario', $modulos)) {
            $modulos[] = 'usuario';
        }
    }

    if ($rolModel->actualizarRol($id, $nombre, $activo, $modulos)) {
        // Si se actualizó el rol del usuario actualmente en sesión, actualizar sus módulos en sesión
        if ($id === (int)$_SESSION['usuario_rol']) {
            $_SESSION['usuario_modulos'] = is_array($modulos) ? implode(',', $modulos) : $modulos;
        }
        respuestaJson('exito', 'Rol actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el rol');
    }

// FUNCIÓN: toggleRol
// OBJETIVO: Habilita o deshabilita un rol
} elseif ($metodo === 'toggleRol') {
    verificarAutenticacion();
    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $activo = intval($_POST['activo'] ?? 0);

    if ($id < 1) respuestaJson('error', 'ID de rol no válido');
    if ($id === 1) {
        respuestaJson('error', 'El rol de Administrador no puede ser deshabilitado');
    }

    if ($rolModel->toggleActivo($id, $activo)) {
        $mensaje = $activo ? 'Rol habilitado exitosamente' : 'Rol deshabilitado exitosamente';
        respuestaJson('exito', $mensaje);
    } else {
        respuestaJson('error', 'Error al cambiar el estado del rol');
    }

// FUNCIÓN: eliminarRol
// OBJETIVO: Elimina permanentemente un rol (protege Administrador id_rol = 1)
} elseif ($metodo === 'eliminarRol') {
    verificarAutenticacion();
    if (!tienePermiso('usuario')) {
        respuestaJson('error', 'Acceso denegado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de rol no válido');
    }

    if ($id === 1) {
        respuestaJson('error', 'El rol de Administrador no puede ser eliminado');
    }

    try {
        if ($rolModel->eliminarRol($id)) {
            respuestaJson('exito', 'Rol eliminado exitosamente');
        } else {
            respuestaJson('error', 'Error al eliminar el rol');
        }
    } catch (\PDOException $e) {
        respuestaJson('error', $e->getMessage());
    }

} else {
    require_once __DIR__ . '/../views/error404View.php';
}

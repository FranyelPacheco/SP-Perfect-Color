<?php
// Archivo: usuarioController.php
// Controlador procedimental para la gestion de usuarios y roles

namespace App\Controllers;

use App\Models\{
    UsuarioModel,
    RolModel
};
use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    verificarRolAdmin,
    tienePermiso,
    verificarPermiso,
    validarRequerido,
    validarCorreo,
    validarClave,
    verificarPropietario,
    enviarCredencialesUsuario
};

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

    if (!tienePermiso('usuario')) {
        header('Location: /SP%20Perfect%20Color/perfil');
        exit;
    }

    $puedeGestionar = true;
    $usuarios = $usuarioModel->listarTodos();
    $roles = $rolModel->listarActivos();
    $todosRoles = $rolModel->listarTodos();

    $pageTitle = 'SP Perfect Color - Gestión de Usuarios y Roles';
    $pageDescription = 'Gestión de usuarios y roles del sistema - SP Perfect Color';
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

// FUNCIÓN: verificarCorreoAjax
// OBJETIVO: Verifica en tiempo real si un correo ya está registrado en usuarios
} elseif ($metodo === 'verificarCorreoAjax') {
    verificarAutenticacion();
    $correo = trim($_GET['correo'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($correo) && $usuarioModel->correoExiste($correo, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo usuario o reactiva uno inactivo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    if (intval($_POST['id_rol'] ?? 0) === 1 && intval($_SESSION['usuario_rol'] ?? 0) !== 1) {
        respuestaJson('error', 'Solo un administrador puede asignar el rol Administrador');
    }

    $resultado = $usuarioModel->registrar($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

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

    if (intval($_POST['id_rol'] ?? 0) === 1 && intval($_SESSION['usuario_rol'] ?? 0) !== 1) {
        respuestaJson('error', 'Solo un administrador puede asignar el rol Administrador');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de usuario no valido');

    if (!tienePermiso('usuario')) {
        verificarPropietario($id);
    }

    $resultado = $usuarioModel->modificar($id, $_POST, tienePermiso('usuario'));
    if ($resultado['exito']) {
        if ($id == $_SESSION['id_usuario']) {
            $_SESSION['usuario_nombre'] = trim($_POST['nombre'] ?? $_SESSION['usuario_nombre']);
            $_SESSION['usuario_correo'] = trim($_POST['correo'] ?? $_SESSION['usuario_correo']);
        }
        respuestaJson('exito', $resultado['mensaje']);
    } else {
        respuestaJson('error', $resultado['mensaje']);
    }

// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un usuario (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $resultado = $usuarioModel->darDeBaja($id, (int)$_SESSION['id_usuario']);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

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

    $resultado = $rolModel->registrar($_POST);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

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
    $resultado = $rolModel->modificar($id, $_POST);
    if ($resultado['exito']) {
        if ($id === (int)$_SESSION['usuario_rol']) {
            $modulos = $_POST['modulos'] ?? [];
            $_SESSION['usuario_modulos'] = is_array($modulos) ? implode(',', $modulos) : $modulos;
        }
        respuestaJson('exito', $resultado['mensaje']);
    } else {
        respuestaJson('error', $resultado['mensaje']);
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
    $resultado = $rolModel->darDeBaja($id);
    respuestaJson($resultado['exito'] ? 'exito' : 'error', $resultado['mensaje']);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}

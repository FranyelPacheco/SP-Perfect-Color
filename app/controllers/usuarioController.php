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
    verificarSoloAdmin,
    tienePermiso,
    verificarPropietario,
    soloPost,
    responder
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
    if ((int)($_SESSION['usuario_rol'] ?? 0) !== 1) {
        $usuarios = array_map(fn($u) => array_diff_key($u, ['tiene_contrato' => 0]), $usuarios);
    }
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
    if (!tienePermiso('usuario') && $id !== (int)($_SESSION['id_usuario'] ?? 0)) {
        respuestaJson('error', 'Acceso denegado');
    }
    $existe = !empty($correo) && $usuarioModel->correoExiste($correo, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo usuario o reactiva uno inactivo
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    soloPost();

    if (intval($_POST['id_rol'] ?? 0) === 1 && intval($_SESSION['usuario_rol'] ?? 0) !== 1) {
        respuestaJson('error', 'Solo un administrador puede asignar el rol Administrador');
    }

    $resultado = $usuarioModel->registrar($_POST);
    responder($resultado);

// FUNCIÓN: obtener
// OBJETIVO: Obtiene un usuario por ID (oculta password_hash) para edición
} elseif ($metodo === 'obtener') {
    verificarAutenticacion();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'No se encontró el registro. Actualice la página e intente de nuevo.');

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

    soloPost();

    if (intval($_POST['id_rol'] ?? 0) === 1 && intval($_SESSION['usuario_rol'] ?? 0) !== 1) {
        respuestaJson('error', 'Solo un administrador puede asignar el rol Administrador');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'No se encontró el registro. Actualice la página e intente de nuevo.');

    if (!tienePermiso('usuario')) {
        verificarPropietario($id);
    }

    $objetivo = $usuarioModel->buscarPorId($id);
    if ($objetivo && (int)$objetivo['id_rol'] === 1 && (int)($_SESSION['usuario_rol'] ?? 0) !== 1) {
        respuestaJson('error', 'Solo un administrador puede modificar a otro administrador');
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

    soloPost();

    $id = intval($_POST['id'] ?? 0);
    $objetivo = $usuarioModel->buscarPorId($id);
    if ($objetivo && (int)$objetivo['id_rol'] === 1 && (int)($_SESSION['usuario_rol'] ?? 0) !== 1) {
        respuestaJson('error', 'Solo un administrador puede eliminar a otro administrador');
    }
    $resultado = $usuarioModel->darDeBaja($id, (int)$_SESSION['id_usuario']);
    responder($resultado);

// FUNCIÓN: subirContrato
// OBJETIVO: Guarda el contrato en PDF de un trabajador (solo Administrador)
} elseif ($metodo === 'subirContrato') {
    verificarSoloAdmin();

    soloPost();

    $resultado = $usuarioModel->guardarContrato(intval($_POST['id'] ?? 0), $_FILES['contrato'] ?? []);
    responder($resultado);

// FUNCIÓN: eliminarContrato
// OBJETIVO: Quita el contrato de un trabajador (solo Administrador)
} elseif ($metodo === 'eliminarContrato') {
    verificarSoloAdmin();

    soloPost();

    $resultado = $usuarioModel->eliminarContrato(intval($_POST['id'] ?? 0));
    responder($resultado);

// FUNCIÓN: verContrato
// OBJETIVO: Muestra el PDF del contrato en el navegador; el archivo no es accesible por URL directa (solo Administrador)
} elseif ($metodo === 'verContrato') {
    verificarSoloAdmin();

    $idContrato = intval($_GET['id'] ?? 0);
    $ruta = $usuarioModel->rutaContrato($idContrato);
    if ($ruta === null) {
        http_response_code(404);
        require_once __DIR__ . '/../views/error404View.php';
        exit;
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="contrato-' . $idContrato . '.pdf"');
    header('Content-Length: ' . filesize($ruta));
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    readfile($ruta);
    exit;

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
    if ($id < 1) respuestaJson('error', 'No se encontró el registro. Actualice la página e intente de nuevo.');

    $rol = $rolModel->buscarPorId($id);
    if ($rol) {
        respuestaJson('exito', 'Rol obtenido correctamente', $rol);
    } else {
        respuestaJson('error', 'Rol no encontrado');
    }

// FUNCIÓN: guardarRol
// OBJETIVO: Crea un nuevo rol con sus módulos autorizados
} elseif ($metodo === 'guardarRol') {
    verificarSoloAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $resultado = $rolModel->registrar($_POST);
    responder($resultado);

// FUNCIÓN: actualizarRol
// OBJETIVO: Actualiza el nombre, módulos y estado de un rol
} elseif ($metodo === 'actualizarRol') {
    verificarSoloAdmin();

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
    verificarSoloAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $activo = intval($_POST['activo'] ?? 0);

    if ($id < 1) respuestaJson('error', 'No se encontró el registro. Actualice la página e intente de nuevo.');
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
    verificarSoloAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Método no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $resultado = $rolModel->darDeBaja($id);
    responder($resultado);

} else {
    require_once __DIR__ . '/../views/error404View.php';
}

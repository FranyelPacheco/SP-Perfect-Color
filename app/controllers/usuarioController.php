<?php
// Archivo: usuarioController.php
// Controlador procedimental para la gestion de usuarios

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';
require_once __DIR__ . '/../helpers/validacionHelper.php';

$usuarioModel = new UsuarioModel();

function verificarPropietario($idSolicitado)
{
    $idSesion = $_SESSION['usuario_id'] ?? null;
    if ($idSesion === null || (int)$idSolicitado !== (int)$idSesion) {
        respuestaJson('error', 'Acceso denegado. Solo puedes acceder a tu propio perfil');
    }
}

// 1. Cargar la vista del listado de usuarios
if ($metodo === 'index') {
    verificarAutenticacion();

    if ($_SESSION['usuario_rol'] == 1) {
        $usuarios = $usuarioModel->listarTodos();
        $roles = $usuarioModel->listarRoles();
    } elseif ($_SESSION['usuario_rol'] == 2) {
        $usuarios = [];
        $roles = [];
    } else {
        verificarRolAdmin();
    }

    $contenidoVista = __DIR__ . '/../views/usuarioListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
    echo "<script>const SESSION_USER_ROL = " . $_SESSION['usuario_rol'] . "; const SESSION_USER_ID = " . $_SESSION['usuario_id'] . ";</script>";
    exit;

// 2. Obtener usuarios y roles en JSON (AJAX)
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();

    if ($_SESSION['usuario_rol'] != 1) {
        respuestaJson('error', 'Acceso denegado');
    }

    $usuarios = $usuarioModel->listarTodos();
    $roles = $usuarioModel->listarRoles();

    respuestaJson('exito', 'Usuarios obtenidos correctamente', [
        'usuarios' => $usuarios,
        'roles' => $roles
    ]);

// 3. Guardar un nuevo usuario
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $rol_id = intval($_POST['rol_id'] ?? 0);

    if (!validarRequerido($nombre)) respuestaJson('error', 'El nombre es obligatorio');
    if (!validarCorreo($correo)) respuestaJson('error', 'El correo electronico no es valido');
    if (!validarRequerido($clave) || strlen($clave) < 6) respuestaJson('error', 'La clave debe tener al menos 6 caracteres');
    if ($rol_id < 1) respuestaJson('error', 'Debe seleccionar un rol');
    if ($usuarioModel->correoExiste($correo)) respuestaJson('error', 'El correo electronico ya esta registrado');

    $datos = [
        'nombre' => $nombre,
        'correo' => $correo,
        'password_hash' => password_hash($clave, PASSWORD_DEFAULT),
        'rol_id' => $rol_id,
        'activo' => 1
    ];

    if ($usuarioModel->insertarUsuario($datos)) {
        respuestaJson('exito', 'Usuario creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el usuario');
    }

// 4. Obtener un usuario por ID (AJAX para edicion)
} elseif ($metodo === 'obtener') {
    verificarAutenticacion();

    $id = intval($_GET['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de usuario no valido');

    if ($_SESSION['usuario_rol'] == 2) {
        verificarPropietario($id);
    } elseif ($_SESSION['usuario_rol'] != 1) {
        verificarRolAdmin();
    }

    $usuario = $usuarioModel->buscarPorId($id);

    if ($usuario) {
        unset($usuario['password_hash']);
        respuestaJson('exito', 'Usuario obtenido correctamente', $usuario);
    } else {
        respuestaJson('error', 'Usuario no encontrado');
    }

// 5. Actualizar un usuario existente
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

    // Vendedor solo edita su propio perfil y conserva sus valores originales
    if ($_SESSION['usuario_rol'] == 2) {
        verificarPropietario($id);
        $usuarioActual = $usuarioModel->buscarPorId($id);
        $rol_id = $usuarioActual['rol_id'];
        $activo = $usuarioActual['activo'];
    } else {
        $rol_id = intval($_POST['rol_id'] ?? 0);
        $activo = intval($_POST['activo'] ?? 1);
        if ($rol_id < 1) respuestaJson('error', 'Debe seleccionar un rol');
    }

    if ($usuarioModel->correoExiste($correo, $id)) {
        respuestaJson('error', 'El correo electronico ya esta registrado en otro usuario');
    }

    $datos = [
        'id' => $id,
        'nombre' => $nombre,
        'correo' => $correo,
        'rol_id' => $rol_id,
        'activo' => $activo
    ];

    if ($usuarioModel->actualizarUsuario($datos)) {
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

// 6. Eliminar un usuario
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    if ($id < 1) respuestaJson('error', 'ID de usuario no valido');
    if ($id == $_SESSION['usuario_id']) respuestaJson('error', 'No puede eliminar su propio usuario');

    if ($usuarioModel->eliminarUsuario($id)) {
        respuestaJson('exito', 'Usuario eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar al unico administrador del sistema');
    }

// Fallback: ruta no valida
} else {
    require_once __DIR__ . '/../views/error404View.php';
}

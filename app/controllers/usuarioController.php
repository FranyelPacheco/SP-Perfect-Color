<?php
// Archivo: usuarioController.php
// Controlador para la gestion de usuarios (Procedimental)

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';
require_once __DIR__ . '/../helpers/validacionHelper.php';

// Instancia limpia del modelo para usar de forma procedimental
$usuarioModel = new UsuarioModel();

// 1. Mostrar la lista de usuarios (solo Administrador)
if ($metodo === 'index') {
    // Solo el administrador puede ver la lista de usuarios
    verificarRolAdmin();
    
    // Obtener todos los usuarios
    $usuarios = $usuarioModel->listarTodos();
    $roles = $usuarioModel->listarRoles();
    
    // Definir la vista de contenido
    $contenidoVista = __DIR__ . '/../views/usuarioListView.php';
    
    // Cargar la plantilla base
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtener la lista de usuarios en formato JSON para AJAX
} elseif ($metodo === 'listarAjax') {
    verificarRolAdmin();
    
    $usuarios = $usuarioModel->listarTodos();
    $roles = $usuarioModel->listarRoles();
    
    respuestaJson('exito', 'Usuarios obtenidos correctamente', [
        'usuarios' => $usuarios,
        'roles' => $roles
    ]);

// 3. Guardar un nuevo usuario via AJAX
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();
    
    // Verificar metodo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Obtener datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $rol_id = intval($_POST['rol_id'] ?? 0);
    
    // Validar campos obligatorios
    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }
    
    if (!validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
    }
    
    if (!validarRequerido($clave) || strlen($clave) < 6) {
        respuestaJson('error', 'La clave debe tener al menos 6 caracteres');
    }
    
    if ($rol_id < 1) {
        respuestaJson('error', 'Debe seleccionar un rol');
    }
    
    // Verificar que el correo no exista
    if ($usuarioModel->correoExiste($correo)) {
        respuestaJson('error', 'El correo electronico ya esta registrado');
    }
    
    // Generar hash de la clave
    $passwordHash = password_hash($clave, PASSWORD_DEFAULT);
    
    // Preparar datos para insertar
    $datos = [
        'nombre' => $nombre,
        'correo' => $correo,
        'password_hash' => $passwordHash,
        'rol_id' => $rol_id,
        'activo' => 1
    ];
    
    // Insertar usuario
    if ($usuarioModel->insertarUsuario($datos)) {
        respuestaJson('exito', 'Usuario creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el usuario');
    }

// 4. Obtener un usuario por ID para edicion via AJAX
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de usuario no valido');
    }
    
    $usuario = $usuarioModel->buscarPorId($id);
    
    if ($usuario) {
        // No enviar el hash de la clave por seguridad
        unset($usuario['password_hash']);
        respuestaJson('exito', 'Usuario obtenido correctamente', $usuario);
    } else {
        respuestaJson('error', 'Usuario no encontrado');
    }

// 5. Actualizar un usuario existente via AJAX
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();
    
    // Verificar metodo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Obtener datos del formulario
    $id = intval($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol_id = intval($_POST['rol_id'] ?? 0);
    $activo = intval($_POST['activo'] ?? 1);
    $cambiarClave = isset($_POST['cambiar_clave']) && $_POST['cambiar_clave'] == '1';
    $nuevaClave = $_POST['nueva_clave'] ?? '';
    
    // Validar campos obligatorios
    if ($id < 1) {
        respuestaJson('error', 'ID de usuario no valido');
    }
    
    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }
    
    if (!validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
    }
    
    if ($rol_id < 1) {
        respuestaJson('error', 'Debe seleccionar un rol');
    }
    
    // Verificar que el correo no exista en otro usuario
    if ($usuarioModel->correoExiste($correo, $id)) {
        respuestaJson('error', 'El correo electronico ya esta registrado en otro usuario');
    }
    
    // Verificar que no se desactive al unico administrador
    $usuarioActual = $usuarioModel->buscarPorId($id);
    if ($activo == 0 && $usuarioActual['rol_id'] == 1) {
        // Contar administradores activos
        $usuarios = $usuarioModel->listarTodos();
        $adminsActivos = 0;
        foreach ($usuarios as $u) {
            if ($u['rol_id'] == 1 && $u['activo'] == 1 && $u['id'] != $id) {
                $adminsActivos++;
            }
        }
        if ($adminsActivos == 0) {
            respuestaJson('error', 'No se puede desactivar al unico administrador activo');
        }
    }
    
    // Preparar datos para actualizar
    $datos = [
        'id' => $id,
        'nombre' => $nombre,
        'correo' => $correo,
        'rol_id' => $rol_id,
        'activo' => $activo
    ];
    
    // Actualizar usuario
    if ($usuarioModel->actualizarUsuario($datos)) {
        // Si se solicito cambiar clave, actualizarla
        if ($cambiarClave && validarRequerido($nuevaClave)) {
            if (strlen($nuevaClave) < 6) {
                respuestaJson('error', 'La nueva clave debe tener al menos 6 caracteres');
            }
            $passwordHash = password_hash($nuevaClave, PASSWORD_DEFAULT);
            $usuarioModel->actualizarClave($id, $passwordHash);
        }
        
        respuestaJson('exito', 'Usuario actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el usuario');
    }

// 6. Elimina un usuario via AJAX
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();
    
    // Verificar metodo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de usuario no valido');
    }
    
    // No permitir eliminar el propio usuario
    if ($id == $_SESSION['usuario_id']) {
        respuestaJson('error', 'No puede eliminar su propio usuario');
    }
    
    // Intentar eliminar
    if ($usuarioModel->eliminarUsuario($id)) {
        respuestaJson('exito', 'Usuario eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar al unico administrador del sistema');
    }

// 7. Cambia el estado de un usuario via AJAX
} elseif ($metodo === 'cambiarEstado') {
    verificarRolAdmin();
    
    // Verificar metodo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $estado = intval($_POST['activo'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de usuario no valido');
    }
    
    // No permitir desactivar el propio usuario
    if ($id == $_SESSION['usuario_id'] && $estado == 0) {
        respuestaJson('error', 'No puede desactivar su propio usuario');
    }
    
    // Intentar cambiar estado
    if ($usuarioModel->cambiarEstado($id, $estado)) {
        $mensaje = $estado == 1 ? 'Usuario activado exitosamente' : 'Usuario desactivado exitosamente';
        respuestaJson('exito', $mensaje);
    } else {
        respuestaJson('error', 'No se puede desactivar al unico administrador activo');
    }

// Fallback: Si el metodo solicitado no coincide con ninguna accion valida
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
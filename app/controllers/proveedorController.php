<?php
// Archivo: proveedorController.php
// Controlador para la gestion de proveedores

require_once __DIR__ . '/../models/ProveedorModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';
require_once __DIR__ . '/../helpers/validacionHelper.php';

$proveedorModel = new ProveedorModel();

if ($metodo === 'index') {
    verificarAutenticacion();

    $contenidoVista = __DIR__ . '/../views/proveedorListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();

    $proveedores = $proveedorModel->listarTodos();

    respuestaJson('exito', 'Proveedores obtenidos correctamente', [
        'proveedores' => $proveedores
    ]);
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();

    $termino = trim($_GET['termino'] ?? '');

    if (empty($termino)) {
        $proveedores = $proveedorModel->listarTodos();
    } else {
        $proveedores = $proveedorModel->buscarProveedores($termino);
    }

    respuestaJson('exito', 'Busqueda completada', [
        'proveedores' => $proveedores
    ]);
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    // Obtener datos del formulario
    $rif = strtoupper(trim($_POST['rif'] ?? ''));
    $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rubros = trim($_POST['rubros'] ?? '');

    // Validar campos obligatorios
    if (!validarRequerido($rif)) {
        respuestaJson('error', 'El RIF es obligatorio');
    }

    if (!validarRIF($rif)) {
        respuestaJson('error', 'El RIF debe tener formato valido (Ej: J-123456789)');
    }

    if (!validarRequerido($nombreEmpresa)) {
        respuestaJson('error', 'El nombre de la empresa es obligatorio');
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        respuestaJson('error', 'El telefono debe tener 11 digitos');
    }

    if (!empty($correo) && !validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
    }

    // Verificar que el RIF no exista
    if ($proveedorModel->rifExiste($rif)) {
        respuestaJson('error', 'Ya existe un proveedor con ese RIF');
    }

    // Preparar datos para insertar
    $datos = [
        'rif' => $rif,
        'nombre_empresa' => $nombreEmpresa,
        'direccion' => $direccion,
        'contacto' => $contacto,
        'telefono' => $telefono,
        'correo' => $correo,
        'rubros' => $rubros
    ];

    // Insertar proveedor
    if ($proveedorModel->insertarProveedor($datos)) {
        respuestaJson('exito', 'Proveedor creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el proveedor');
    }
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();

    $id = intval($_GET['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de proveedor no valido');
    }

    $proveedor = $proveedorModel->buscarPorId($id);

    if ($proveedor) {
        respuestaJson('exito', 'Proveedor obtenido correctamente', $proveedor);
    } else {
        respuestaJson('error', 'Proveedor no encontrado');
    }
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    // Obtener datos del formulario
    $id = intval($_POST['id'] ?? 0);
    $rif = strtoupper(trim($_POST['rif'] ?? ''));
    $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rubros = trim($_POST['rubros'] ?? '');

    // Validar
    if ($id < 1) {
        respuestaJson('error', 'ID de proveedor no valido');
    }

    if (!validarRequerido($rif)) {
        respuestaJson('error', 'El RIF es obligatorio');
    }

    if (!validarRIF($rif)) {
        respuestaJson('error', 'El RIF debe tener formato valido (Ej: J-123456789)');
    }

    if (!validarRequerido($nombreEmpresa)) {
        respuestaJson('error', 'El nombre de la empresa es obligatorio');
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        respuestaJson('error', 'El telefono debe tener 11 digitos');
    }

    if (!empty($correo) && !validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
    }

    // Verificar que el RIF no exista en otro proveedor
    if ($proveedorModel->rifExiste($rif, $id)) {
        respuestaJson('error', 'Ya existe otro proveedor con ese RIF');
    }

    // Preparar datos para actualizar
    $datos = [
        'id' => $id,
        'rif' => $rif,
        'nombre_empresa' => $nombreEmpresa,
        'direccion' => $direccion,
        'contacto' => $contacto,
        'telefono' => $telefono,
        'correo' => $correo,
        'rubros' => $rubros
    ];

    // Actualizar proveedor
    if ($proveedorModel->actualizarProveedor($datos)) {
        respuestaJson('exito', 'Proveedor actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el proveedor');
    }
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de proveedor no valido');
    }

    // Intentar eliminar
    if ($proveedorModel->eliminarProveedor($id)) {
        respuestaJson('exito', 'Proveedor eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar el proveedor. Tiene cuentas por pagar pendientes o insumos asociados.');
    }
} else {
    require_once __DIR__ . '/../views/error404View.php';
}

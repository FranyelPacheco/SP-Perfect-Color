<?php
// Archivo: inventarioController.php
// Controlador para la gestion de inventario (Procedimental)

require_once __DIR__ . '/../models/InventarioModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';
require_once __DIR__ . '/../helpers/validacionHelper.php';

// Instancia limpia del modelo para usar de forma procedimental
$inventarioModel = new InventarioModel();

// 1. Muestra la lista de insumos
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/inventarioListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene la lista de insumos en formato JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $insumos = $inventarioModel->listarTodos();
    $proveedores = $inventarioModel->listarProveedoresActivos();
    $alertas = $inventarioModel->obtenerAlertasStockBajo();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos,
        'proveedores' => $proveedores,
        'alertas' => $alertas
    ]);

// 3. Busca insumos por termino
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $insumos = $inventarioModel->listarTodos();
    } else {
        $insumos = $inventarioModel->buscarInsumos($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'insumos' => $insumos
    ]);

// 4. Guarda un nuevo insumo (solo Administrador)
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Obtener datos del formulario
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $unidadMedida = trim($_POST['unidad_medida'] ?? '');
    $stockActual = floatval($_POST['stock_actual'] ?? 0);
    $stockMinimo = floatval($_POST['stock_minimo'] ?? 5);
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $precioCompra = floatval($_POST['precio_compra'] ?? 0);
    $fechaVencimiento = trim($_POST['fecha_vencimiento'] ?? '');
    $proveedorId = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    
    // Validar campos obligatorios
    if (!validarRequerido($codigo)) {
        respuestaJson('error', 'El codigo es obligatorio');
    }
    
    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre del insumo es obligatorio');
    }
    
    if (!validarDecimalPositivo($precioVenta)) {
        respuestaJson('error', 'El precio de venta debe ser un numero positivo');
    }
    
    if (!empty($fechaVencimiento) && !validarFecha($fechaVencimiento)) {
        respuestaJson('error', 'La fecha de vencimiento no es valida');
    }
    
    // Verificar que el codigo no exista
    if ($inventarioModel->codigoExiste($codigo)) {
        respuestaJson('error', 'Ya existe un insumo con ese codigo');
    }
    
    // Preparar datos para insertar
    $datos = [
        'codigo' => $codigo,
        'nombre' => $nombre,
        'marca' => $marca,
        'categoria' => $categoria,
        'unidad_medida' => $unidadMedida,
        'stock_actual' => $stockActual,
        'stock_minimo' => $stockMinimo,
        'precio_venta' => $precioVenta,
        'precio_compra' => $precioCompra,
        'fecha_vencimiento' => !empty($fechaVencimiento) ? $fechaVencimiento : null,
        'proveedor_id' => $proveedorId
    ];
    
    // Insertar insumo
    if ($inventarioModel->insertarInsumo($datos)) {
        respuestaJson('exito', 'Insumo creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el insumo');
    }

// 5. Obtiene un insumo por ID para edicion
} elseif ($metodo === 'obtener') {
    verificarRolAdmin();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de insumo no valido');
    }
    
    $insumo = $inventarioModel->buscarPorId($id);
    
    if ($insumo) {
        respuestaJson('exito', 'Insumo obtenido correctamente', $insumo);
    } else {
        respuestaJson('error', 'Insumo no encontrado');
    }

// 6. Actualiza un insumo existente
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Obtener datos del formulario
    $id = intval($_POST['id'] ?? 0);
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $unidadMedida = trim($_POST['unidad_medida'] ?? '');
    $stockActual = floatval($_POST['stock_actual'] ?? 0);
    $stockMinimo = floatval($_POST['stock_minimo'] ?? 5);
    $precioVenta = floatval($_POST['precio_venta'] ?? 0);
    $precioCompra = floatval($_POST['precio_compra'] ?? 0);
    $fechaVencimiento = trim($_POST['fecha_vencimiento'] ?? '');
    $proveedorId = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    
    // Validar
    if ($id < 1) {
        respuestaJson('error', 'ID de insumo no valido');
    }
    
    if (!validarRequerido($codigo)) {
        respuestaJson('error', 'El codigo es obligatorio');
    }
    
    if (!validarRequerido($nombre)) {
        respuestaJson('error', 'El nombre del insumo es obligatorio');
    }
    
    if (!validarDecimalPositivo($precioVenta)) {
        respuestaJson('error', 'El precio de venta debe ser un numero positivo');
    }
    
    if (!empty($fechaVencimiento) && !validarFecha($fechaVencimiento)) {
        respuestaJson('error', 'La fecha de vencimiento no es valida');
    }
    
    // Verificar que el codigo no exista en otro insumo
    if ($inventarioModel->codigoExiste($codigo, $id)) {
        respuestaJson('error', 'Ya existe otro insumo con ese codigo');
    }
    
    // Preparar datos para actualizar
    $datos = [
        'id' => $id,
        'codigo' => $codigo,
        'nombre' => $nombre,
        'marca' => $marca,
        'categoria' => $categoria,
        'unidad_medida' => $unidadMedida,
        'stock_actual' => $stockActual,
        'stock_minimo' => $stockMinimo,
        'precio_venta' => $precioVenta,
        'precio_compra' => $precioCompra,
        'fecha_vencimiento' => !empty($fechaVencimiento) ? $fechaVencimiento : null,
        'proveedor_id' => $proveedorId
    ];
    
    // Actualizar insumo
    if ($inventarioModel->actualizarInsumo($datos)) {
        respuestaJson('exito', 'Insumo actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el insumo');
    }

// 7. Elimina un insumo
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id < 1) {
        respuestaJson('error', 'ID de insumo no valido');
    }
    
    if ($inventarioModel->eliminarInsumo($id)) {
        respuestaJson('exito', 'Insumo eliminado exitosamente');
    } else {
        respuestaJson('error', 'Error al eliminar el insumo');
    }

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
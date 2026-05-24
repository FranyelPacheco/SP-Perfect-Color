<?php
// Archivo: presupuestoController.php
// Controlador para la gestion de presupuestos (Procedimental)

require_once __DIR__ . '/../models/PresupuestoModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../models/InventarioModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';
require_once __DIR__ . '/../helpers/validacionHelper.php';

// Instancias limpias de los modelos para uso procedimental
$presupuestoModel = new PresupuestoModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();

// 1. Muestra la lista de presupuestos
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/presupuestoListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene la lista de presupuestos en formato JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $presupuestos = $presupuestoModel->listarTodos();
    
    respuestaJson('exito', 'Presupuestos obtenidos correctamente', [
        'presupuestos' => $presupuestos
    ]);

// 3. Busca presupuestos por cliente o estado
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();
    
    $termino = trim($_GET['termino'] ?? '');
    $estado = trim($_GET['estado'] ?? '');
    
    $presupuestos = $presupuestoModel->buscarPresupuestos($termino, $estado);
    
    respuestaJson('exito', 'Busqueda completada', [
        'presupuestos' => $presupuestos
    ]);

// 4. Muestra el formulario para crear un nuevo presupuesto
} elseif ($metodo === 'nuevo') {
    verificarRolVendedor();
    
    $contenidoVista = __DIR__ . '/../views/presupuestoFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 5. Obtiene los insumos disponibles para el presupuesto en formato JSON
} elseif ($metodo === 'obtenerInsumosAjax') {
    if (!isset($_SESSION['usuario_id'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $insumos = $inventarioModel->listarTodos();
    
    respuestaJson('exito', 'Insumos obtenidos correctamente', [
        'insumos' => $insumos
    ]);

// 6. Obtiene los clientes disponibles para el presupuesto en formato JSON
} elseif ($metodo === 'obtenerClientesAjax') {
    if (!isset($_SESSION['usuario_id'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// 7. Guarda un nuevo presupuesto via AJAX
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Obtener datos del presupuesto
    $clienteId = intval($_POST['cliente_id'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Obtener items del detalle desde el JSON enviado
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    // Validar
    if ($clienteId < 1) {
        respuestaJson('error', 'Debe seleccionar un cliente');
    }
    
    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un insumo al presupuesto');
    }
    
    // Calcular total y preparar detalle
    $total = 0;
    $detalle = [];
    
    foreach ($items as $item) {
        $insumoId = intval($item['insumo_id'] ?? 0);
        $cantidad = floatval($item['cantidad'] ?? 0);
        $precioUnitario = floatval($item['precio_unitario'] ?? 0);
        $subtotal = $cantidad * $precioUnitario;
        
        if ($insumoId < 1 || $cantidad <= 0 || $precioUnitario <= 0) {
            respuestaJson('error', 'Datos invalidos en uno de los items');
        }
        
        $total += $subtotal;
        
        $detalle[] = [
            'insumo_id' => $insumoId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal
        ];
    }
    
    // Preparar datos del presupuesto
    $datos = [
        'cliente_id' => $clienteId,
        'usuario_id' => $_SESSION['usuario_id'],
        'total' => $total,
        'observaciones' => $observaciones
    ];
    
    // Insertar presupuesto con su detalle
    try {
        $presupuestoId = $presupuestoModel->insertarPresupuesto($datos, $detalle);
        
        respuestaJson('exito', 'Presupuesto creado exitosamente', [
            'presupuesto_id' => $presupuestoId,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al crear el presupuesto: ' . $e->getMessage());
    }

// 8. Muestra el detalle de un presupuesto
} elseif ($metodo === 'ver') {
    verificarAutenticacion();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: ../presupuesto');
        exit;
    }
    
    $presupuesto = $presupuestoModel->buscarPorId($id);
    
    if (!$presupuesto) {
        header('Location: ../presupuesto');
        exit;
    }
    
    $detalle = $presupuestoModel->obtenerDetalle($id);
    
    $contenidoVista = __DIR__ . '/../views/presupuestoVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 9. Cambia el estado de un presupuesto via AJAX
} elseif ($metodo === 'cambiarEstado') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    $id = intval($_POST['id'] ?? 0);
    $estado = $_POST['estado'] ?? '';
    
    $estadosValidos = ['pendiente', 'aprobado', 'rechazado', 'convertido'];
    
    if ($id < 1 || !in_array($estado, $estadosValidos)) {
        respuestaJson('error', 'Datos invalidos');
    }
    
    if ($presupuestoModel->cambiarEstado($id, $estado)) {
        $mensajes = [
            'aprobado' => 'Presupuesto aprobado exitosamente',
            'rechazado' => 'Presupuesto rechazado',
            'convertido' => 'Presupuesto convertido a nota de entrega',
            'pendiente' => 'Presupuesto vuelto a estado pendiente'
        ];
        
        respuestaJson('exito', $mensajes[$estado] ?? 'Estado actualizado');
    } else {
        respuestaJson('error', 'Error al cambiar el estado');
    }

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
<?php
// Archivo: facturaController.php
// Controlador para la gestion de facturacion (Procedimental)

require_once __DIR__ . '/../models/FacturaModel.php';
require_once __DIR__ . '/../models/CajaModel.php';
require_once __DIR__ . '/../models/ClienteModel.php';
require_once __DIR__ . '/../models/InventarioModel.php';
require_once __DIR__ . '/../models/NotaEntregaModel.php';
require_once __DIR__ . '/../helpers/respuestaHelper.php';
require_once __DIR__ . '/../helpers/sesionHelper.php';

// Instancias limpias de los modelos para uso procedimental
$facturaModel = new FacturaModel();
$cajaModel = new CajaModel();
$clienteModel = new ClienteModel();
$inventarioModel = new InventarioModel();
$notaEntregaModel = new NotaEntregaModel();

// 1. Muestra la lista de facturas
if ($metodo === 'index') {
    verificarAutenticacion();
    
    $contenidoVista = __DIR__ . '/../views/facturaListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 2. Obtiene la lista de facturas en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();
    
    $facturas = $facturaModel->listarTodos();
    
    respuestaJson('exito', 'Facturas obtenidas correctamente', [
        'facturas' => $facturas
    ]);

// 3. Busca facturas
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();
    
    $termino = trim($_GET['termino'] ?? '');
    
    if (empty($termino)) {
        $facturas = $facturaModel->listarTodos();
    } else {
        $facturas = $facturaModel->buscarFacturas($termino);
    }
    
    respuestaJson('exito', 'Busqueda completada', [
        'facturas' => $facturas
    ]);

// 4. Muestra el formulario para nueva factura
} elseif ($metodo === 'nueva') {
    verificarRolVendedor();
    
    // Verificar que haya caja abierta
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    
    if (!$cajaAbierta) {
        header('Location: /SP%20Perfect%20Color/caja');
        exit;
    }
    
    $contenidoVista = __DIR__ . '/../views/facturaFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 5. Crea factura desde nota de entrega
} elseif ($metodo === 'crearDesdeNota') {
    verificarRolVendedor();
    
    $notaId = intval($_GET['id'] ?? 0);
    
    if ($notaId < 1) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }
    
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    
    if (!$cajaAbierta) {
        header('Location: /SP%20Perfect%20Color/caja');
        exit;
    }
    
    $nota = $notaEntregaModel->buscarPorId($notaId);
    if (!$nota) {
        header('Location: /SP%20Perfect%20Color/notaEntrega');
        exit;
    }
    
    $contenidoVista = __DIR__ . '/../views/facturaFormView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// 6. Obtiene clientes para el formulario
} elseif ($metodo === 'obtenerClientesAjax') {
    if (!isset($_SESSION['usuario_id'])) {
        respuestaJson('error', 'Sesion expirada');
    }
    
    $clientes = $clienteModel->listarTodos();
    
    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// 7. Guarda una nueva factura
} elseif ($metodo === 'guardar') {
    verificarRolVendedor();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }
    
    // Verificar caja abierta
    $cajaAbierta = $cajaModel->cajaAbierta($_SESSION['usuario_id']);
    if (!$cajaAbierta) {
        respuestaJson('error', 'Debe abrir la caja antes de facturar');
    }
    
    $clienteId = intval($_POST['cliente_id'] ?? 0);
    $metodoPago = $_POST['metodo_pago'] ?? '';
    $notaEntregaId = !empty($_POST['nota_entrega_id']) ? intval($_POST['nota_entrega_id']) : null;
    $items = json_decode($_POST['items'] ?? '[]', true);
    
    // Validar
    $metodosValidos = ['Efectivo', 'Punto de Venta', 'Pago Movil', 'Credito'];
    
    if ($clienteId < 1) {
        respuestaJson('error', 'Debe seleccionar un cliente');
    }
    
    if (!in_array($metodoPago, $metodosValidos)) {
        respuestaJson('error', 'Metodo de pago no valido');
    }
    
    if (empty($items)) {
        respuestaJson('error', 'Debe agregar al menos un item');
    }
    
    // Si es a credito, verificar que el cliente no tenga cuentas vencidas
    if ($metodoPago === 'Credito') {
        $consulta = "SELECT COUNT(*) as total FROM cuentas_cobrar 
                     WHERE cliente_id = :cliente_id AND estado = 'moroso'";
        $stmt = $cajaModel->obtenerInstancia()->obtenerConexion()->prepare($consulta);
        $stmt->bindParam(':cliente_id', $clienteId, PDO::PARAM_INT);
        $stmt->execute();
        // Esta validacion se hara desde el modelo
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
    
    // Preparar datos
    $estado = ($metodoPago === 'Credito') ? 'pendiente' : 'pagado';
    
    $datos = [
        'cliente_id' => $clienteId,
        'usuario_id' => $_SESSION['usuario_id'],
        'caja_id' => $cajaAbierta['id'],
        'total' => $total,
        'metodo_pago' => $metodoPago,
        'estado' => $estado,
        'nota_entrega_id' => $notaEntregaId
    ];
    
    try {
        $resultado = $facturaModel->crearFactura($datos, $detalle);
        
        respuestaJson('exito', 'Factura creada exitosamente', [
            'factura_id' => $resultado['factura_id'],
            'numero_factura' => $resultado['numero_factura'],
            'total' => $total
        ]);
    } catch (PDOException $e) {
        respuestaJson('error', 'Error al crear la factura: ' . $e->getMessage());
    }

// 8. Muestra el detalle de una factura
} elseif ($metodo === 'ver') {
    verificarAutenticacion();
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id < 1) {
        header('Location: /SP%20Perfect%20Color/factura');
        exit;
    }
    
    $factura = $facturaModel->buscarPorId($id);
    
    if (!$factura) {
        header('Location: /SP%20Perfect%20Color/factura');
        exit;
    }
    
    $detalle = $facturaModel->obtenerDetalle($id);
    
    $contenidoVista = __DIR__ . '/../views/facturaVerView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// Fallback: Metodo desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}
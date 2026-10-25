<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\{ClienteModel, ProveedorModel, InventarioModel, UsuarioModel, RolModel, BancoModel, TipoPagoModel, TasaCambioModel};
use function App\Helpers\{respuestaJson, verificarAutenticacion, verificarPermiso, verificarRolAdmin, tienePermiso};

// OBJETIVO: Validación en vivo de campos únicos (cédula, RIF, correo, código, nombres, fecha de tasa)
// NOTA: la lista blanca evita que la petición elija tablas o columnas arbitrarias
const VALIDACIONES_UNICAS = [
    'cliente.cedula'     => [ClienteModel::class,    'clientes',    'cedula', 'id_cliente',     'Ya existe un cliente con esta cédula',       'cliente'],
    'cliente.correo'     => [ClienteModel::class,    'clientes',    'correo', 'id_cliente',     'Este correo ya está registrado en clientes', 'cliente'],
    'proveedor.rif'      => [ProveedorModel::class,  'proveedores', 'rif',    'id_proveedor',   'Ya existe un proveedor con este RIF',        'proveedor'],
    'proveedor.correo'   => [ProveedorModel::class,  'proveedores', 'correo', 'id_proveedor',   'Este correo ya está registrado en proveedores', 'proveedor'],
    'inventario.codigo'  => [InventarioModel::class, 'productos',   'codigo', 'id_producto',    'Ya existe un producto con este código',      'inventario'],
    'usuario.correo'     => [UsuarioModel::class,    'usuarios',    'correo', 'id_usuario',     'Este correo ya está registrado',             'usuario'],
    'rol.nombre'         => [RolModel::class,        'roles',       'nombre', 'id_rol',         'Ya existe un rol con este nombre',           'usuario'],
    'banco.nombre'       => [BancoModel::class,      'banco',       'nombre', 'id_banco',       'Ya existe un banco con este nombre',         'admin'],
    'tipoPago.nombre'    => [TipoPagoModel::class,   'tipo_pago',   'nombre', 'id_tipo_pago',   'Ya existe un tipo de pago con este nombre',  'admin'],
    'tasaCambio.fecha'   => [TasaCambioModel::class, 'tasa_cambio', 'fecha',  'id_tasa_cambio', 'Ya existe una tasa registrada para esta fecha', 'admin'],
];

if ($metodo === 'unico') {
    verificarAutenticacion();

    $clave = ($_GET['entidad'] ?? '') . '.' . ($_GET['campo'] ?? '');
    $valor = trim((string)($_GET['valor'] ?? ''));
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    if (!isset(VALIDACIONES_UNICAS[$clave])) {
        respuestaJson('error', 'Validación no soportada');
    }
    [$modelo, $tabla, $columna, $pk, $mensaje, $permiso] = VALIDACIONES_UNICAS[$clave];

    // Un usuario puede validar su propio correo desde el perfil
    $esPropioPerfil = $clave === 'usuario.correo' && $id !== null && $id === (int)($_SESSION['id_usuario'] ?? 0);
    if (!$esPropioPerfil) {
        $permiso === 'admin' ? verificarRolAdmin() : verificarPermiso($permiso);
    }

    if ($valor === '') {
        respuestaJson('exito', 'Sin valor', ['existe' => false, 'inactivo_id' => null, 'mensaje' => '']);
    }

    $fila = (new $modelo())->verificarUnico($tabla, $columna, $pk, $valor, $id, $clave === 'tasaCambio.fecha');
    respuestaJson('exito', 'Verificación realizada', [
        'existe'      => (bool)($fila && $fila['activo']),
        'inactivo_id' => $fila && !$fila['activo'] ? $fila['id'] : null,
        'mensaje'     => $mensaje,
    ]);
} else {
    respuestaJson('error', 'Método no encontrado');
}

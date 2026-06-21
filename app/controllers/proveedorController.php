<?php
// Archivo: proveedorController.php
// Controlador para la gestion de proveedores

namespace App\Controllers;

use App\Models\ProveedorModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarAcceso;
use function App\Helpers\verificarRolAdmin;
use function App\Helpers\validarRequerido;
use function App\Helpers\validarRIF;
use function App\Helpers\validarTelefono;
use function App\Helpers\validarCorreo;

$proveedorModel = new ProveedorModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista del listado de proveedores
if ($metodo === 'index') {
    verificarAcceso([1]);

    $contenidoVista = __DIR__ . '/../views/proveedorListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
// FUNCIÓN: listarAjax
// OBJETIVO: Obtiene el listado completo de proveedores en JSON
} elseif ($metodo === 'listarAjax') {
    verificarAcceso([1]);

    $proveedores = $proveedorModel->listarTodos();

    respuestaJson('exito', 'Proveedores obtenidos correctamente', [
        'proveedores' => $proveedores
    ]);
// FUNCIÓN: buscarAjax
// OBJETIVO: Busca proveedores por término de búsqueda o devuelve todos
} elseif ($metodo === 'buscarAjax') {
    verificarAcceso([1]);

    $termino = trim($_GET['termino'] ?? '');

    if (empty($termino)) {
        $proveedores = $proveedorModel->listarTodos();
    } else {
        $proveedores = $proveedorModel->buscarProveedores($termino);
    }

    respuestaJson('exito', 'Busqueda completada', [
        'proveedores' => $proveedores
    ]);
// FUNCIÓN: guardar
// OBJETIVO: Crea un nuevo proveedor o reactiva uno inactivo, con teléfonos y rubros
} elseif ($metodo === 'guardar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $rif = strtoupper(trim($_POST['rif'] ?? ''));
    $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rubros = $_POST['rubros'] ?? [];

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

    if ($proveedorModel->rifExiste($rif)) {
        respuestaJson('error', 'Ya existe un proveedor con ese RIF');
    }

    $inactivoId = $proveedorModel->buscarInactivoPorRIF($rif);
    if ($inactivoId) {
        if ($proveedorModel->actualizarProveedor($inactivoId, $rif, $nombreEmpresa, $direccion, $contacto, $correo)) {
            $proveedorModel->eliminarTelefonos($inactivoId);
            if (!empty($telefono)) {
                $proveedorModel->insertarTelefono($inactivoId, $telefono, 'movil');
            }
            $proveedorModel->eliminarRubros($inactivoId);
            foreach ($rubros as $rubroId) {
                $rubroId = intval($rubroId);
                if ($rubroId > 0) {
                    $proveedorModel->insertarRubro($inactivoId, $rubroId);
                }
            }
            respuestaJson('exito', 'Proveedor reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el proveedor');
        }
    }

    $nuevoId = $proveedorModel->insertarProveedor($rif, $nombreEmpresa, $direccion, $contacto, $correo);
    if ($nuevoId) {
        if (!empty($telefono)) {
            $proveedorModel->insertarTelefono($nuevoId, $telefono, 'movil');
        }
        foreach ($rubros as $rubroId) {
            $rubroId = intval($rubroId);
            if ($rubroId > 0) {
                $proveedorModel->insertarRubro($nuevoId, $rubroId);
            }
        }
        respuestaJson('exito', 'Proveedor creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el proveedor');
    }
// FUNCIÓN: obtener
// OBJETIVO: Obtiene un proveedor por ID para edición
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
// FUNCIÓN: actualizar
// OBJETIVO: Actualiza un proveedor existente con sus teléfonos y rubros
} elseif ($metodo === 'actualizar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $rif = strtoupper(trim($_POST['rif'] ?? ''));
    $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rubros = $_POST['rubros'] ?? [];

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

    if ($proveedorModel->rifExiste($rif, $id)) {
        respuestaJson('error', 'Ya existe otro proveedor con ese RIF');
    }

    if ($proveedorModel->actualizarProveedor($id, $rif, $nombreEmpresa, $direccion, $contacto, $correo)) {
        $proveedorModel->eliminarTelefonos($id);
        if (!empty($telefono)) {
            $proveedorModel->insertarTelefono($id, $telefono, 'movil');
        }
        $proveedorModel->eliminarRubros($id);
        foreach ($rubros as $rubroId) {
            $rubroId = intval($rubroId);
            if ($rubroId > 0) {
                $proveedorModel->insertarRubro($id, $rubroId);
            }
        }
        respuestaJson('exito', 'Proveedor actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el proveedor');
    }
// FUNCIÓN: eliminar
// OBJETIVO: Eliminación lógica de un proveedor (soft-delete)
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de proveedor no valido');
    }

    if ($proveedorModel->eliminarProveedor($id)) {
        respuestaJson('exito', 'Proveedor eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar el proveedor. Tiene cuentas por pagar pendientes o insumos asociados.');
    }
} else {
    require_once __DIR__ . '/../views/error404View.php';
}

<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use function App\Helpers\{
    respuestaJson,
    verificarAutenticacion,
    verificarPermiso,
    verificarRolAdmin,
    validarRequerido,
    validarCedula,
    validarTelefono,
    validarCorreo
};

$clienteModel = new ClienteModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de clientes con datos precargados
if ($metodo === 'index') {
    verificarPermiso('cliente');

    $clientes = $clienteModel->listarTodos();

    $contenidoVista = __DIR__ . '/../views/clienteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve listado JSON de todos los clientes para DataTable
} elseif ($metodo === 'listarAjax') {
    verificarPermiso('cliente');

    $clientes = $clienteModel->listarTodos();

    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca clientes por término de búsqueda (cédula, nombre, apellido) y devuelve JSON
} elseif ($metodo === 'buscarAjax') {
    verificarPermiso('cliente');

    $termino = trim($_GET['termino'] ?? '');

    if (empty($termino)) {
        $clientes = $clienteModel->listarTodos();
    } else {
        $clientes = $clienteModel->buscarClientes($termino);
    }

    respuestaJson('exito', 'Busqueda completada', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: verificarCedulaAjax
// OBJETIVO: Verifica en tiempo real si una cédula ya está registrada
} elseif ($metodo === 'verificarCedulaAjax') {
    verificarPermiso('cliente');
    $cedula = trim($_GET['cedula'] ?? '');
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;
    $existe = !empty($cedula) && $clienteModel->cedulaExiste($cedula, $id);
    respuestaJson('exito', 'Verificacion realizada', ['existe' => $existe]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un cliente nuevo o reactiva uno inactivo con la misma cédula; incluye validaciones de campos
// NOTA: El teléfono se guarda en tabla separada (telefono_cliente); verifica unicidad de cédula antes de insertar
} elseif ($metodo === 'guardar') {
    verificarPermiso('cliente');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $tipoDoc = trim($_POST['tipo_doc'] ?? 'V');
    $cedulaNum = trim($_POST['cedula_num'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    if (empty($cedula) && !empty($cedulaNum)) {
        $cedula = $tipoDoc . '-' . $cedulaNum;
    }
    
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    
    $operadora = trim($_POST['operadora'] ?? '');
    $telefonoNum = trim($_POST['telefono_num'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    if (empty($telefono) && !empty($telefonoNum)) {
        $telefono = $operadora . $telefonoNum;
    }

    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!validarRequerido($cedula)) {
        respuestaJson('error', 'La cedula es obligatoria');
    }

    if (!preg_match('/^[VEJ]-\d{6,9}$/i', $cedula)) {
        respuestaJson('error', 'El documento debe tener formato V, E o J seguido de 6 a 9 digitos');
    }

    if (!validarRequerido($nombres)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }

    if (!validarRequerido($apellidos)) {
        respuestaJson('error', 'El apellido es obligatorio');
    }

    if (!validarRequerido($correo)) {
        respuestaJson('error', 'El correo electronico es obligatorio');
    }

    if (!validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        respuestaJson('error', 'El telefono debe tener 11 digitos');
    }

    if ($clienteModel->cedulaExiste($cedula)) {
        respuestaJson('error', 'Ya existe un cliente con esa cedula');
    }

    $inactivoId = $clienteModel->buscarInactivoPorCedula($cedula);
    if ($inactivoId) {
        if ($clienteModel->actualizarCliente($inactivoId, $cedula, $nombres, $apellidos, $correo, $direccion)) {
            if (!empty($telefono)) {
                $clienteModel->eliminarTelefonos($inactivoId);
                $clienteModel->insertarTelefono($inactivoId, $telefono, 'movil');
            }
            respuestaJson('exito', 'Cliente reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el cliente');
        }
    }

    $nuevoId = $clienteModel->insertarCliente($cedula, $nombres, $apellidos, $correo, $direccion);
    if ($nuevoId) {
        if (!empty($telefono)) {
            $clienteModel->insertarTelefono($nuevoId, $telefono, 'movil');
        }
        respuestaJson('exito', 'Cliente creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el cliente');
    }

// FUNCIÓN: obtener
// OBJETIVO: Devuelve los datos de un cliente por ID en formato JSON (incluye teléfonos)
} elseif ($metodo === 'obtener') {
    verificarPermiso('cliente');

    $id = intval($_GET['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de cliente no valido');
    }

    $cliente = $clienteModel->buscarPorId($id);

    if ($cliente) {
        respuestaJson('exito', 'Cliente obtenido correctamente', $cliente);
    } else {
        respuestaJson('error', 'Cliente no encontrado');
    }

// FUNCIÓN: actualizar
// OBJETIVO: Actualiza los datos de un cliente existente; reemplaza teléfonos (elimina viejos, inserta nuevo)
} elseif ($metodo === 'actualizar') {
    verificarPermiso('cliente');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $tipoDoc = trim($_POST['tipo_doc'] ?? 'V');
    $cedulaNum = trim($_POST['cedula_num'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    if (empty($cedula) && !empty($cedulaNum)) {
        $cedula = $tipoDoc . '-' . $cedulaNum;
    }

    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');

    $operadora = trim($_POST['operadora'] ?? '');
    $telefonoNum = trim($_POST['telefono_num'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    if (empty($telefono) && !empty($telefonoNum)) {
        $telefono = $operadora . $telefonoNum;
    }

    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($id < 1) {
        respuestaJson('error', 'ID de cliente no valido');
    }

    if (!validarRequerido($cedula)) {
        respuestaJson('error', 'La cedula es obligatoria');
    }

    if (!preg_match('/^[VEJ]-\d{6,9}$/i', $cedula)) {
        respuestaJson('error', 'El documento debe tener formato V, E o J seguido de 6 a 9 digitos');
    }

    if (!validarRequerido($nombres)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }

    if (!validarRequerido($apellidos)) {
        respuestaJson('error', 'El apellido es obligatorio');
    }

    if (!validarRequerido($correo)) {
        respuestaJson('error', 'El correo electronico es obligatorio');
    }

    if (!validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        respuestaJson('error', 'El telefono debe tener 11 digitos');
    }

    if ($clienteModel->cedulaExiste($cedula, $id)) {
        respuestaJson('error', 'Ya existe otro cliente con esa cedula');
    }

    if ($clienteModel->actualizarCliente($id, $cedula, $nombres, $apellidos, $correo, $direccion)) {
        $clienteModel->eliminarTelefonos($id);
        if (!empty($telefono)) {
            $clienteModel->insertarTelefono($id, $telefono, 'movil');
        }
        respuestaJson('exito', 'Cliente actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el cliente');
    }

// FUNCIÓN: eliminar
// OBJETIVO: Elimina (soft-delete) un cliente por ID; previene si tiene cuentas por cobrar pendientes
} elseif ($metodo === 'eliminar') {
    verificarRolAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de cliente no valido');
    }

    if ($clienteModel->eliminarCliente($id)) {
        respuestaJson('exito', 'Cliente eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar el cliente. Tiene cuentas por cobrar pendientes.');
    }

// FUNCIÓN: 404
// OBJETIVO: Muestra página de error 404 para método desconocido
} else {
    require_once __DIR__ . '/../views/error404View.php';
}

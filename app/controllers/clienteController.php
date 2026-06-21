<?php

namespace App\Controllers;

use App\Models\ClienteModel;
use function App\Helpers\respuestaJson;
use function App\Helpers\verificarAutenticacion;
use function App\Helpers\verificarRolAdmin;
use function App\Helpers\validarRequerido;
use function App\Helpers\validarCedula;
use function App\Helpers\validarTelefono;
use function App\Helpers\validarCorreo;

$clienteModel = new ClienteModel();

// FUNCIÓN: index
// OBJETIVO: Renderiza la vista de listado de clientes con datos precargados
if ($metodo === 'index') {
    verificarAutenticacion();

    $clientes = $clienteModel->listarTodos();

    $contenidoVista = __DIR__ . '/../views/clienteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';

// FUNCIÓN: listarAjax
// OBJETIVO: Devuelve listado JSON de todos los clientes para DataTable
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();

    $clientes = $clienteModel->listarTodos();

    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: buscarAjax
// OBJETIVO: Busca clientes por término de búsqueda (cédula, nombre, apellido) y devuelve JSON
} elseif ($metodo === 'buscarAjax') {
    verificarAutenticacion();

    $termino = trim($_GET['termino'] ?? '');

    if (empty($termino)) {
        $clientes = $clienteModel->listarTodos();
    } else {
        $clientes = $clienteModel->buscarClientes($termino);
    }

    respuestaJson('exito', 'Busqueda completada', [
        'clientes' => $clientes
    ]);

// FUNCIÓN: guardar
// OBJETIVO: Crea un cliente nuevo o reactiva uno inactivo con la misma cédula; incluye validaciones de campos
// NOTA: El teléfono se guarda en tabla separada (telefono_cliente); verifica unicidad de cédula antes de insertar
} elseif ($metodo === 'guardar') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $cedula = trim($_POST['cedula'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (!validarRequerido($cedula)) {
        respuestaJson('error', 'La cedula es obligatoria');
    }

    if (!validarCedula($cedula)) {
        respuestaJson('error', 'La cedula debe tener entre 7 y 8 digitos');
    }

    if (!validarRequerido($nombres)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }

    if (!validarRequerido($apellidos)) {
        respuestaJson('error', 'El apellido es obligatorio');
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        respuestaJson('error', 'El telefono debe tener 11 digitos');
    }

    if (!empty($correo) && !validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
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
    verificarAutenticacion();

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
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);
    $cedula = trim($_POST['cedula'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($id < 1) {
        respuestaJson('error', 'ID de cliente no valido');
    }

    if (!validarRequerido($cedula)) {
        respuestaJson('error', 'La cedula es obligatoria');
    }

    if (!validarCedula($cedula)) {
        respuestaJson('error', 'La cedula debe tener entre 7 y 8 digitos');
    }

    if (!validarRequerido($nombres)) {
        respuestaJson('error', 'El nombre es obligatorio');
    }

    if (!validarRequerido($apellidos)) {
        respuestaJson('error', 'El apellido es obligatorio');
    }

    if (!empty($telefono) && !validarTelefono($telefono)) {
        respuestaJson('error', 'El telefono debe tener 11 digitos');
    }

    if (!empty($correo) && !validarCorreo($correo)) {
        respuestaJson('error', 'El correo electronico no es valido');
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
    verificarAutenticacion();

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

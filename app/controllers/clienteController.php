<?php
// Archivo: clienteController.php
// Controlador para la gestion de clientes

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

if ($metodo === 'index') {
    verificarAutenticacion();

    $clientes = $clienteModel->listarTodos();

    $contenidoVista = __DIR__ . '/../views/clienteListView.php';
    require_once __DIR__ . '/../views/plantillaBase.php';
} elseif ($metodo === 'listarAjax') {
    verificarAutenticacion();

    $clientes = $clienteModel->listarTodos();

    respuestaJson('exito', 'Clientes obtenidos correctamente', [
        'clientes' => $clientes
    ]);
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
} elseif ($metodo === 'guardar') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    // Obtener datos del formulario
    $cedula = trim($_POST['cedula'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // Validar campos obligatorios
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

    // Verificar que la cedula no exista
    if ($clienteModel->cedulaExiste($cedula)) {
        respuestaJson('error', 'Ya existe un cliente con esa cedula');
    }

    // Preparar datos para insertar
    $datos = [
        'cedula' => $cedula,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'correo' => $correo,
        'direccion' => $direccion
    ];

    // Si existe un cliente inactivo con la misma cedula, reactivarlo
    $inactivoId = $clienteModel->buscarInactivoPorCedula($cedula);
    if ($inactivoId) {
        $datos['id'] = $inactivoId;
        if ($clienteModel->actualizarCliente($datos)) {
            if (!empty($telefono)) {
                $clienteModel->eliminarTelefonos($inactivoId);
                $clienteModel->insertarTelefono($inactivoId, $telefono, 'movil');
            }
            respuestaJson('exito', 'Cliente reactivado exitosamente');
        } else {
            respuestaJson('error', 'Error al reactivar el cliente');
        }
    }

    // Insertar cliente
    $nuevoId = $clienteModel->insertarCliente($datos);
    if ($nuevoId) {
        // Guardar telefono en la tabla separada
        if (!empty($telefono)) {
            $clienteModel->insertarTelefono($nuevoId, $telefono, 'movil');
        }
        respuestaJson('exito', 'Cliente creado exitosamente');
    } else {
        respuestaJson('error', 'Error al crear el cliente');
    }
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
} elseif ($metodo === 'actualizar') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    // Obtener datos del formulario
    $id = intval($_POST['id'] ?? 0);
    $cedula = trim($_POST['cedula'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    // Validar
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

    // Verificar que la cedula no exista en otro cliente
    if ($clienteModel->cedulaExiste($cedula, $id)) {
        respuestaJson('error', 'Ya existe otro cliente con esa cedula');
    }

    // Preparar datos para actualizar
    $datos = [
        'id' => $id,
        'cedula' => $cedula,
        'nombres' => $nombres,
        'apellidos' => $apellidos,
        'correo' => $correo,
        'direccion' => $direccion
    ];

    // Actualizar cliente
    if ($clienteModel->actualizarCliente($datos)) {
        // Actualizar telefonos
        $clienteModel->eliminarTelefonos($id);
        if (!empty($telefono)) {
            $clienteModel->insertarTelefono($id, $telefono, 'movil');
        }
        respuestaJson('exito', 'Cliente actualizado exitosamente');
    } else {
        respuestaJson('error', 'Error al actualizar el cliente');
    }
} elseif ($metodo === 'eliminar') {
    verificarAutenticacion();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respuestaJson('error', 'Metodo no permitido');
    }

    $id = intval($_POST['id'] ?? 0);

    if ($id < 1) {
        respuestaJson('error', 'ID de cliente no valido');
    }

    // Intentar eliminar
    if ($clienteModel->eliminarCliente($id)) {
        respuestaJson('exito', 'Cliente eliminado exitosamente');
    } else {
        respuestaJson('error', 'No se puede eliminar el cliente. Tiene cuentas por cobrar pendientes.');
    }
} else {
    require_once __DIR__ . '/../views/error404View.php';
}

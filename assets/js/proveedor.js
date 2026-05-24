// Archivo: proveedor.js
// Manejo de la vista de gestion de proveedores

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const tablaProveedores = document.getElementById('cuerpoTablaProveedores');
    const busquedaProveedores = document.getElementById('busquedaProveedores');
    const btnNuevoProveedor = document.getElementById('btnNuevoProveedor');
    const modalProveedor = document.getElementById('modalProveedor');
    const btnCerrarModal = document.getElementById('btnCerrarModalProveedor');
    const btnCancelar = document.getElementById('btnCancelarProveedor');
    const formularioProveedor = document.getElementById('formularioProveedor');
    const tituloModal = document.getElementById('tituloModalProveedor');
    const proveedorId = document.getElementById('proveedorId');
    const rifProveedor = document.getElementById('rifProveedor');
    const nombreEmpresaProveedor = document.getElementById('nombreEmpresaProveedor');
    const contactoProveedor = document.getElementById('contactoProveedor');
    const telefonoProveedor = document.getElementById('telefonoProveedor');
    const correoProveedor = document.getElementById('correoProveedor');
    const direccionProveedor = document.getElementById('direccionProveedor');
    const rubrosProveedor = document.getElementById('rubrosProveedor');
    const mensajeError = document.getElementById('mensajeErrorProveedor');

    // Cargar lista de proveedores al iniciar
    cargarProveedores();

    // Evento para buscar proveedores mientras se escribe
    let temporizadorBusqueda;
    if (busquedaProveedores) {
        busquedaProveedores.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                buscarProveedores(busquedaProveedores.value.trim());
            }, 300);
        });
    }

    // Evento para abrir modal de nuevo proveedor
    if (btnNuevoProveedor) {
        btnNuevoProveedor.addEventListener('click', function() {
            abrirModalCrear();
        });
    }

    // Eventos para cerrar modal
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
        btnCancelar.addEventListener('click', cerrarModal);
    }

    // Evento para enviar formulario
    if (formularioProveedor) {
        formularioProveedor.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            await guardarProveedor();
        });
    }

    // Cierra el modal al hacer clic fuera del contenido
    if (modalProveedor) {
        modalProveedor.addEventListener('click', function(evento) {
            if (evento.target === modalProveedor) {
                cerrarModal();
            }
        });
    }

    // Formatear RIF automaticamente
    if (rifProveedor) {
        rifProveedor.addEventListener('input', function() {
            let valor = this.value.toUpperCase();
            // Permitir formato J-123456789
            if (valor.length === 1 && /^[JGVEP]$/.test(valor)) {
                valor = valor + '-';
            }
            this.value = valor;
        });
    }

    // Permitir solo numeros en telefono
    if (telefonoProveedor) {
        telefonoProveedor.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Funcion para cargar la lista de proveedores
    async function cargarProveedores() {
        try {
            const respuesta = await fetch('proveedor/listarAjax');
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarProveedores(resultado.datos.proveedores);
            }
        } catch (error) {
            console.error('Error al cargar proveedores:', error);
        }
    }

    // Funcion para buscar proveedores
    async function buscarProveedores(termino) {
        try {
            const respuesta = await fetch('proveedor/buscarAjax?termino=' + encodeURIComponent(termino));
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarProveedores(resultado.datos.proveedores);
            }
        } catch (error) {
            console.error('Error al buscar proveedores:', error);
        }
    }

    // Muestra los proveedores en la tabla
    function mostrarProveedores(proveedores) {
        tablaProveedores.innerHTML = '';

        if (proveedores.length === 0) {
            const colspan = document.querySelector('#tablaProveedores thead tr').children.length;
            tablaProveedores.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center;">No hay proveedores registrados</td></tr>';
            return;
        }

        const esAdmin = document.getElementById('btnNuevoProveedor') !== null;

        proveedores.forEach(function(proveedor) {
            const fila = document.createElement('tr');
            
            // RIF
            const celdaRIF = document.createElement('td');
            celdaRIF.textContent = proveedor.rif;
            fila.appendChild(celdaRIF);
            
            // Empresa
            const celdaEmpresa = document.createElement('td');
            celdaEmpresa.textContent = proveedor.nombre_empresa;
            fila.appendChild(celdaEmpresa);
            
            // Contacto
            const celdaContacto = document.createElement('td');
            celdaContacto.textContent = proveedor.contacto || '-';
            fila.appendChild(celdaContacto);
            
            // Telefono
            const celdaTelefono = document.createElement('td');
            celdaTelefono.textContent = proveedor.telefono || '-';
            fila.appendChild(celdaTelefono);
            
            // Rubros
            const celdaRubros = document.createElement('td');
            celdaRubros.textContent = proveedor.rubros || '-';
            fila.appendChild(celdaRubros);
            
            // Acciones (solo Administrador)
            if (esAdmin) {
                const celdaAcciones = document.createElement('td');
                celdaAcciones.className = 'acciones';
                
                // Boton editar
                const btnEditar = document.createElement('button');
                btnEditar.className = 'btn-primario';
                btnEditar.textContent = 'Editar';
                btnEditar.addEventListener('click', function() {
                    abrirModalEditar(proveedor.id);
                });
                celdaAcciones.appendChild(btnEditar);
                
                // Boton eliminar
                const btnEliminar = document.createElement('button');
                btnEliminar.className = 'btn-peligro';
                btnEliminar.textContent = 'Eliminar';
                btnEliminar.addEventListener('click', function() {
                    eliminarProveedor(proveedor.id, proveedor.nombre_empresa);
                });
                celdaAcciones.appendChild(btnEliminar);
                
                fila.appendChild(celdaAcciones);
            }
            
            tablaProveedores.appendChild(fila);
        });
    }

    // Abre el modal en modo creacion
    function abrirModalCrear() {
        tituloModal.textContent = 'Nuevo Proveedor';
        proveedorId.value = '';
        rifProveedor.value = '';
        rifProveedor.disabled = false;
        nombreEmpresaProveedor.value = '';
        contactoProveedor.value = '';
        telefonoProveedor.value = '';
        correoProveedor.value = '';
        direccionProveedor.value = '';
        rubrosProveedor.value = '';
        mensajeError.style.display = 'none';
        
        modalProveedor.style.display = 'flex';
    }

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('proveedor/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const proveedor = resultado.datos;
                
                tituloModal.textContent = 'Editar Proveedor';
                proveedorId.value = proveedor.id;
                rifProveedor.value = proveedor.rif;
                rifProveedor.disabled = true;
                nombreEmpresaProveedor.value = proveedor.nombre_empresa;
                contactoProveedor.value = proveedor.contacto || '';
                telefonoProveedor.value = proveedor.telefono || '';
                correoProveedor.value = proveedor.correo || '';
                direccionProveedor.value = proveedor.direccion || '';
                rubrosProveedor.value = proveedor.rubros || '';
                mensajeError.style.display = 'none';
                
                modalProveedor.style.display = 'flex';
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener proveedor:', error);
            mostrarNotificacion('Error al cargar los datos del proveedor', 'error');
        }
    }

    // Cierra el modal
    function cerrarModal() {
        modalProveedor.style.display = 'none';
        formularioProveedor.reset();
        mensajeError.style.display = 'none';
    }

    // Guarda o actualiza un proveedor
    async function guardarProveedor() {
        const id = proveedorId.value;
        const esEdicion = id !== '';
        
        // Validar RIF
        const rif = rifProveedor.value.trim().toUpperCase();
        if (!rif) {
            mostrarError('El RIF es obligatorio');
            return;
        }
        
        const formatoRIF = /^[JGVEP]-\d{8,9}$/;
        if (!formatoRIF.test(rif)) {
            mostrarError('El RIF debe tener formato valido (Ej: J-123456789)');
            return;
        }
        
        if (!nombreEmpresaProveedor.value.trim()) {
            mostrarError('El nombre de la empresa es obligatorio');
            return;
        }
        
        if (telefonoProveedor.value.trim() && telefonoProveedor.value.trim().length !== 11) {
            mostrarError('El telefono debe tener 11 digitos');
            return;
        }
        
        // Determinar URL
        const url = esEdicion ? 'proveedor/actualizar' : 'proveedor/guardar';
        
        // Preparar datos
        const formData = new FormData(formularioProveedor);
        formData.set('rif', rif);
        
        if (esEdicion) {
            formData.set('id', id);
        }
        
        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cerrarModal();
                cargarProveedores();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar proveedor:', error);
            mostrarError('Error de conexion al guardar el proveedor');
        }
    }

    // Elimina un proveedor
    async function eliminarProveedor(id, nombre) {
        if (!confirm('Esta seguro de eliminar al proveedor ' + nombre + '?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        
        try {
            const respuesta = await fetch('proveedor/eliminar', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cargarProveedores();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar proveedor:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.style.display = 'block';
    }
});
// Archivo: inventario.js
// Manejo de la vista de gestion de inventario

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const tablaInsumos = document.getElementById('cuerpoTablaInsumos');
    const busquedaInsumos = document.getElementById('busquedaInsumos');
    const btnNuevoInsumo = document.getElementById('btnNuevoInsumo');
    const modalInsumo = document.getElementById('modalInsumo');
    const btnCerrarModal = document.getElementById('btnCerrarModalInsumo');
    const btnCancelar = document.getElementById('btnCancelarInsumo');
    const formularioInsumo = document.getElementById('formularioInsumo');
    const tituloModal = document.getElementById('tituloModalInsumo');
    const insumoId = document.getElementById('insumoId');
    const codigoInsumo = document.getElementById('codigoInsumo');
    const nombreInsumo = document.getElementById('nombreInsumo');
    const marcaInsumo = document.getElementById('marcaInsumo');
    const categoriaInsumo = document.getElementById('categoriaInsumo');
    const unidadMedidaInsumo = document.getElementById('unidadMedidaInsumo');
    const stockActualInsumo = document.getElementById('stockActualInsumo');
    const stockMinimoInsumo = document.getElementById('stockMinimoInsumo');
    const precioVentaInsumo = document.getElementById('precioVentaInsumo');
    const precioCompraInsumo = document.getElementById('precioCompraInsumo');
    const fechaVencimientoInsumo = document.getElementById('fechaVencimientoInsumo');
    const proveedorInsumo = document.getElementById('proveedorInsumo');
    const mensajeError = document.getElementById('mensajeErrorInsumo');
    const alertasStockBajo = document.getElementById('alertasStockBajo');
    const contenidoAlertas = document.getElementById('contenidoAlertas');

    let proveedoresGlobal = [];
    const esAdmin = document.getElementById('btnNuevoInsumo') !== null;

    // Cargar lista de insumos al iniciar
    cargarInsumos();

    // Evento para buscar insumos mientras se escribe
    let temporizadorBusqueda;
    if (busquedaInsumos) {
        busquedaInsumos.addEventListener('keyup', function() {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(function() {
                buscarInsumos(busquedaInsumos.value.trim());
            }, 300);
        });
    }

    // Evento para abrir modal de nuevo insumo
    if (btnNuevoInsumo) {
        btnNuevoInsumo.addEventListener('click', function() {
            abrirModalCrear();
        });
    }

    // Eventos para cerrar modal
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModal);
        btnCancelar.addEventListener('click', cerrarModal);
    }

    // Evento para enviar formulario
    if (formularioInsumo) {
        formularioInsumo.addEventListener('submit', async function(evento) {
            evento.preventDefault();
            await guardarInsumo();
        });
    }

    // Cierra el modal al hacer clic fuera del contenido
    if (modalInsumo) {
        modalInsumo.addEventListener('click', function(evento) {
            if (evento.target === modalInsumo) {
                cerrarModal();
            }
        });
    }

    // Funcion para cargar la lista de insumos
    async function cargarInsumos() {
        try {
            const respuesta = await fetch('inventario/listarAjax');
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarInsumos(resultado.datos.insumos);
                
                // Guardar proveedores globalmente
                if (resultado.datos.proveedores) {
                    proveedoresGlobal = resultado.datos.proveedores;
                    llenarSelectProveedores();
                }
                
                // Mostrar alertas de stock bajo
                if (resultado.datos.alertas && resultado.datos.alertas.length > 0) {
                    mostrarAlertas(resultado.datos.alertas);
                }
            }
        } catch (error) {
            console.error('Error al cargar insumos:', error);
        }
    }

    // Funcion para buscar insumos
    async function buscarInsumos(termino) {
        try {
            const respuesta = await fetch('inventario/buscarAjax?termino=' + encodeURIComponent(termino));
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                mostrarInsumos(resultado.datos.insumos);
            }
        } catch (error) {
            console.error('Error al buscar insumos:', error);
        }
    }

    // Muestra los insumos en la tabla
    function mostrarInsumos(insumos) {
        tablaInsumos.innerHTML = '';

        if (insumos.length === 0) {
            const colspan = esAdmin ? 8 : 7;
            tablaInsumos.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align: center;">No hay insumos registrados</td></tr>';
            return;
        }

        insumos.forEach(function(insumo) {
            const fila = document.createElement('tr');
            
            // Codigo
            const celdaCodigo = document.createElement('td');
            celdaCodigo.textContent = insumo.codigo;
            fila.appendChild(celdaCodigo);
            
            // Nombre
            const celdaNombre = document.createElement('td');
            celdaNombre.textContent = insumo.nombre;
            fila.appendChild(celdaNombre);
            
            // Marca
            const celdaMarca = document.createElement('td');
            celdaMarca.textContent = insumo.marca || '-';
            fila.appendChild(celdaMarca);
            
            // Categoria
            const celdaCategoria = document.createElement('td');
            celdaCategoria.textContent = insumo.categoria || '-';
            fila.appendChild(celdaCategoria);
            
            // Stock
            const celdaStock = document.createElement('td');
            const spanStock = document.createElement('span');
            spanStock.textContent = formatearMoneda(insumo.stock_actual);
            if (parseFloat(insumo.stock_actual) <= parseFloat(insumo.stock_minimo)) {
                spanStock.className = 'stock-bajo';
                spanStock.title = 'Stock bajo - Minimo: ' + insumo.stock_minimo;
            } else {
                spanStock.className = 'stock-normal';
            }
            celdaStock.appendChild(spanStock);
            fila.appendChild(celdaStock);
            
            // Precio Venta
            const celdaPrecio = document.createElement('td');
            celdaPrecio.textContent = formatearMoneda(insumo.precio_venta);
            fila.appendChild(celdaPrecio);
            
            // Proveedor
            const celdaProveedor = document.createElement('td');
            celdaProveedor.textContent = insumo.proveedor_nombre || '-';
            fila.appendChild(celdaProveedor);
            
            // Acciones (solo Administrador)
            if (esAdmin) {
                const celdaAcciones = document.createElement('td');
                celdaAcciones.className = 'acciones';
                
                // Boton editar
                const btnEditar = document.createElement('button');
                btnEditar.className = 'btn-primario';
                btnEditar.textContent = 'Editar';
                btnEditar.addEventListener('click', function() {
                    abrirModalEditar(insumo.id);
                });
                celdaAcciones.appendChild(btnEditar);
                
                // Boton eliminar
                const btnEliminar = document.createElement('button');
                btnEliminar.className = 'btn-peligro';
                btnEliminar.textContent = 'Eliminar';
                btnEliminar.addEventListener('click', function() {
                    eliminarInsumo(insumo.id, insumo.nombre);
                });
                celdaAcciones.appendChild(btnEliminar);
                
                fila.appendChild(celdaAcciones);
            }
            
            tablaInsumos.appendChild(fila);
        });
    }

    // Muestra las alertas de stock bajo
    function mostrarAlertas(alertas) {
        alertasStockBajo.style.display = 'block';
        contenidoAlertas.innerHTML = '';
        
        alertas.forEach(function(alerta) {
            const div = document.createElement('div');
            div.className = 'alerta-item';
            div.textContent = alerta.codigo + ' - ' + alerta.nombre + ' (Stock: ' + formatearMoneda(alerta.stock_actual) + ', Minimo: ' + formatearMoneda(alerta.stock_minimo) + ')';
            contenidoAlertas.appendChild(div);
        });
    }

    // Llena el select de proveedores
    function llenarSelectProveedores() {
        if (!proveedorInsumo) return;
        
        proveedorInsumo.innerHTML = '<option value="">Seleccione un proveedor...</option>';
        proveedoresGlobal.forEach(function(proveedor) {
            const opcion = document.createElement('option');
            opcion.value = proveedor.id;
            opcion.textContent = proveedor.rif + ' - ' + proveedor.nombre_empresa;
            proveedorInsumo.appendChild(opcion);
        });
    }

    // Abre el modal en modo creacion
    function abrirModalCrear() {
        tituloModal.textContent = 'Nuevo Insumo';
        insumoId.value = '';
        codigoInsumo.value = '';
        codigoInsumo.disabled = false;
        nombreInsumo.value = '';
        marcaInsumo.value = '';
        categoriaInsumo.value = '';
        unidadMedidaInsumo.value = '';
        stockActualInsumo.value = '0';
        stockMinimoInsumo.value = '5';
        precioVentaInsumo.value = '0';
        precioCompraInsumo.value = '0';
        fechaVencimientoInsumo.value = '';
        proveedorInsumo.value = '';
        mensajeError.style.display = 'none';
        
        modalInsumo.style.display = 'flex';
    }

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const respuesta = await fetch('inventario/obtener?id=' + id);
            const resultado = await respuesta.json();

            if (resultado.estado === 'exito') {
                const insumo = resultado.datos;
                
                tituloModal.textContent = 'Editar Insumo';
                insumoId.value = insumo.id;
                codigoInsumo.value = insumo.codigo;
                codigoInsumo.disabled = true;
                nombreInsumo.value = insumo.nombre;
                marcaInsumo.value = insumo.marca || '';
                categoriaInsumo.value = insumo.categoria || '';
                unidadMedidaInsumo.value = insumo.unidad_medida || '';
                stockActualInsumo.value = insumo.stock_actual;
                stockMinimoInsumo.value = insumo.stock_minimo;
                precioVentaInsumo.value = insumo.precio_venta;
                precioCompraInsumo.value = insumo.precio_compra;
                fechaVencimientoInsumo.value = insumo.fecha_vencimiento || '';
                proveedorInsumo.value = insumo.proveedor_id || '';
                mensajeError.style.display = 'none';
                
                modalInsumo.style.display = 'flex';
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener insumo:', error);
            mostrarNotificacion('Error al cargar los datos del insumo', 'error');
        }
    }

    // Cierra el modal
    function cerrarModal() {
        modalInsumo.style.display = 'none';
        formularioInsumo.reset();
        mensajeError.style.display = 'none';
    }

    // Guarda o actualiza un insumo
    async function guardarInsumo() {
        const id = insumoId.value;
        const esEdicion = id !== '';
        
        // Validar campos
        if (!codigoInsumo.value.trim()) {
            mostrarError('El codigo es obligatorio');
            return;
        }
        
        if (!nombreInsumo.value.trim()) {
            mostrarError('El nombre del insumo es obligatorio');
            return;
        }
        
        const precioVenta = parseFloat(precioVentaInsumo.value);
        if (isNaN(precioVenta) || precioVenta <= 0) {
            mostrarError('El precio de venta debe ser un numero positivo');
            return;
        }
        
        // Determinar URL
        const url = esEdicion ? 'inventario/actualizar' : 'inventario/guardar';
        
        // Preparar datos
        const formData = new FormData(formularioInsumo);
        
        if (esEdicion) {
            formData.set('id', id);
            formData.set('codigo', codigoInsumo.value);
        }
        
        try {
            const respuesta = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cerrarModal();
                cargarInsumos();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar insumo:', error);
            mostrarError('Error de conexion al guardar el insumo');
        }
    }

    // Elimina un insumo
    async function eliminarInsumo(id, nombre) {
        if (!confirm('Esta seguro de eliminar el insumo ' + nombre + '?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        
        try {
            const respuesta = await fetch('inventario/eliminar', {
                method: 'POST',
                body: formData
            });
            
            const resultado = await respuesta.json();
            
            if (resultado.estado === 'exito') {
                cargarInsumos();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al eliminar insumo:', error);
            mostrarNotificacion('Error de conexion', 'error');
        }
    }

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        mensajeError.textContent = mensaje;
        mensajeError.style.display = 'block';
    }
});
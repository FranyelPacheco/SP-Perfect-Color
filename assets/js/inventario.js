// Archivo: inventario.js
// Manejo de la vista de gestion de inventario

$(function() {
    // Referencias a elementos del DOM
    const $modalInsumo = $('#modalInsumo');
    const $formularioInsumo = $('#formularioInsumo');
    const $tituloModal = $('#tituloModalInsumo');
    const $insumoId = $('#insumoId');
    const $codigoInsumo = $('#codigoInsumo');
    const $nombreInsumo = $('#nombreInsumo');
    const $tipoProductoInsumo = $('#tipoProductoInsumo');
    const $rubroInsumo = $('#rubroInsumo');
    const $unidadMedidaInsumo = $('#unidadMedidaInsumo');
    const $stockActualInsumo = $('#stockActualInsumo');
    const $stockMinimoInsumo = $('#stockMinimoInsumo');
    const $precioVentaInsumo = $('#precioVentaInsumo');
    const $proveedorInsumo = $('#proveedorInsumo');
    const $mensajeError = $('#mensajeErrorInsumo');
    const $alertasStockBajo = $('#alertasStockBajo');
    const $contenidoAlertas = $('#contenidoAlertas');
    const $feedbackCodigoInsumo = $('#feedbackCodigoInsumo');
    const $btnGuardarInsumo = $('#btnGuardarInsumo');

    let timerCodigo = null;

    function verificarCodigoEnTiempoReal() {
        const cod = $codigoInsumo.val().trim().toUpperCase();
        const id = $insumoId.val() || '';

        clearTimeout(timerCodigo);

        if (!cod) {
            $codigoInsumo.removeClass('is-invalid is-valid');
            $feedbackCodigoInsumo.addClass('d-none');
            $btnGuardarInsumo.prop('disabled', false);
            return;
        }

        timerCodigo = setTimeout(async function() {
            try {
                const res = await api.get('inventario/verificarCodigoAjax', { codigo: cod, id: id });
                if (res.estado === 'exito') {
                    if (res.datos.existe) {
                        $codigoInsumo.addClass('is-invalid').removeClass('is-valid');
                        $feedbackCodigoInsumo.text('El insumo con código ' + cod + ' ya existe en el sistema').removeClass('d-none');
                        $btnGuardarInsumo.prop('disabled', true);
                    } else {
                        $codigoInsumo.removeClass('is-invalid').addClass('is-valid');
                        $feedbackCodigoInsumo.addClass('d-none');
                        $btnGuardarInsumo.prop('disabled', false);
                    }
                }
            } catch (e) {
                console.error('Error al verificar código:', e);
            }
        }, 300);
    }

    $codigoInsumo.on('input', verificarCodigoEnTiempoReal);

    let proveedoresGlobal = [];
    let rubrosGlobal = [];
    let insumosGlobal = [];
    let basesGlobal = [];
    let presentacionesGlobal = [];

    // Los tintes base se cuentan en envases por tamaño (1, 1/2 ... 1/128 de galón)
    function esBase(p) {
        return parseInt(p.id_tipo_producto) === 1;
    }

    function factor128(fraccion) {
        const m = String(fraccion || '1').match(/^(\d+)\/(\d+)$/);
        return m ? Math.round(parseInt(m[1]) * 128 / parseInt(m[2])) : 128;
    }

    // Presentación a mostrar: fracción de galón (bases y preparados) o la unidad de medida (simples)
    function presentacionDe(p) {
        const tipo = parseInt(p.id_tipo_producto);
        const unidad = String(p.unidad_medida || '');
        let texto = null;
        if (tipo === 1) texto = String(p.fraccion || '1');
        else if (tipo === 3) texto = (unidad.match(/\d+\/\d+/) || [])[0] || (/gal/i.test(unidad) ? '1' : null);
        if (texto === null) return { fraccion: null, etiqueta: unidad || '-' };
        const partes = texto.split('/');
        const valor = partes.length === 2 ? parseInt(partes[0]) / parseInt(partes[1]) : 1;
        return { fraccion: valor, etiqueta: (valor === 1 ? '1' : texto) + ' Galón' };
    }

    function formatearGalones(galones) {
        const total = Math.round((parseFloat(galones) || 0) * 128);
        let texto = Math.floor(total / 128) + ' gal';
        let resto = total % 128;
        $.each([64, 32, 16, 8, 4, 2, 1], function (i, f) {
            if (resto >= f) {
                texto += ' + 1/' + (128 / f);
                resto -= f;
            }
        });
        return texto;
    }

    const esAdmin = $('#btnPrepararMezcla').length > 0;

    // Agrega una opción a un select si todavía no existe
    function asegurarOpcion($select, valor, texto) {
        if (!$select.find('option').filter(function () { return this.value === String(valor); }).length) {
            $select.append(new Option(texto, valor));
        }
    }

    // Solo enteros (sin punto, coma, signo ni notación científica)
    function soloEnteros($input, teclasBloqueadas) {
        $input.on('keydown', function(e) {
            if (teclasBloqueadas.includes(e.key)) e.preventDefault();
        });
        $input.on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Ajusta la unidad de medida según el tipo de producto
    function ajustarUnidadSegunTipo(unidadPrevia) {
        if (!$tipoProductoInsumo.length || !$unidadMedidaInsumo.length) return;
        if (String($tipoProductoInsumo.val()) === '1') {
            // Base solo puede ser galón
            $unidadMedidaInsumo.html('<option value="Galon">Galón</option>').val('Galon');
        } else {
            // Simple: Unidad o KG
            $unidadMedidaInsumo.html('<option value="Unidad">Unidad</option><option value="KG">KG</option>');
            $unidadMedidaInsumo.val(unidadPrevia === 'Unidad' || unidadPrevia === 'KG' ? unidadPrevia : 'Unidad');
        }
    }

    $tipoProductoInsumo.on('change', function() {
        ajustarUnidadSegunTipo();
    });

    // Restringir inputs de stock a solo números enteros
    soloEnteros($stockActualInsumo.add($stockMinimoInsumo), ['.', ',', 'e', 'E', '-']);

    // Cargar lista de insumos al iniciar
    cargarInsumos();

    // Eventos para cerrar modal
    $('#btnCerrarModalInsumo, #btnCancelarInsumo').on('click', function() {
        bootstrap.Modal.getInstance($modalInsumo[0]).hide();
    });

    // Evento para enviar formulario
    $formularioInsumo.on('submit', async function(evento) {
        evento.preventDefault();
        await guardarInsumo();
    });

    // Limpiar formulario cuando el modal se cierra
    $modalInsumo.on('hidden.bs.modal', function () {
        $formularioInsumo[0].reset();
        $mensajeError.addClass('d-none');
    });

    // Funcion para cargar la lista de insumos
    async function cargarInsumos() {
        try {
            const resultado = await api.get('inventario/listarAjax');

            if (resultado.estado === 'exito') {
                insumosGlobal = resultado.datos.insumos || [];
                presentacionesGlobal = resultado.datos.presentaciones || [];
                mostrarInsumos(resultado.datos.insumos);

                if (resultado.datos.proveedores) {
                    proveedoresGlobal = resultado.datos.proveedores;
                    llenarSelectProveedores();
                }
                if (resultado.datos.rubros) {
                    rubrosGlobal = resultado.datos.rubros;
                    llenarSelectRubros(rubrosGlobal);
                }

                if (resultado.datos.alertas && resultado.datos.alertas.length > 0) {
                    mostrarAlertas(resultado.datos.alertas);
                }
            }
        } catch (error) {
            console.error('No se pudo cargar el catálogo de productos. Recargue la página:', error);
        }
    }

    // Muestra los insumos en la tabla usando API DataTables con columns explicitas
    function mostrarInsumos(insumos) {
        if (!$.fn.DataTable.isDataTable('#tablaInsumos')) {
            const columnsDef = [
                { data: 'codigo', render: $.fn.dataTable.render.text() },
                {
                    data: 'nombre',
                    render: function(d, type, row) {
                        if (type !== 'display') return d;
                        return (row.id_producto_padre ? '<i class="bi bi-arrow-return-right text-muted me-1"></i>' : '') + escapeHtml(d);
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        if (!row) return '';
                        const tipoId = parseInt(row.id_tipo_producto);
                        const tipoNom = row.tipo_producto_nombre || (tipoId === 1 ? 'Base' : (tipoId === 3 ? 'Preparado' : 'Simple'));
                        let badgeClass = 'badge-tipo-simple';
                        let iconClass = 'bi-box-seam';
                        if (tipoId === 1 || tipoNom.toLowerCase() === 'base') {
                            badgeClass = 'badge-tipo-base';
                            iconClass = 'bi-droplet-half';
                        } else if (tipoId === 3 || tipoNom.toLowerCase() === 'preparado') {
                            badgeClass = 'badge-tipo-preparado';
                            iconClass = 'bi-palette-fill';
                        }
                        return '<span class="' + badgeClass + '"><i class="bi ' + iconClass + ' me-1"></i>' + tipoNom + '</span>';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        if (!row) return '';
                        const pres = presentacionDe(row);
                        if (type !== 'display') return pres.etiqueta;
                        if (pres.fraccion === null) {
                            return '';
                        }
                        return '<span class="badge-presentacion' + (pres.fraccion === 1 ? ' entero' : '') + '" title="' + Math.round(pres.fraccion * 128) + '/128 de galón">' +
                            '<span class="pres-pie" style="--p:' + (pres.fraccion * 100) + '"></span>' + escapeHtml(pres.etiqueta) + '</span>';
                    }
                },
                {
                    data: 'rubro_nombre',
                    render: function(d) { return escapeHtml(d) || '-'; }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        if (!row) return '';
                        const stockVal = Math.floor(parseFloat(row.stock_actual) || 0);
                        const stockMin = Math.floor(parseFloat(row.stock_minimo) || 0);
                        const alertaActiva = parseInt(row.alerta_activa) !== 0 && !(parseInt(row.id_tipo_producto) === 3 && stockMin <= 0);
                        const stockStyle = alertaActiva && stockVal <= stockMin ? 'stock-bajo' : 'stock-normal';
                        const totalFamilia = (esBase(row) && !row.id_producto_padre && row.volumen_familia_128 != null)
                            ? '<div class="small text-muted">Total: ' + formatearGalones(row.volumen_familia_128 / 128) + '</div>' : '';
                        return '<span class="' + stockStyle + '">' + stockVal + '</span>' + totalFamilia;
                    }
                },
                {
                    data: 'precio_venta',
                    render: function(d) { return d != null ? formatearMoneda(d) : '0,00'; }
                },
                {
                    data: 'proveedores_nombre',
                    render: function(d) { return escapeHtml(d) || '-'; }
                }
            ];

            if (esAdmin) {
                columnsDef.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        if (!row) return '';
                        const nom = escapeHtml(row.nombre);
                        return '<div class="d-flex gap-2">' +
                            '<button class="btn btn-sm btn-warning btn-editar-insumo" data-id="' + row.id_insumo + '" title="Editar" aria-label="Editar" data-bs-toggle="tooltip"><i class="bi bi-pencil-square"></i></button>' +
                            '<button class="btn btn-sm btn-danger btn-eliminar-insumo" data-id="' + row.id_insumo + '" data-nombre="' + nom + '" title="Eliminar" aria-label="Eliminar" data-bs-toggle="tooltip"><i class="bi bi-trash-fill"></i></button>' +
                            '</div>';
                    }
                });
            }

            $('#tablaInsumos').DataTable({
                dom: 'lrtip',
                language: window.DATATABLES_SPANISH,
                columns: columnsDef
            });
        }

        const table = $('#tablaInsumos').DataTable();
        table.clear();

        if (Array.isArray(insumos)) {
            $.each(insumos, function(i, insumo) {
                table.row.add(insumo);
            });
        }

        table.draw();
    }

    // Delegacion de eventos robusta para botones de accion
    alHacerClick('.btn-editar-insumo', function() {
        const id = parseInt($(this).attr('data-id'));
        if (!isNaN(id)) {
            abrirModalEditar(id);
        } else {
            console.error('ID de insumo inválido:', $(this).attr('data-id'));
        }
    });

    alHacerClick('.btn-eliminar-insumo', function() {
        const id = parseInt($(this).attr('data-id'));
        const nombre = $(this).attr('data-nombre') || 'este insumo';
        if (!isNaN(id)) {
            eliminarInsumo(id, nombre);
        }
    });

    // Silencia la alerta de stock mínimo de una presentación hija
    function silenciarAlerta(id) {
        api.post('inventario/silenciarAlerta', { id: id, activa: 0 })
            .then(function(res) {
                mostrarNotificacion(res.mensaje, res.estado === 'exito' ? 'exito' : 'error');
                if (res.estado === 'exito') {
                    $alertasStockBajo.addClass('d-none');
                    cargarInsumos();
                }
            })
            .catch(function() { mostrarNotificacion('No se pudo conectar con el servidor. Intente de nuevo.', 'error'); });
    }

    // Muestra las alertas de stock bajo
    function mostrarAlertas(alertas) {
        if (!$alertasStockBajo.length || !$contenidoAlertas.length) return;
        $alertasStockBajo.removeClass('d-none');
        $contenidoAlertas.empty();

        $.each(alertas, function(i, alerta) {
            const stockEnt = Math.floor(parseFloat(alerta.stock_actual) || 0);
            const minEnt = Math.floor(parseFloat(alerta.stock_minimo) || 0);
            const $div = $('<div class="alerta-item"></div>')
                .append($('<span></span>').text(alerta.codigo + ' - ' + alerta.nombre + ' (Stock: ' + stockEnt + ', Mínimo: ' + minEnt + ')'));
            if (alerta.id_producto_padre && esAdmin) {
                $('<button type="button" class="btn btn-sm btn-outline-secondary ms-2 py-0"><i class="bi bi-bell-slash me-1"></i>Silenciar</button>')
                    .on('click', function() { silenciarAlerta(alerta.id_producto); })
                    .appendTo($div);
            }
            $contenidoAlertas.append($div);
        });
    }

    // Llena el select de proveedores
    function llenarSelectProveedores() {
        $proveedorInsumo.html('<option value="">Seleccione un proveedor...</option>');
        $.each(proveedoresGlobal, function(i, proveedor) {
            $proveedorInsumo.append($('<option></option>').val(proveedor.id_proveedor).text(proveedor.rif + ' - ' + proveedor.nombre_empresa));
        });
    }

    function llenarSelectRubros(rubros) {
        $rubroInsumo.html('<option value="">Seleccione un rubro</option>');
        $.each(rubros, function(i, rubro) {
            $rubroInsumo.append($('<option></option>').val(rubro.id_rubro).text(rubro.nombre));
        });
    }

    // Carga los rubros filtrados por proveedor
    async function cargarRubrosPorProveedor(proveedorId) {
        if (!proveedorId) {
            llenarSelectRubros(rubrosGlobal);
            $rubroInsumo.prop('disabled', false);
            return [];
        }
        try {
            const resultado = await api.get('inventario/obtenerRubrosPorProveedorAjax', { id_proveedor: proveedorId });
            if (resultado.estado === 'exito') {
                llenarSelectRubros(resultado.datos.rubros);
                return resultado.datos.rubros;
            }
        } catch (error) {
            console.error('Error al cargar rubros por proveedor:', error);
        }
        return [];
    }

    $proveedorInsumo.on('change', async function() {
        const valor = $(this).val();
        const rubros = await cargarRubrosPorProveedor(valor);
        if (valor && rubros.length === 1) {
            $rubroInsumo.val(rubros[0].id_rubro).prop('disabled', true);
        } else {
            $rubroInsumo.val('').prop('disabled', false);
        }
    });

    // Abre el modal en modo edicion
    async function abrirModalEditar(id) {
        try {
            const resultado = await api.get('inventario/obtener', { id: id });

            if (resultado.estado === 'exito') {
                const insumo = resultado.datos;

                $tituloModal.text('Editar Producto');
                $insumoId.val(insumo.id_insumo);
                $codigoInsumo.val(insumo.codigo).prop('disabled', true).removeClass('is-invalid is-valid');
                $feedbackCodigoInsumo.addClass('d-none');
                $btnGuardarInsumo.prop('disabled', false);
                $nombreInsumo.val(insumo.nombre);
                $tipoProductoInsumo.val(insumo.id_tipo_producto || '2');
                ajustarUnidadSegunTipo(insumo.unidad_medida);
                if ($tipoProductoInsumo.length) {
                    asegurarOpcion($tipoProductoInsumo, insumo.id_tipo_producto, insumo.tipo_producto_nombre || 'Preparado');
                    $tipoProductoInsumo.val(insumo.id_tipo_producto).prop('disabled', true);
                }
                if ($unidadMedidaInsumo.length) {
                    if (insumo.unidad_medida) asegurarOpcion($unidadMedidaInsumo, insumo.unidad_medida, insumo.unidad_medida);
                    $unidadMedidaInsumo.val(insumo.unidad_medida || $unidadMedidaInsumo.val()).prop('disabled', true);
                }
                const $grupoAlerta = $('#grupoAlertaActivaInsumo');
                const $chkAlerta = $('#alertaActivaInsumo');
                if ($grupoAlerta.length && $chkAlerta.length) {
                    $grupoAlerta.toggleClass('d-none', !insumo.id_producto_padre);
                    $chkAlerta.prop('checked', parseInt(insumo.alerta_activa) !== 0);
                }
                $stockActualInsumo.val(Math.round(parseFloat(insumo.stock_actual) || 0)).prop('readOnly', true);
                $stockMinimoInsumo.val(Math.round(parseFloat(insumo.stock_minimo) || 0));
                $precioVentaInsumo.val(insumo.precio_venta);
                $proveedorInsumo.val(insumo.proveedores_id || '').prop('disabled', true);
                // Cargar rubros segun el proveedor del insumo
                await cargarRubrosPorProveedor(insumo.proveedores_id || '');
                $rubroInsumo.val(insumo.id_rubro || '').prop('disabled', true);
                $mensajeError.addClass('d-none');

                bootstrap.Modal.getOrCreateInstance($modalInsumo[0]).show();
            } else {
                mostrarNotificacion(resultado.mensaje, 'error');
            }
        } catch (error) {
            console.error('Error al obtener insumo:', error);
            mostrarNotificacion('No se pudieron cargar los datos del producto. Actualice la página e intente de nuevo.', 'error');
        }
    }

    // Guarda o actualiza un insumo
    async function guardarInsumo() {
        const id = $insumoId.val();
        const esEdicion = id !== '';

        if (!$codigoInsumo.val().trim()) {
            mostrarError('El código es obligatorio');
            return;
        }

        if (!$nombreInsumo.val().trim()) {
            mostrarError('El nombre del insumo es obligatorio');
            return;
        }

        const precioVenta = parseFloat($precioVentaInsumo.val());
        if (isNaN(precioVenta) || precioVenta <= 0) {
            mostrarError('El precio de venta debe ser un número positivo');
            return;
        }

        const stockActual = parseInt($stockActualInsumo.val(), 10);
        const stockMinimo = parseInt($stockMinimoInsumo.val(), 10);
        if (isNaN(stockActual) || stockActual < 0) {
            mostrarError('El stock actual debe ser un número entero no negativo');
            return;
        }
        if (isNaN(stockMinimo) || stockMinimo < 0) {
            mostrarError('El stock mínimo debe ser un número entero no negativo');
            return;
        }

        const formData = new FormData($formularioInsumo[0]);

        if (esEdicion) {
            formData.set('id', id);
            formData.set('codigo', $codigoInsumo.val());
        }

        try {
            const resultado = await api.post(esEdicion ? 'inventario/actualizar' : 'inventario/guardar', formData);

            if (resultado.estado === 'exito') {
                bootstrap.Modal.getInstance($modalInsumo[0]).hide();
                cargarInsumos();
                mostrarNotificacion(resultado.mensaje, 'exito');
            } else {
                mostrarError(resultado.mensaje);
            }
        } catch (error) {
            console.error('Error al guardar insumo:', error);
            mostrarError('No se pudo guardar el insumo. Revise su conexión e intente de nuevo.');
        }
    }

    // Elimina un insumo
    function eliminarInsumo(id, nombre) {
        confirmarConModal('Eliminar', '¿Está seguro de eliminar el insumo ' + escapeHtml(nombre) + '?', function() {
            api.post('inventario/eliminar', { id: id })
                .then(function(resultado) {
                    if (resultado.estado === 'exito') {
                        cargarInsumos();
                        mostrarNotificacion(resultado.mensaje, 'exito');
                    } else {
                        mostrarNotificacion(resultado.mensaje, 'error');
                    }
                })
                .catch(function(error) {
                    console.error('Error al eliminar insumo:', error);
                    mostrarNotificacion('No se pudo eliminar el insumo. Revise su conexión e intente de nuevo.', 'error');
                });
        });
    }

    // Enlazar busqueda manual a DataTables
    vincularBusqueda('#busquedaInsumos', '#tablaInsumos');

    // Muestra un mensaje de error en el modal
    function mostrarError(mensaje) {
        $mensajeError.text(mensaje).removeClass('d-none');
    }

    // =========================================================================
    // SECCIÓN: PREPARAR MEZCLA / COLOR PERSONALIZADO (TALLER)
    // =========================================================================
    const $btnPrepararMezcla = $('#btnPrepararMezcla');
    const $modalPrepararMezcla = $('#modalPrepararMezcla');
    const $formMezcla = $('#formularioMezcla');
    const $cuerpoBasesMezcla = $('#cuerpoBasesMezcla');
    const $btnAgregarFilaBase = $('#btnAgregarFilaBase');
    const $contadorBasesMezcla = $('#contadorBasesMezcla');
    const $mezclaNombre = $('#mezclaNombre');
    const $mezclaPresentacion = $('#mezclaPresentacion');
    const $mezclaCantidad = $('#mezclaCantidad');
    const $mezclaPrecioVenta = $('#mezclaPrecioVenta');
    const $mezclaSubtotalEstimado = $('#mezclaSubtotalEstimado');
    const $mensajeErrorMezcla = $('#mensajeErrorMezcla');

    $mezclaNombre.on('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\/\#\.]/g, '').substring(0, 40);
    });

    async function cargarBasesParaMezcla() {
        try {
            const res = await api.get('inventario/listarBasesAjax');
            if (res.estado === 'exito') {
                basesGlobal = res.datos.bases || [];
            }
        } catch (e) {
            console.error('Error al cargar bases:', e);
        }
    }

    $btnPrepararMezcla.on('click', async function() {
        if ($formMezcla.length) $formMezcla[0].reset();
        $mensajeErrorMezcla.addClass('d-none');
        $cuerpoBasesMezcla.empty();
        await cargarBasesParaMezcla();
        agregarFilaBaseMezcla();
        recalcularTotalesMezcla();
        if ($modalPrepararMezcla.length) {
            bootstrap.Modal.getOrCreateInstance($modalPrepararMezcla[0]).show();
        }
    });

    $btnAgregarFilaBase.on('click', agregarFilaBaseMezcla);

    // Cierra las listas de sugerencias al hacer clic fuera de su fila
    $(document).on('click', function(e) {
        $cuerpoBasesMezcla.find('tr').each(function() {
            if (!$.contains(this, e.target)) {
                $(this).find('.lista-sugerencias-base').removeClass('show');
                $(this).find('td.celda-buscador-activa').removeClass('celda-buscador-activa');
            }
        });
    });

    function agregarFilaBaseMezcla() {
        if (!$cuerpoBasesMezcla.length) return;
        if ($cuerpoBasesMezcla.children('tr').length >= 6) {
            mostrarNotificacion('El límite máximo es de 6 tintes base por mezcla.', 'error');
            return;
        }

        const $fila = $('<tr></tr>').html(
            '<td class="position-relative">' +
                '<div class="input-group input-group-sm">' +
                    '<span class="input-group-text bg-light text-muted px-2"><i class="bi bi-search"></i></span>' +
                    '<input type="text" class="form-control form-control-sm input-buscar-base" aria-label="Buscar base" placeholder="Buscar base por código o nombre..." autocomplete="off">' +
                    '<button type="button" class="btn btn-outline-secondary btn-limpiar-base d-none" title="Limpiar"><i class="bi bi-x"></i></button>' +
                '</div>' +
                '<input type="hidden" class="select-base-tinte" value="" required>' +
                '<div class="dropdown-menu w-100 lista-sugerencias-base buscador-dropdown-lista"></div>' +
            '</td>' +
            '<td>' +
                '<select class="form-select form-select-sm select-fraccion-tinte" aria-label="Fracción del tinte">' +
                    '<option value="1" data-galon="1.0">1 Galón (1.00 gal)</option>' +
                    '<option value="1/2" data-galon="0.5">1/2 Galón (0.50 gal)</option>' +
                    '<option value="1/4" data-galon="0.25" selected>1/4 Galón (0.25 gal)</option>' +
                    '<option value="1/8" data-galon="0.125">1/8 Galón (0.125 gal)</option>' +
                    '<option value="1/16" data-galon="0.0625">1/16 Galón (0.0625 gal)</option>' +
                    '<option value="1/32" data-galon="0.03125">1/32 Galón (0.0312 gal)</option>' +
                    '<option value="1/64" data-galon="0.015625">1/64 Galón (0.0156 gal)</option>' +
                    '<option value="1/128" data-galon="0.0078125">1/128 Galón (0.0078 gal)</option>' +
                '</select>' +
            '</td>' +
            '<td class="text-end">' +
                '<span class="badge bg-light text-dark border consumo-galon-text">0.2500 gal</span>' +
            '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-base-fila" title="Quitar"><i class="bi bi-x-lg"></i></button>' +
            '</td>');

        const $inputBuscar = $fila.find('.input-buscar-base');
        const $inputHidden = $fila.find('.select-base-tinte');
        const $btnLimpiar = $fila.find('.btn-limpiar-base');
        const $listaSug = $fila.find('.lista-sugerencias-base');
        const $selFrac = $fila.find('.select-fraccion-tinte');
        const $labelConsumo = $fila.find('.consumo-galon-text');
        const $tdBuscador = $fila.find('td.position-relative');

        function renderizarSugerencias(filtro) {
            $cuerpoBasesMezcla.find('.lista-sugerencias-base.show').not($listaSug).removeClass('show');
            $cuerpoBasesMezcla.find('td.celda-buscador-activa').not($tdBuscador).removeClass('celda-buscador-activa');
            $tdBuscador.addClass('celda-buscador-activa');

            $listaSug.empty();
            const q = (filtro || '').toLowerCase().trim();
            const filtradas = basesGlobal.filter(function(b) {
                if (!q) return true;
                return ((b.codigo || '') + ' ' + (b.nombre || '')).toLowerCase().includes(q);
            });

            if (filtradas.length === 0) {
                $listaSug.html('<div class="p-3 text-muted small text-center">No se encontraron tintes base</div>');
            } else {
                $.each(filtradas, function(i, b) {
                    const volumen = parseInt(b.volumen_familia_128) || 0;
                    $('<a class="buscador-dropdown-item" href="#"></a>')
                        .html(
                            '<span class="item-titulo">' + escapeHtml(b.nombre) + '</span>' +
                            '<div class="item-meta">' +
                                '<span class="badge-codigo">' + escapeHtml(b.codigo) + '</span>' +
                                '<span class="badge-stock ' + (volumen <= 0 ? 'bajo' : '') + '">Disponible: ' + formatearGalones(volumen / 128) + '</span>' +
                            '</div>')
                        .on('click', function(ev) {
                            ev.preventDefault();
                            $inputBuscar.val(b.codigo + ' - ' + b.nombre);
                            $inputHidden.val(b.id_producto).attr({ 'data-precio': b.precio_venta || 0, 'data-nombre': b.nombre });
                            $btnLimpiar.removeClass('d-none');
                            $listaSug.removeClass('show');
                            $tdBuscador.removeClass('celda-buscador-activa');
                            recalcularTotalesMezcla();
                        })
                        .appendTo($listaSug);
                });
            }
            $listaSug.addClass('show');
        }

        $inputBuscar.on('focus', function() {
            renderizarSugerencias(this.value);
        });

        $inputBuscar.on('input', function() {
            if (!this.value.trim()) {
                $inputHidden.val('').attr('data-precio', 0);
                $btnLimpiar.addClass('d-none');
                recalcularTotalesMezcla();
            }
            renderizarSugerencias(this.value);
        });

        $btnLimpiar.on('click', function() {
            $inputBuscar.val('');
            $inputHidden.val('').attr('data-precio', 0);
            $btnLimpiar.addClass('d-none');
            $listaSug.removeClass('show');
            $tdBuscador.removeClass('celda-buscador-activa');
            recalcularTotalesMezcla();
            $inputBuscar.trigger('focus');
        });

        $selFrac.on('change', function() {
            $labelConsumo.text(galonesDe($selFrac).toFixed(4) + ' gal');
            recalcularTotalesMezcla();
        });

        $fila.find('.btn-eliminar-base-fila').on('click', function() {
            $fila.remove();
            actualizarContadorBasesMezcla();
            recalcularTotalesMezcla();
        });

        $cuerpoBasesMezcla.append($fila);
        actualizarContadorBasesMezcla();
        recalcularTotalesMezcla();
    }

    // Galones de la opción seleccionada en un select de fracción/presentación
    function galonesDe($select, porDefecto) {
        const v = $select.find('option:selected').attr('data-galon');
        return v === undefined ? (porDefecto || 0) : parseFloat(v);
    }

    function capacidadObjetivoMezcla() {
        const f = $mezclaPresentacion.find('option:selected').attr('data-factor');
        return parseFloat(f === undefined ? 1.0 : f);
    }

    function actualizarContadorBasesMezcla() {
        if (!$cuerpoBasesMezcla.length || !$contadorBasesMezcla.length) return;
        const count = $cuerpoBasesMezcla.children('tr').length;
        $contadorBasesMezcla.text(count + '/6 bases');
        $btnAgregarFilaBase.prop('disabled', count >= 6);
    }

    function recalcularTotalesMezcla() {
        if (!$cuerpoBasesMezcla.length) return;
        let totalGal = 0;
        $cuerpoBasesMezcla.children('tr').each(function() {
            if ($(this).find('.select-base-tinte').val()) {
                totalGal += galonesDe($(this).find('.select-fraccion-tinte'));
            }
        });

        const capacidadObjetivo = capacidadObjetivoMezcla();
        const cant = parseInt($mezclaCantidad.val() || 1) || 1;
        const volumenTotalTaller = totalGal * cant;

        const $indicadorBalance = $('#indicadorBalanceFormula');
        if (totalGal === 0) {
            $indicadorBalance.html('<span class="text-muted small">Volumen: 0.0000 / ' + capacidadObjetivo.toFixed(4) + ' gal</span>');
        } else if (Math.abs(totalGal - capacidadObjetivo) < 0.0001) {
            $indicadorBalance.html('<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Fórmula exacta: ' + totalGal.toFixed(4) + ' / ' + capacidadObjetivo.toFixed(4) + ' gal (' + cant + ' envases = ' + volumenTotalTaller.toFixed(4) + ' gal)</span>');
        } else if (totalGal < capacidadObjetivo) {
            $indicadorBalance.html('<span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Faltan ' + (capacidadObjetivo - totalGal).toFixed(4) + ' gal para completar ' + capacidadObjetivo.toFixed(4) + ' gal</span>');
        } else {
            $indicadorBalance.html('<span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Exceso de fórmula: excede por ' + (totalGal - capacidadObjetivo).toFixed(4) + ' gal</span>');
        }

        const precio = parseFloat($mezclaPrecioVenta.val() || 0) || 0;
        $mezclaSubtotalEstimado.text('$ ' + formatearMoneda(cant * precio));
    }

    $mezclaPresentacion.on('change', recalcularTotalesMezcla);
    $mezclaCantidad.add($mezclaPrecioVenta).on('input', recalcularTotalesMezcla);

    function errorMezcla(texto) {
        $mensajeErrorMezcla.text(texto).removeClass('d-none');
    }

    $formMezcla.on('submit', async function(e) {
        e.preventDefault();
        $mensajeErrorMezcla.addClass('d-none');

        const nombre = $mezclaNombre.val().trim();
        if (!nombre) {
            errorMezcla('El nombre o identificación del color es obligatorio.');
            return;
        }
        if (nombre.length > 40) {
            errorMezcla('El nombre de la mezcla no puede superar los 40 caracteres.');
            return;
        }
        const presentacion = $mezclaPresentacion.val();
        const capacidadObjetivo = capacidadObjetivoMezcla();
        const cantidad = parseInt($mezclaCantidad.val()) || 1;
        const precioVenta = parseFloat($mezclaPrecioVenta.val()) || 0;

        const $filas = $cuerpoBasesMezcla.children('tr');
        if ($filas.length === 0) {
            errorMezcla('Debe agregar al menos una base para la formulación.');
            return;
        }

        let sumaFormula = 0;
        const basesData = [];
        let errorBases = false;
        $filas.each(function() {
            const idBase = parseInt($(this).find('.select-base-tinte').val() || 0);
            if (!idBase) {
                errorBases = true;
                return;
            }
            const $selFrac = $(this).find('.select-fraccion-tinte');
            const cantGal = galonesDe($selFrac, 0.25);
            sumaFormula += cantGal;
            basesData.push({
                id_producto_base: idBase,
                fraccion: $selFrac.val() || '1/4',
                cantidad_galon: cantGal
            });
        });

        if (errorBases || basesData.length === 0) {
            errorMezcla('Por favor seleccione todos los tintes base agregados.');
            return;
        }

        // Validación estricta de balance de volumen: debe dar la cantidad exacta
        if (Math.abs(sumaFormula - capacidadObjetivo) >= 0.0001) {
            if (sumaFormula < capacidadObjetivo) {
                errorMezcla('La mezcla debe dar la cantidad exacta de la presentación (' + capacidadObjetivo.toFixed(4) + ' gal). Le faltan ' + (capacidadObjetivo - sumaFormula).toFixed(4) + ' galones.');
            } else {
                errorMezcla('La mezcla excede la capacidad de la presentación (' + capacidadObjetivo.toFixed(4) + ' gal). Le sobran ' + (sumaFormula - capacidadObjetivo).toFixed(4) + ' galones.');
            }
            return;
        }

        try {
            const res = await api.post('inventario/guardarMezcla', {
                nombre: nombre,
                presentacion: presentacion,
                cantidad: cantidad,
                precio_venta: precioVenta,
                bases: JSON.stringify(basesData)
            });
            if (res.estado === 'exito') {
                bootstrap.Modal.getInstance($modalPrepararMezcla[0]).hide();
                mostrarNotificacion(res.mensaje, 'exito');
                cargarInsumos();
            } else {
                errorMezcla(res.mensaje);
            }
        } catch (err) {
            console.error(err);
            errorMezcla('No se pudo registrar la mezcla. Revise su conexión e intente de nuevo.');
        }
    });

    // =========================================================================
    // SECCIÓN: AJUSTE POR MERMA / DAÑO
    // =========================================================================
    const $btnAjusteMerma = $('#btnAjusteMerma');
    const $modalAjusteMerma = $('#modalAjusteMerma');
    const $formAjusteMerma = $('#formularioAjusteMerma');
    const $ajusteProducto = $('#ajusteProducto');
    const $inputBuscarAjusteProducto = $('#inputBuscarAjusteProducto');
    const $btnLimpiarAjusteProducto = $('#btnLimpiarAjusteProducto');
    const $listaSugerenciasAjusteProducto = $('#listaSugerenciasAjusteProducto');
    const $ajusteStockDisponible = $('#ajusteStockDisponible');
    const $ajusteCantidad = $('#ajusteCantidad');
    const $ajusteMotivo = $('#ajusteMotivo');
    const $ajusteObservacion = $('#ajusteObservacion');
    const $mensajeErrorAjuste = $('#mensajeErrorAjuste');
    const $grupoAjustePresentacion = $('#grupoAjustePresentacion');
    const $ajustePresentacion = $('#ajustePresentacion');
    const $ajusteEquivalencia = $('#ajusteEquivalencia');
    const textoEquivalenciaBase = $ajusteEquivalencia.text();

    function errorAjuste(texto) {
        $mensajeErrorAjuste.text(texto).removeClass('d-none');
    }

    // Deja el selector de producto de la merma sin selección
    function limpiarSeleccionAjuste() {
        $ajusteProducto.val('').attr('data-stock', '0');
        $ajusteStockDisponible.text('-');
        configurarAjustePresentacion(false);
    }

    // Para tintes base la merma se indica por presentación perdida; el resto sigue en unidades enteras
    function actualizarEquivalenciaAjuste() {
        if (!$ajusteEquivalencia.length || !$ajusteProducto.length) return;
        const esTinte = $ajusteProducto.attr('data-galones') === '1';
        const volumen = parseInt($ajusteProducto.attr('data-volumen')) || 0;
        const factorSel = esTinte ? $ajustePresentacion.find('option:selected').attr('data-factor') : undefined;
        const maximo = esTinte
            ? Math.floor(volumen / (factorSel !== undefined ? parseInt(factorSel) : 128))
            : Math.floor(parseFloat($ajusteProducto.attr('data-stock')) || 0);
        $ajusteCantidad.attr('max', maximo);
        if (parseInt($ajusteCantidad.val()) > maximo) $ajusteCantidad.val(maximo > 0 ? maximo : '');
        const prefijoTope = $ajusteProducto.val()
            ? 'Máximo permitido: ' + maximo + (esTinte ? ' (total disponible: ' + formatearGalones(volumen / 128) + '). ' : '. ')
            : '';
        if (!esTinte) {
            $ajusteEquivalencia.text(prefijoTope + textoEquivalenciaBase);
            return;
        }
        const cant = parseInt($ajusteCantidad.val()) || 0;
        const eq = cant * (factorSel !== undefined ? parseInt(factorSel) : 0);
        if (cant < 1) {
            $ajusteEquivalencia.text(prefijoTope + 'Indique cuántas unidades de esa presentación se perdieron.');
            return;
        }
        $ajusteEquivalencia.text(prefijoTope + 'Se perderán ' + formatearGalones(eq / 128) + ' en total. Si no hay envases de ese tamaño se abre uno mayor y el sobrante queda en stock.');
    }

    function configurarAjustePresentacion(esTinte, producto) {
        if (!$ajusteProducto.length || !$grupoAjustePresentacion.length) return;
        $ajusteProducto.attr('data-galones', esTinte ? '1' : '0');
        $grupoAjustePresentacion.toggleClass('d-none', !esTinte);
        if (esTinte) {
            const tamanoProducto = factor128(producto ? producto.fraccion : '1');
            $ajustePresentacion.html(presentacionesGlobal.map(function (pr) {
                return '<option value="' + pr.id_presentacion + '" data-factor="' + pr.factor_128 + '"' +
                    (parseInt(pr.factor_128) === tamanoProducto ? ' selected' : '') + '>' + escapeHtml(pr.etiqueta) + '</option>';
            }).join(''));
            $ajusteCantidad.removeAttr('max');
        }
        actualizarEquivalenciaAjuste();
    }

    $ajustePresentacion.on('change', actualizarEquivalenciaAjuste);
    $ajusteCantidad.on('input', actualizarEquivalenciaAjuste);

    // Bloquear puntos, comas y decimales en ajusteCantidad y mezclaCantidad
    soloEnteros($ajusteCantidad.add($mezclaCantidad), ['.', ',', 'e', 'E', '-', '+']);

    function renderizarSugerenciasAjuste(filtro) {
        if (!$listaSugerenciasAjusteProducto.length) return;
        $listaSugerenciasAjusteProducto.empty();
        const q = (filtro || '').toLowerCase().trim();

        const productosConStock = insumosGlobal.filter(function(p) {
            if (p.id_producto_padre) return false;
            const stock = esBase(p) ? (parseInt(p.volumen_familia_128) || 0) : Math.floor(parseFloat(p.stock_actual) || 0);
            if (stock <= 0) return false;
            if (!q) return true;
            return ((p.codigo || '') + ' ' + (p.nombre || '')).toLowerCase().includes(q);
        });

        if (productosConStock.length === 0) {
            $listaSugerenciasAjusteProducto.html('<div class="p-3 text-muted small text-center">No se encontraron productos con stock disponible</div>');
        } else {
            $.each(productosConStock, function(i, p) {
                const enGalones = esBase(p);
                const stock = Math.floor(parseFloat(p.stock_actual) || 0);
                const min = Math.floor(parseFloat(p.stock_minimo) || 0);
                const unidad = p.unidad_medida || '';
                const stockTexto = enGalones ? stock + ' envase(s) · familia: ' + formatearGalones((parseInt(p.volumen_familia_128) || 0) / 128) : stock + ' ' + unidad;
                $('<a class="buscador-dropdown-item" href="#"></a>')
                    .html(
                        '<span class="item-titulo">' + escapeHtml(p.nombre) + '</span>' +
                        '<div class="item-meta">' +
                            '<span class="badge-codigo">' + escapeHtml(p.codigo) + '</span>' +
                            '<span class="badge-stock ' + (stock <= min ? 'bajo' : '') + '">Stock: ' + stockTexto + '</span>' +
                        '</div>')
                    .on('click', function(ev) {
                        ev.preventDefault();
                        $inputBuscarAjusteProducto.val(p.codigo + ' - ' + p.nombre);
                        $ajusteProducto.val(p.id_producto).attr({
                            'data-stock': stock,
                            'data-volumen': parseInt(p.volumen_familia_128) || 0,
                            'data-unidad': unidad
                        });
                        $ajusteStockDisponible.text(stockTexto);
                        if (!enGalones) $ajusteCantidad.attr('max', stock);
                        configurarAjustePresentacion(enGalones, p);
                        $btnLimpiarAjusteProducto.removeClass('d-none');
                        $listaSugerenciasAjusteProducto.removeClass('show');
                        $ajusteCantidad.trigger('focus');
                    })
                    .appendTo($listaSugerenciasAjusteProducto);
            });
        }
        $listaSugerenciasAjusteProducto.addClass('show');
    }

    $inputBuscarAjusteProducto.on('focus', function() {
        renderizarSugerenciasAjuste(this.value);
    });
    $inputBuscarAjusteProducto.on('input', function() {
        if (!this.value.trim()) {
            limpiarSeleccionAjuste();
            $btnLimpiarAjusteProducto.addClass('d-none');
        }
        renderizarSugerenciasAjuste(this.value);
    });

    $btnLimpiarAjusteProducto.on('click', function() {
        $inputBuscarAjusteProducto.val('');
        limpiarSeleccionAjuste();
        $btnLimpiarAjusteProducto.addClass('d-none');
        $listaSugerenciasAjusteProducto.removeClass('show');
        $inputBuscarAjusteProducto.trigger('focus');
    });

    $(document).on('click', function(e) {
        const $cont = $('#contenedorBuscadorAjuste');
        if ($cont.length && !$cont[0].contains(e.target)) {
            $listaSugerenciasAjusteProducto.removeClass('show');
        }
    });

    const $contadorAjuste = $('#contadorAjusteObservacion');
    $ajusteObservacion.on('input', function() {
        // Permitir solo texto alfanumérico, espacios y puntuación estándar
        this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\:\;]/g, '').substring(0, 200);
        const palabras = this.value.trim().split(/\s+/).filter(Boolean);
        if (palabras.length > 40) {
            this.value = palabras.slice(0, 40).join(' ');
        }
        const countPalabras = this.value.trim().split(/\s+/).filter(Boolean).length;
        $contadorAjuste.text(this.value.length + ' / 200 caracteres (' + countPalabras + ' / 40 palabras)');
    });

    $btnAjusteMerma.on('click', function() {
        if ($formAjusteMerma.length) $formAjusteMerma[0].reset();
        $mensajeErrorAjuste.addClass('d-none');
        $inputBuscarAjusteProducto.val('');
        limpiarSeleccionAjuste();
        $btnLimpiarAjusteProducto.addClass('d-none');
        $listaSugerenciasAjusteProducto.removeClass('show');
        $contadorAjuste.text('0 / 200 caracteres (máx. 40 palabras)');

        if ($modalAjusteMerma.length) {
            bootstrap.Modal.getOrCreateInstance($modalAjusteMerma[0]).show();
        }
    });

    $formAjusteMerma.on('submit', async function(e) {
        e.preventDefault();
        $mensajeErrorAjuste.addClass('d-none');

        const idProd = parseInt($ajusteProducto.val()) || 0;
        const cantRaw = $ajusteCantidad.val().trim();
        const cant = Number(cantRaw);
        const obs = $ajusteObservacion.val().trim();

        const palabrasObs = obs.split(/\s+/).filter(Boolean).length;
        if (obs.length > 200 || palabrasObs > 40) {
            errorAjuste('La observación no puede superar los 200 caracteres ni 40 palabras.');
            return;
        }

        const motivoFinal = $ajusteMotivo.val() + (obs ? ': ' + obs : '');

        if (!idProd) {
            errorAjuste('Seleccione un producto afectado de la lista.');
            return;
        }

        const enGalones = $ajusteProducto.attr('data-galones') === '1';
        const stockActual = Math.floor(parseFloat($ajusteProducto.attr('data-stock')) || 0);
        const volumenFamilia = parseInt($ajusteProducto.attr('data-volumen')) || 0;

        if (!cantRaw || !Number.isInteger(cant) || cant < 1) {
            errorAjuste('La cantidad a descontar debe ser un número entero mayor o igual a 1.');
            return;
        }

        if (enGalones) {
            const factor = $ajustePresentacion.find('option:selected').attr('data-factor');
            const eq = cant * (factor !== undefined ? parseInt(factor) : 0);
            if (factor === undefined || eq > volumenFamilia) {
                errorAjuste(factor !== undefined
                    ? 'Lo perdido (' + formatearGalones(eq / 128) + ') excede el stock disponible (' + formatearGalones(volumenFamilia / 128) + ').'
                    : 'Seleccione la presentación que se perdió.');
                return;
            }
        } else if (cant > stockActual) {
            errorAjuste('La cantidad (' + cant + ') excede el stock disponible (' + stockActual + ').');
            return;
        }

        const datos = { id_producto: idProd, cantidad: cant, motivo: motivoFinal };
        if (enGalones) datos.id_presentacion = $ajustePresentacion.val();

        try {
            const res = await api.post('inventario/ajustarMerma', datos);
            if (res.estado === 'exito') {
                bootstrap.Modal.getInstance($modalAjusteMerma[0]).hide();
                mostrarNotificacion(res.mensaje, 'exito');
                cargarInsumos();
            } else {
                errorAjuste(res.mensaje);
            }
        } catch (err) {
            console.error(err);
            errorAjuste('No se pudo registrar el ajuste. Revise su conexión e intente de nuevo.');
        }
    });

    // Re-cargar datos si la pagina se restaura desde bfcache
    $(window).on('pageshow', function(e) {
        if (e.originalEvent.persisted) {
            cargarInsumos();
        }
    });
});

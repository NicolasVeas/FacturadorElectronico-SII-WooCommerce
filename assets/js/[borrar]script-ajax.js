jQuery(document).ready(function($) {
    // Funciones de validación y formateo de RUT
    var Fn = {
        validaRut: function(rutCompleto) {
            if (!/^[0-9]+-[0-9kK]{1}$/.test(rutCompleto)) {
                return false;
            }
            var tmp = rutCompleto.split('-');
            var digv = tmp[1];
            var rut = tmp[0];
            if (digv == 'K') digv = 'k';
            return (Fn.dv(rut) == digv);
        },
        dv: function(T) {
            var M = 0,
                S = 1;
            for (; T; T = Math.floor(T / 10))
                S = (S + T % 10 * (9 - M++ % 6)) % 11;
            return S ? S - 1 : 'k';
        },
        formateaRut: function(rut) {
            var actual = rut.replace(/^0+/, "").replace(/\./g, "").replace(/-/g, "").toUpperCase();
            if (actual.length <= 1) {
                return actual;
            }
            var inicio = actual.slice(0, -1);
            var dv = actual.slice(-1);
            return inicio + '-' + dv;
        }
    };

    // Formatear y validar RUT al escribir
    $(document).on('input', '#rut_receptor', function() {
        var rut = $(this).val().replace(/[^0-9kK]/g, '').toUpperCase();
        $(this).val(Fn.formateaRut(rut));
    });

    // Acción para ver DTE
    $(document).on('click', '.ver-dte-btn', function() {
        var order_id = $(this).data('order-id');
        var modalContent = $('#ver-dte-modal-content');

        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.post(ajax_object.ajaxurl, { action: 'ver_dte', order_id: order_id }, function(response) {
            Swal.close();
            if (response.success) {
                modalContent.html(response.data);
                $('#verDteModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.data,
                });
            }
        });
    });

    // Acción para ver detalles del DTE
    $(document).on('click', '.detalles-dte-btn', function() {
        var document_type = $(this).data('document-type');
        var document_number = $(this).data('document-number');

        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.post(ajax_object.ajaxurl, { action: 'obtener_detalles_dte', document_type: document_type, document_number: document_number }, function(response) {
            Swal.close();
            if (response.success) {
                var stages = response.data.stages;
                var detailsHtml = '<ul>';
                stages.forEach(function(stage) {
                    detailsHtml += '<li>' + stage.register_date + ': ' + stage.description + '</li>';
                });
                detailsHtml += '</ul>';
                $('#ver-dte-modal-content').html(detailsHtml);
                $('#verDteModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.data.message,
                });
            }
        });
    });

    // Acción para generar DTE
    $(document).on('click', '.generar-dte-btn', function() {
        var order_id = $(this).data('order-id');
        var modalContent = $('#generar-dte-modal-content');

        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.post(ajax_object.ajaxurl, { action: 'cargar_datos_emisor', order_id: order_id }, function(response) {
            Swal.close();
            if (response.success) {
                modalContent.html(response.data);
                $('#generarDteModal').modal('show');
                $('#tipo_dte').trigger('change'); // Trigger change event to show/hide fields

                // Añadir eventos de validación y formateo del RUT después de cargar el modal
                $('#rut_receptor').on('input', function() {
                    var rut = $(this).val().replace(/[^0-9kK]/g, '').toUpperCase();
                    $(this).val(Fn.formateaRut(rut));
                });

                $('#rut_receptor').on('blur', function() {
                    var rut = $(this).val().replace(/[^0-9kK]/g, '').toUpperCase();
                    $(this).val(Fn.formateaRut(rut));
                    if (!Fn.validaRut($(this).val())) {
                        Swal.fire({
                            icon: 'error',
                            title: 'RUT inválido',
                            text: 'El RUT ingresado no es válido. Por favor, verifica y corrige.',
                        });
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });

                // Formatear RUT cargado desde la base de datos
                var rutReceptorInput = $('#rut_receptor');
                rutReceptorInput.val(Fn.formateaRut(rutReceptorInput.val()));
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.data,
                });
            }
        });
    });

    // Acción para enviar el formulario y generar el DTE
    $(document).on('click', '#submit-generar-dte-btn', function() {
        var order_id = $(this).data('order-id');
        var tipo_dte = $('#tipo_dte').val();

        var formData = $('#generar-dte-form').serialize();

        // Validar campos requeridos
        let isValid = true;
        $('#generar-dte-form').find('input[required]').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validar RUT
        var rutReceptor = $('#rut_receptor').val();
        if (!Fn.validaRut(rutReceptor)) {
            isValid = false;
            $('#rut_receptor').addClass('is-invalid');
        } else {
            $('#rut_receptor').removeClass('is-invalid');
        }

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Campos incompletos',
                text: 'Por favor, completa todos los campos obligatorios correctamente.',
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Estás seguro que deseas emitir este documento?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, emitir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Emitiendo...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post(ajax_object.ajaxurl, {
                    action: 'generar_dte',
                    order_id: order_id,
                    tipo_dte: tipo_dte,
                    form_data: formData
                }, function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'DTE generado',
                            text: 'DTE generado exitosamente',
                        });
                        $('#generarDteModal').modal('hide');
                    } else {
                        if (response.data.includes("No CAF found for folio")) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No quedan folios disponibles. Por favor, sube un archivo CAF en la sección de folios.',
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al generar el DTE: ' + response.data,
                            });
                        }
                    }
                });
            }
        });
    });

    // Mostrar campos adicionales si se selecciona Factura Electrónica
    $(document).on('change', '#tipo_dte', function() {
        var tipoDte = $(this).val();
        if (tipoDte == '33') {
            $('#factura_fields').show();
            $('#receptor_fields').show();
        } else {
            $('#factura_fields').hide();
            $('#receptor_fields').hide();
        }
    }).change(); // Trigger change event on page load to set the initial state

    // Paginación
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        var section = $(this).data('section');

        Swal.fire({
            title: 'Cargando...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.post(ajax_object.ajaxurl, {
            action: 'cargar_pagina',
            page: page,
            section: section
        }, function(response) {
            Swal.close();
            if (response.success) {
                if (section === 'emitidos') {
                    $('#emitidos-table-container').html(response.data);
                } else if (section === 'no_emitidos') {
                    $('#no_emitidos-table-container').html(response.data);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.data,
                });
            }
        });
    });
});

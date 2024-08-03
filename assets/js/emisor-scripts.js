// assets/js/emisor-scripts.js

function mostrarInfoActeco() {
    Swal.fire({
        title: 'Información ACTECO',
        html: 'ACTECO: Se acepta un máximo de 4 códigos de actividad económica del emisor del DTE. Se puede incluir sólo el código que corresponde a la transacción.<br><br>' +
              '<a href="https://www.sii.cl/ayudas/ayudas_por_servicios/1956-codigos-1959.html" target="_blank">Códigos de actividad económica</a><br>' +
              '<a href="https://zeus.sii.cl/cvc/stc/stc.html" target="_blank">Consultar situación tributaria de terceros</a>',
        icon: 'info',
        confirmButtonText: 'Cerrar'
    });
}

jQuery(document).ready(function($) {
    // Mostrar mensajes en modales si hay mensajes en la URL
    var message = $('#emisor-form').data('message');
    if (message === 'success') {
        Swal.fire({
            title: 'Éxito',
            text: '¡Éxito! Los datos del emisor se han guardado correctamente.',
            icon: 'success',
            confirmButtonText: 'Cerrar'
        });
    } else if (message === 'error') {
        Swal.fire({
            title: 'Error',
            text: 'Hubo un error al guardar los datos del emisor. Por favor, intenta de nuevo.',
            icon: 'error',
            confirmButtonText: 'Cerrar'
        });
    } else if (message === 'invalid_rut') {
        Swal.fire({
            title: 'Advertencia',
            text: 'El RUT ingresado no es válido. Por favor, verifica y corrige.',
            icon: 'warning',
            confirmButtonText: 'Cerrar'
        });
    } else if (message === 'no_changes') {
        Swal.fire({
            title: 'Sin cambios',
            text: 'No se realizaron cambios en los datos del emisor.',
            icon: 'info',
            confirmButtonText: 'Cerrar'
        });
    }

    $('#guardar-btn').on('click', function() {
        var rut = $('#sii_wc_rut').val();
        if (!Fn.validaRut(rut)) {
            Swal.fire({
                title: 'Advertencia',
                text: 'El RUT ingresado no es válido. Por favor, verifica y corrige.',
                icon: 'warning',
                confirmButtonText: 'Cerrar'
            });
        } else {
            Swal.fire({
                title: 'Confirmación',
                text: '¿Estás seguro de que deseas guardar los datos del emisor?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#spinnerModal').modal('show');
                    $('#emisor-form').submit();
                }
            });
        }
    });

    // Validar y formatear RUT al escribir
    $(document).on("input", ".rut-input", function() {
        var rut = $(this).val().replace(/[^0-9kK]/g, "").toUpperCase();
        $(this).val(Fn.formateaRut(rut));
    });

    $(document).on("blur", ".rut-input", function() {
        var rut = $(this).val().replace(/[^0-9kK]/g, "").toUpperCase();
        $(this).val(Fn.formateaRut(rut));
        if (!Fn.validaRut($(this).val())) {
            $(this).addClass("is-invalid");
        } else {
            $(this).removeClass("is-invalid");
        }
    });

    // Limitar longitud del RUT
    $(".rut-input").attr("maxlength", "10").attr("minlength", "9");

    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

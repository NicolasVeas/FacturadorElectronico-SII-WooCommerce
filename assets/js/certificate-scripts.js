// assets/js/certificate-scripts.js
jQuery(document).ready(function($) {
    // Validar el RUT antes de enviar el formulario
    $('#certificate-upload-form').on('submit', function(e) {
        var rut = $('#certificate_owner_rut').val();
        if (!Fn.validaRut(rut)) {
            e.preventDefault(); // Evitar el envío del formulario si el RUT no es válido
            Swal.fire({
                title: 'Advertencia',
                text: 'El RUT ingresado no es válido. Por favor, verifica y corrige.',
                icon: 'warning',
                confirmButtonText: 'Cerrar'
            });
            return false; // Asegura que el envío se cancele
        }
        $('#spinnerModal').modal('show'); // Mostrar el spinner solo si el RUT es válido
    });

    // Manejar la eliminación del certificado
    $('.delete-certificate-btn').on('click', function() {
        var certificateId = $(this).data('certificate-id');
        var certificateRut = $(this).data('certificate-rut');
        Swal.fire({
            title: 'Confirmar eliminación',
            text: '¿Estás seguro de que deseas eliminar el certificado con ID ' + certificateId + ' del RUT ' + certificateRut + '? Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#deleteLoadingModal').modal('show');
                $.post('', { delete_certificate: true, certificate_id: certificateId, certificate_rut: certificateRut }, function() {
                    window.location.href = window.location.href + '&delete_success=' + certificateId + '&delete_rut=' + certificateRut;
                }).fail(function() {
                    window.location.href = window.location.href + '&delete_error=1';
                });
            }
        });
    });

    // Mostrar mensajes de éxito y error usando SweetAlert2
    if (typeof uploadSuccessPHP !== 'undefined' && uploadSuccessPHP) {
        Swal.fire({
            title: 'Éxito',
            text: 'Archivo de certificado subido correctamente.',
            icon: 'success',
            confirmButtonText: 'Cerrar'
        });
    } else if (typeof uploadErrorPHP !== 'undefined' && uploadErrorPHP) {
        Swal.fire({
            title: 'Error',
            text: 'Error al subir el archivo de certificado: ' + uploadErrorPHP,
            icon: 'error',
            confirmButtonText: 'Cerrar'
        });
    }

    if (typeof deleteSuccessPHP !== 'undefined' && deleteSuccessPHP) {
        Swal.fire({
            title: 'Éxito',
            text: 'Certificado con ID ' + deleteSuccessPHP + ' del RUT ' + deleteRutPHP + ' eliminado correctamente.',
            icon: 'success',
            confirmButtonText: 'Cerrar'
        });
    } else if (typeof deleteErrorPHP !== 'undefined' && deleteErrorPHP) {
        Swal.fire({
            title: 'Error',
            text: 'Error al eliminar el certificado.',
            icon: 'error',
            confirmButtonText: 'Cerrar'
        });
    }

    // Formatear y validar RUT al escribir
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

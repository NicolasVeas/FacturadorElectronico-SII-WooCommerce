jQuery(document).ready(function($) {
    var uploadSuccessPHP = typeof uploadSuccessPHP !== 'undefined' ? uploadSuccessPHP : false;
    var uploadErrorPHP = typeof uploadErrorPHP !== 'undefined' ? uploadErrorPHP : '';

    if (uploadSuccessPHP) {
        Swal.fire({
            title: "Éxito",
            text: "Archivo CAF subido correctamente.",
            icon: "success",
            confirmButtonText: "Cerrar"
        });
    }

    if (uploadErrorPHP) {
        Swal.fire({
            title: "Error",
            text: "Error al subir el archivo CAF: " + uploadErrorPHP,
            icon: "error",
            confirmButtonText: "Cerrar"
        });
    }

    $('#caf-upload-form').on('submit', function() {
        $('#spinnerModal').modal('show');
    });
});

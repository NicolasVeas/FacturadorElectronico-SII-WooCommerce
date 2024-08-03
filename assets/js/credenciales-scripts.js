// assets/js/credenciales-scripts.js
jQuery(document).ready(function($) {
    $('#guardar-credenciales-btn').on('click', function(e) {
        e.preventDefault();

        var email = $('#sii_wc_email').val();
        var password = $('#sii_wc_password').val();
        var emailValid = validateEmail(email);
        var passwordValid = validatePassword(password);

        if (!emailValid) {
            $('#sii_wc_email').addClass('is-invalid');
        } else {
            $('#sii_wc_email').removeClass('is-invalid');
        }

        if (!passwordValid) {
            $('#sii_wc_password').addClass('is-invalid');
        } else {
            $('#sii_wc_password').removeClass('is-invalid');
        }

        if (emailValid && passwordValid) {
            $('#spinnerModal').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
            $('#credenciales-form').submit();
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Por favor, corrige los campos marcados antes de continuar.',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    });

    function validateEmail(email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@(([^<>()[\]\.,;:\s@"]+\.)+[^<>()[\]\.,;:\s@"]{2,})$/i;
        return re.test(String(email).toLowerCase());
    }

    function validatePassword(password) {
        return password.length >= 6; // Example: password must be at least 6 characters long
    }

    $('#sii_wc_email').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    $('#sii_wc_password').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    var message = $('#credenciales-form').data('message');
    if (message === 'success') {
        Swal.fire({
            title: 'Éxito',
            text: 'Las credenciales se han guardado correctamente.',
            icon: 'success',
            confirmButtonText: 'Cerrar'
        });
    } else if (message === 'error') {
        Swal.fire({
            title: 'Error',
            text: 'Hubo un error al guardar las credenciales. Por favor, intenta de nuevo.',
            icon: 'error',
            confirmButtonText: 'Cerrar'
        });
    }
});

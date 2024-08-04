jQuery(document).ready(function($) {
    $(document).on('click', '.reenviar-dte-btn', function() {
        var orderId = $(this).data('order-id');

        // Mostrar el modal y el spinner, ocultar el contenido y el footer
        $('#reenviarDteModal').modal('show');
        $('#reenviarDteModal .modal-body .spinner-border').show();
        $('#reenviarDteModal .modal-body p').show(); // Mostrar el mensaje "Cargando datos..."
        $('#reenviarDteModal .modal-body-content').hide();
        $('#reenviarDteModal .modal-footer').hide(); // Ocultar el footer con los botones

        $.ajax({
            url: sii_wc_ajax.ajaxurl,
            type: 'post',
            data: {
                action: 'reenviar_dte_modal',
                order_id: orderId,
                nonce: sii_wc_ajax.nonces.send_reenviar_dte_nonce // Usa el nonce adecuado para la primera carga del modal
            },
            success: function(response) {
                if (response.success) {
                    // Ocultar el spinner y mostrar el contenido y el footer
                    $('#reenviarDteModal .modal-body .spinner-border').hide();
                    $('#reenviarDteModal .modal-body p').hide(); // Ocultar el mensaje "Cargando datos..."
                    $('#reenviarDteModal .modal-body-content').html(response.data.html).show();
                    $('#reenviarDteModal .modal-footer').show();
                    sii_wc_ajax.nonces.send_reenviar_dte_nonce = response.data.nonce; // Actualiza el nonce
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al cargar el formulario.'
                    });
                    console.error('Error en la respuesta AJAX:', response.data);
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la llamada AJAX'
                });
                console.error('Error en la llamada AJAX:', error);
            }
        });
    });

    $(document).off('click', '#sendReenviarDteBtn').on('click', '#sendReenviarDteBtn', function() {
        var form = $('#reenviarDteForm');
        var $button = $(this);
        var $buttonSpinner = $('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

        // Validar correos electrónicos
        var isValid = true;
        form.find('input[type="email"]').each(function() {
            var $this = $(this);
            var email = $this.val();
            var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

            if (!emailPattern.test(email)) {
                $this.addClass('is-invalid');
                $this.next('.invalid-feedback').show();
                isValid = false;
            } else {
                $this.removeClass('is-invalid');
                $this.next('.invalid-feedback').hide();
            }
        });

        // Validar el campo de asunto
        var subject = $('#subject').val();
        if (subject.trim() === '') {
            $('#subject').addClass('is-invalid');
            $('#subject').next('.invalid-feedback').show();
            isValid = false;
        } else {
            $('#subject').removeClass('is-invalid');
            $('#subject').next('.invalid-feedback').hide();
        }

        if (!isValid) {
            return;
        }

        $button.prop('disabled', true).prepend($buttonSpinner);

        $.ajax({
            url: sii_wc_ajax.ajaxurl,
            type: 'post',
            data: form.serialize() + '&action=send_reenviar_dte&nonce=' + sii_wc_ajax.nonces.send_reenviar_dte_nonce,
            success: function(response) {
                $button.prop('disabled', false).find('.spinner-border').remove();

                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Correo enviado exitosamente'
                    }).then(() => {
                        $('#reenviarDteModal').modal('hide');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message
                    });
                }
            },
            error: function(xhr, status, error) {
                $button.prop('disabled', false).find('.spinner-border').remove();

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la llamada AJAX'
                });
                console.error('Error en la llamada AJAX:', error);
            }
        });
    });
});

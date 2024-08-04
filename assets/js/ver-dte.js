jQuery(document).ready(function($) {
    // Event listener para el botón Ver DTE
    $(document).on('click', '.view-dte-btn', function(e) {
        e.stopPropagation(); // Detener la propagación del evento

        var button = $(this);
        var orderId = button.data('order-id');

        // Deshabilitar el botón temporalmente
        button.prop('disabled', true);

        $('#viewDteModal .modal-body').show();
        $('#viewDteModal .modal-body-content').hide();
        $('#viewDteModal').modal('show');

        $.ajax({
            url: sii_wc_ajax.ajaxurl,
            type: 'post',
            data: {
                action: 'view_dte_modal',
                order_id: orderId,
                nonce: sii_wc_ajax.nonces.view_dte_nonce
            },
            beforeSend: function() {
                console.log("Enviando solicitud AJAX para 'Ver DTE'");
            },
            success: function(response) {
                if (response.success) {
                    console.log("Respuesta AJAX exitosa");
                    $('#viewDteModal .modal-body').hide();
                    $('#viewDteModal .modal-body-content').html(response.data.html).show();
                    $('#viewDteModal .modal-footer').show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message
                    });
                    console.error('Error en la respuesta AJAX:', response.data);
                }
                // Habilitar el botón después de la carga
                button.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error en la llamada AJAX.'
                });
                console.error('Error en la llamada AJAX:', error);
                // Habilitar el botón en caso de error también
                button.prop('disabled', false);
            }
        });
    });
});

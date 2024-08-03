jQuery(document).ready(function($) {
    // Mostrar modal "Ver DTE" al hacer clic en el botón
    $(document).on('click', '.view-dte-btn', function() {
        var orderId = $(this).data('order-id');

        // Mostrar el modal con el spinner
        $('#viewDteModal .modal-body').show();
        $('#viewDteModal .modal-body-content').hide();
        $('#viewDteModal').modal('show');

        // Hacer una llamada AJAX para obtener y mostrar el contenido del DTE
        $.ajax({
            url: sii_wc_ajax.ajaxurl,
            type: 'post',
            data: {
                action: 'view_dte_modal',
                order_id: orderId,
                nonce: sii_wc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#viewDteModal .modal-body').hide();
                    $('#viewDteModal .modal-body-content').html(response.data.html).show();
                } else {
                    console.error('Error en la respuesta AJAX:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la llamada AJAX:', error);
            }
        });
    });
});

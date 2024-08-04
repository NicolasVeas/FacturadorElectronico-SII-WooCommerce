jQuery(document).ready(function($) {
    $(document).on('click', '.view-trazabilidad-btn', function() {
        var orderId = $(this).data('order-id');

        $('#trazabilidadModal .modal-body').show();
        $('#trazabilidadModal .modal-body-content').hide();
        $('#trazabilidadModal').modal('show');

        $.ajax({
            url: sii_wc_ajax.ajaxurl,
            type: 'post',
            data: {
                action: 'view_trazabilidad_modal',
                order_id: orderId,
                nonce: sii_wc_ajax.nonces.view_trazabilidad_nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#trazabilidadModal .modal-body').hide();
                    $('#trazabilidadModal .modal-body-content').html(response.data.html).show();
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

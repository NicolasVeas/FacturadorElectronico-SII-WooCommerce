jQuery(document).ready(function($) {
    function loadPedidosEmitidos(page, showSpinner) {
        var nonce = sii_wc_ajax.nonces.get_pedidos_emitidos_nonce;

        if (showSpinner) {
            $('#spinnerModal').modal('show');
        }

        $.ajax({
            url: sii_wc_ajax.ajaxurl,
            type: 'post',
            data: {
                action: 'get_pedidos_emitidos',
                page: page,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#pedidos-emitidos-body').html(response.data.html);
                    $('#pagination-container').html(response.data.pagination);
                } else {
                    console.error('Error en la respuesta AJAX:', response.data);
                }
                if (showSpinner) {
                    $('#spinnerModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la llamada AJAX:', error);
                if (showSpinner) {
                    $('#spinnerModal').modal('hide');
                }
            }
        });
    }

    // Event listener para la paginación
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').split('paged=')[1];
        loadPedidosEmitidos(page, true);
    });

    // Cargar pedidos emitidos al cargar la página
    loadPedidosEmitidos(1, false);
});

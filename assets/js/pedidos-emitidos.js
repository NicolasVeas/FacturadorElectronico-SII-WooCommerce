jQuery(document).ready(function($) {
    // Función para cargar pedidos emitidos
    function loadPedidosEmitidos(page, showSpinner) {
        var nonce = sii_wc_ajax.nonce;

        if (showSpinner) {
            // Mostrar el modal de carga solo si showSpinner es true
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
                    // Ocultar el modal de carga solo si showSpinner es true
                    $('#spinnerModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la llamada AJAX:', error);
                if (showSpinner) {
                    // Ocultar el modal de carga solo si showSpinner es true
                    $('#spinnerModal').modal('hide');
                }
            }
        });
    }

    // Cargar la primera página de pedidos emitidos sin mostrar el spinner
    loadPedidosEmitidos(1, false);

    // Manejar la paginación
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').split('paged=')[1];
        loadPedidosEmitidos(page, true);
    });
});

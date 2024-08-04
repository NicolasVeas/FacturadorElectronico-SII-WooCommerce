<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;

$items_per_page = 10;
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$offset = ($page - 1) * $items_per_page;

$emitidos = PedidosEmitidosModel::getPedidosEmitidos($offset, $items_per_page);
$total_emitidos = PedidosEmitidosModel::getTotalPedidosEmitidos();
$total_pages = ceil($total_emitidos / $items_per_page);
?>

<style>
/* Estilos de tabla */
#pedidos-emitidos-table {
    width: 100%;
    border-collapse: collapse;
}

#pedidos-emitidos-table th, #pedidos-emitidos-table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: center;
}

#pedidos-emitidos-table th:first-child,
#pedidos-emitidos-table td:first-child,
#pedidos-emitidos-table th:nth-child(2),
#pedidos-emitidos-table td:nth-child(2),
#pedidos-emitidos-table th:nth-child(3),
#pedidos-emitidos-table td:nth-child(3) {
    text-align: left;
}

#pedidos-emitidos-table td.text-right {
    text-align: right;
}

#pedidos-emitidos-table tbody tr:hover {
    background-color: #f1f1f1;
}

#pagination-container .pagination {
    margin: 0;
}

#pagination-container .page-numbers {
    padding: 10px 15px;
    border: 1px solid #ddd;
    color: #007bff;
    text-decoration: none;
    margin: 0 5px;
}

#pagination-container .page-numbers:hover {
    background-color: #007bff;
    color: white;
}

#pagination-container .current {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}
</style>

<div class="container mt-5">
    <h2>Pedidos Emitidos</h2>
    <table class="table table-bordered table-striped" id="pedidos-emitidos-table">
        <thead class="thead-dark">
            <tr>
                <th>ID Pedido</th>
                <th>Tipo de Documento</th>
                <th>Folios</th>
                <th>Fecha</th>
                <th>RUT Receptor</th>
                <th>Monto Total</th>
                <th>Progreso</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="pedidos-emitidos-body">
            <?php if (!empty($emitidos)) : ?>
                <?php foreach ($emitidos as $pedido) : ?>
                    <tr>
                        <td class="text-right"><?php echo esc_html($pedido['order_id']); ?></td>
                        <td><?php echo esc_html(format_document_type($pedido['document_type'])); ?></td>
                        <td class="text-right"><?php echo esc_html($pedido['document_number']); ?></td>
                        <td><?php echo esc_html(date('d-m-Y', strtotime($pedido['document_date']))); ?></td>
                        <td><?php echo esc_html($pedido['rut_receptor']); ?></td>
                        <td class="text-right"><?php echo esc_html(format_currency($pedido['total'])); ?></td>
                        <td>
                            <?php 
                            $statusClass = '';
                            switch ($pedido['status']) {
                                case 'ACCEPTED':
                                    $statusClass = 'badge badge-success';
                                    break;
                                case 'REJECTED':
                                    $statusClass = 'badge badge-danger';
                                    break;
                                case 'GROUPED':
                                    $statusClass = 'badge badge-secondary';
                                    break;
                                default:
                                    $statusClass = 'badge badge-warning';
                            }
                            echo '<span class="' . esc_attr($statusClass) . '">' . esc_html($pedido['status']) . '</span>'; 
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm view-dte-btn" data-order-id="<?php echo esc_attr($pedido['order_id']); ?>"><i class="fas fa-file-alt"></i> Ver DTE</button>
                            <button class="btn btn-secondary btn-sm view-trazabilidad-btn" data-order-id="<?php echo esc_attr($pedido['order_id']); ?>"><i class="fas fa-clipboard-list"></i> Trazabilidad</button>
                            <?php if ($pedido['status'] == 'ACCEPTED') : ?>
                                <button class="btn btn-primary btn-sm reenviar-dte-btn" data-order-id="<?php echo esc_attr($pedido['order_id']); ?>"><i class="fas fa-envelope"></i> Reenviar DTE</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8" class="text-center">No se encontraron pedidos emitidos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="pagination-container" class="d-flex justify-content-center mt-3">
        <?php
        $pagination_args = array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'total' => $total_pages,
            'current' => $page,
            'prev_text' => __('&laquo; Anterior', 'sii-woocommerce'),
            'next_text' => __('Siguiente &raquo;', 'sii-woocommerce'),
        );
        echo '<nav><ul class="pagination">';
        echo paginate_links($pagination_args);
        echo '</ul></nav>';
        ?>
    </div>

    <!-- Modal de carga -->
    <div class="modal fade" id="spinnerModal" tabindex="-1" role="dialog" aria-labelledby="spinnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Espere un momento por favor...</span>
                    </div>
                    <p class="mt-3">Espere un momento por favor...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver DTE -->
    <div class="modal fade" id="viewDteModal" tabindex="-1" role="dialog" aria-labelledby="viewDteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Espere un momento por favor...</span>
                    </div>
                    <p class="mt-3">Espere un momento por favor...</p>
                </div>
                <div class="modal-body-content"></div>
                <div class="modal-footer" style="display: none;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Trazabilidad -->
    <div class="modal fade" id="trazabilidadModal" tabindex="-1" role="dialog" aria-labelledby="trazabilidadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Espere un momento por favor...</span>
                    </div>
                    <p class="mt-3">Espere un momento por favor...</p>
                </div>
                <div class="modal-body-content"></div>
                <div class="modal-footer" style="display: none;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Reenviar DTE -->
    <div class="modal fade" id="reenviarDteModal" tabindex="-1" role="dialog" aria-labelledby="reenviarDteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Espere un momento por favor...</span>
                    </div>
                    <p class="mt-3">Espere un momento por favor...</p>
                    <div class="modal-body-content"></div>
                </div>
              
            </div>
        </div>
    </div>

</div>

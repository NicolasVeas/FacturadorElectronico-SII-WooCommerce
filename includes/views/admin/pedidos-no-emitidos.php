<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;
$items_per_page = 4;

$current_page_no_emitidos = isset($_POST['page']) ? intval($_POST['page']) : 1;
$offset_no_emitidos = ($current_page_no_emitidos - 1) * $items_per_page;

$query_no_emitidos = "
    SELECT 
        o.id AS order_id, 
        os.total_sales AS total,
        o.date_created_gmt AS order_date,
        COALESCE(meta_rut.meta_value, 'Sin identificar') AS rut_receptor
    FROM {$wpdb->prefix}wc_orders AS o
    LEFT JOIN {$wpdb->prefix}sii_wc_dtes AS d ON o.id = d.order_id
    LEFT JOIN {$wpdb->prefix}wc_order_stats AS os ON o.id = os.order_id
    LEFT JOIN {$wpdb->prefix}postmeta AS meta_rut ON o.id = meta_rut.post_id AND meta_rut.meta_key = '_billing_rut'
    WHERE d.document_number IS NULL
    ORDER BY o.id DESC
    LIMIT $offset_no_emitidos, $items_per_page
";
$no_emitidos = $wpdb->get_results($query_no_emitidos, ARRAY_A);
$total_no_emitidos = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->prefix}wc_orders AS o
    LEFT JOIN {$wpdb->prefix}sii_wc_dtes AS d ON o.id = d.order_id
    WHERE d.document_number IS NULL
");
$total_pages_no_emitidos = ceil($total_no_emitidos / $items_per_page);
?>

<div id="no_emitidos-table-container">
    <h2>Pedidos No Emitidos</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Fecha</th>
                <th>RUT Receptor</th>
                <th>Monto Total</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($no_emitidos)): ?>
                <?php foreach ($no_emitidos as $pedido): ?>
                    <tr>
                        <td><?php echo esc_html($pedido['order_id']); ?></td>
                        <td><?php echo esc_html(date('Y-m-d', strtotime($pedido['order_date']))); ?></td>
                        <td><?php echo esc_html($pedido['rut_receptor']); ?></td>
                        <td><?php echo esc_html(number_format($pedido['total'], 2, ',', '.')); ?></td>
                        <td>
                            <button class="btn btn btn-dark generar-dte-btn" data-order-id="<?php echo esc_attr($pedido['order_id']); ?>" data-toggle="tooltip" title="Generar DTE"><i class="fas fa-file-invoice"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No se encontraron pedidos no emitidos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php render_pagination($total_pages_no_emitidos, $current_page_no_emitidos, 'no_emitidos'); ?>
</div>

<div class="modal fade" id="generarDteModal" tabindex="-1" role="dialog" aria-labelledby="generarDteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generarDteModalLabel">Generar DTE</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="generar-dte-modal-content">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

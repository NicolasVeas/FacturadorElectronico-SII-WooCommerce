<?php

class PedidosEmitidosController {
    public static function init() {
        add_action('wp_ajax_get_pedidos_emitidos', array(__CLASS__, 'getPedidosEmitidos'));
    }

    public static function getPedidosEmitidos() {
        check_ajax_referer('get_pedidos_emitidos_nonce', 'nonce');

        $items_per_page = 10;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $offset = ($page - 1) * $items_per_page;

        $emitidos = PedidosEmitidosModel::getPedidosEmitidos($offset, $items_per_page);
        $total_emitidos = PedidosEmitidosModel::getTotalPedidosEmitidos();

        $html = '';
        if (!empty($emitidos)) {
            foreach ($emitidos as $pedido) {
                $html .= '<tr>';
                $html .= '<td class="text-right">' . esc_html($pedido['order_id']) . '</td>';
                $html .= '<td>' . esc_html(format_document_type($pedido['document_type'])) . '</td>';
                $html .= '<td class="text-right">' . esc_html($pedido['document_number']) . '</td>';
                $html .= '<td>' . esc_html(date('d-m-Y', strtotime($pedido['document_date']))) . '</td>';
                $html .= '<td>' . esc_html($pedido['rut_receptor']) . '</td>';
                $html .= '<td class="text-right">' . esc_html(format_currency($pedido['total'])) . '</td>';
                $html .= '<td>';
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
                $html .= '<span class="' . $statusClass . '">' . esc_html($pedido['status']) . '</span>';
                $html .= '</td>';
                $html .= '<td>
                    <button class="btn btn-info btn-sm view-dte-btn" data-order-id="' . esc_attr($pedido['order_id']) . '"><i class="fas fa-file-alt"></i> Ver DTE</button>
                    <button class="btn btn-secondary btn-sm view-trazabilidad-btn" data-order-id="' . esc_attr($pedido['order_id']) . '"><i class="fas fa-clipboard-list"></i> Trazabilidad</button>';
                if ($pedido['status'] == 'ACCEPTED') {
                    $html .= '<button class="btn btn-primary btn-sm reenviar-dte-btn" data-order-id="' . esc_attr($pedido['order_id']) . '"><i class="fas fa-envelope"></i> Reenviar DTE</button>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" class="text-center">No se encontraron pedidos emitidos.</td></tr>';
        }

        $total_pages = ceil($total_emitidos / $items_per_page);

        ob_start();
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
        $pagination = ob_get_clean();

        wp_send_json_success(array('html' => $html, 'pagination' => $pagination));
    }
}

PedidosEmitidosController::init();
?>

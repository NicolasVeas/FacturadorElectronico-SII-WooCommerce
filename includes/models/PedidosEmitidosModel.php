<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class PedidosEmitidosModel {
    public static function getPedidosEmitidos($offset, $items_per_page) {
        global $wpdb;

        $query_emitidos = "
            SELECT 
                o.id AS order_id, 
                d.document_type, 
                d.document_number, 
                d.document_date, 
                d.rut_receptor, 
                d.status,
                os.total_sales AS total
            FROM {$wpdb->prefix}wc_orders AS o
            LEFT JOIN {$wpdb->prefix}sii_wc_dtes AS d ON o.id = d.order_id
            LEFT JOIN {$wpdb->prefix}wc_order_stats AS os ON o.id = os.order_id
            WHERE d.document_number IS NOT NULL
            ORDER BY o.id DESC
            LIMIT %d, %d
        ";

        return $wpdb->get_results($wpdb->prepare($query_emitidos, $offset, $items_per_page), ARRAY_A);
    }

    public static function getTotalPedidosEmitidos() {
        global $wpdb;

        return $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}wc_orders AS o
            LEFT JOIN {$wpdb->prefix}sii_wc_dtes AS d ON o.id = d.order_id
            WHERE d.document_number IS NOT NULL
        ");
    }
}

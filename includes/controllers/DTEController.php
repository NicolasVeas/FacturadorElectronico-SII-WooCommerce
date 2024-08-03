<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class DTEController {
    public static function init() {
        add_action('wp_ajax_cargar_pagina', array(__CLASS__, 'cargarPagina'));
    }

    public static function get_pedidos_emitidos($offset, $limit) {
        global $wpdb;
        $query = "
            SELECT 
                o.id AS order_id, 
                d.document_type, 
                d.document_number AS folios, 
                d.document_date, 
                d.rut_receptor, 
                d.status,
                os.total_sales AS total,
                o.billing_email AS email_receptor
            FROM {$wpdb->prefix}wc_orders AS o
            LEFT JOIN {$wpdb->prefix}sii_wc_dtes AS d ON o.id = d.order_id
            LEFT JOIN {$wpdb->prefix}wc_order_stats AS os ON o.id = os.order_id
            WHERE d.document_number IS NOT NULL
            ORDER BY d.document_number DESC
            LIMIT %d, %d
        ";
        return $wpdb->get_results($wpdb->prepare($query, $offset, $limit), ARRAY_A);
    }

    public static function count_pedidos_emitidos() {
        global $wpdb;
        $query = "
            SELECT COUNT(*)
            FROM {$wpdb->prefix}wc_orders AS o
            LEFT JOIN {$wpdb->prefix}sii_wc_dtes AS d ON o.id = d.order_id
            WHERE d.document_number IS NOT NULL
        ";
        return $wpdb->get_var($query);
    }

    public static function cargarPagina() {
        if (!isset($_POST['page']) || !isset($_POST['section'])) {
            wp_send_json_error('Parámetros faltantes.');
        }

        $page = intval($_POST['page']);
        $section = sanitize_text_field($_POST['section']);
        $items_per_page = 4;
        $offset = ($page - 1) * $items_per_page;

        if ($section === 'emitidos') {
            $emitidos = self::get_pedidos_emitidos($offset, $items_per_page);
            $total_emitidos = self::count_pedidos_emitidos();
            $total_pages = ceil($total_emitidos / $items_per_page);

            ob_start();
            include SII_WC_PLUGIN_PATH . 'includes/views/admin/pedidos-emitidos.php';
            $data = ob_get_clean();

            wp_send_json_success($data);
        } else if ($section === 'no_emitidos') {
            // Aquí deberás implementar la lógica para los pedidos no emitidos
        } else {
            wp_send_json_error('Sección no válida.');
        }
    }
}

DTEController::init();

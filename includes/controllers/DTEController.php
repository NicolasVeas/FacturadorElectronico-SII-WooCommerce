<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class DTEController {
    public static function init() {
        add_action('wp_ajax_ver_dte', array(__CLASS__, 'verDTE'));
        add_action('wp_ajax_generar_dte', array(__CLASS__, 'generarDTE'));
        add_action('wp_ajax_cargar_datos_emisor', array(__CLASS__, 'cargarDatosEmisor'));
    }

    public static function verDTE() {
        global $wpdb;
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

        if (!$dte) {
            wp_send_json_error('DTE no encontrado');
            return;
        }

        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");

        if (!$emisor) {
            wp_send_json_error('Datos del emisor no encontrados.');
            return;
        }

        ob_start();
        include SII_WC_PLUGIN_PATH . 'includes/views/admin/ver-dte-modal.php';
        $output = ob_get_clean();
        wp_send_json_success($output);
    }

    public static function cargarDatosEmisor() {
        global $wpdb;
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error('Pedido no encontrado');
            return;
        }

        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");

        if (!$emisor) {
            wp_send_json_error('Datos del emisor no encontrados.');
            return;
        }

        ob_start();
        include SII_WC_PLUGIN_PATH . 'includes/views/admin/generar-dte-modal.php';
        $output = ob_get_clean();
        wp_send_json_success($output);
    }

    public static function generarDTE() {
        global $wpdb;
        $order_id = intval($_POST['order_id']);
        parse_str($_POST['form_data'], $datos);

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error('Pedido no encontrado');
            return;
        }

        $api_handler = new ApiHandler();
        $result = $api_handler->crearFacturaDTE($order, $datos);

        if ($result['success']) {
            wp_send_json_success('DTE generado exitosamente');
        } else {
            wp_send_json_error('Error al generar el DTE: ' . $result['response']);
        }
    }
}

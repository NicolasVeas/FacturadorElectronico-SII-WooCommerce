<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class VerDteController {
    public static function init() {
        add_action('wp_ajax_view_dte_modal', array(__CLASS__, 'viewDteModal'));
    }

    public static function viewDteModal() {
        check_ajax_referer('get_pedidos_emitidos_nonce', 'nonce');

        global $wpdb;

        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(array('message' => 'Pedido no encontrado'));
            return;
        }

        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

        if (!$dte) {
            wp_send_json_error(array('message' => 'DTE no encontrado'));
            return;
        }

        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");

        $rut_emisor = $emisor ? $emisor->rut : '';
        $razon_social_emisor = $emisor ? $emisor->razon_social : '';
        $actecos_emisor = $emisor ? json_decode($emisor->actecos, true)[0]['acteco'] : '';
        $direccion_origen_emisor = $emisor ? $emisor->direccion_origen : '';
        $comuna_origen_emisor = $emisor ? $emisor->comuna_origen : '';
        $giro_emisor = $emisor ? $emisor->giro : '';
        $sucursal_emisor = $emisor ? $emisor->sucursal : '';
        $ciudad_origen_emisor = $emisor ? $emisor->ciudad_origen : '';

        $rut_receptor = get_post_meta($order_id, '_billing_rut', true);
        $razon_social_receptor = get_post_meta($order_id, '_billing_razon_social', true);
        $giro_receptor = get_post_meta($order_id, '_billing_giro', true);
        $direccion_destino_receptor = $order->get_billing_address_1();
        $comuna_destino_receptor = $order->get_billing_city();
        $ciudad_destino_receptor = $order->get_billing_city();

        $totales = array(
            'monto_iva' => round($order->get_total_tax()),
            'monto_neto' => round($order->get_subtotal()),
            'monto_exento' => 0,
            'monto_total' => round($order->get_total()),
            'tasa_iva' => 19.00
        );

        $detalle_productos = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $detalle_productos[] = array(
                'nombre_item' => $product->get_name(),
                'cantidad_item' => $item->get_quantity(),
                'valor_unitario' => round($item->get_total() / $item->get_quantity()),
                'monto_item' => round($item->get_total())
            );
        }

        $document_number = $dte->document_number;
        $document_date = date('Y-m-d', strtotime($dte->document_date));
        $document_type = $dte->document_type == 33 ? 'Factura Electrónica' : ($dte->document_type == 39 ? 'Boleta Electrónica' : 'N/A');

        ob_start();
        include SII_WC_PLUGIN_PATH . 'includes/views/admin/ver-dte-modal.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }
}

VerDteController::init();

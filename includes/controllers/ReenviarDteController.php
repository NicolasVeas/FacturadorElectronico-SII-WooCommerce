<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class ReenviarDteController {
    public static function init() {
        add_action('wp_ajax_reenviar_dte_modal', array(__CLASS__, 'reenviarDteModal'));
        add_action('wp_ajax_send_reenviar_dte', array(__CLASS__, 'sendReenviarDte'));
    }

    public static function reenviarDteModal() {
        check_ajax_referer('send_reenviar_dte_nonce', 'nonce');

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
        if (!$emisor) {
            wp_send_json_error(array('message' => 'Datos del emisor no encontrados'));
            return;
        }

        $correo_comprador = $order->get_billing_email();
        $correo_emisor = $emisor->correo;
        $document_type = $dte->document_type;
        $document_number = $dte->document_number;
        $nombre_comprador = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $document_type_formatted = self::formatDocumentType($document_type);

        $data = array(
            'correo_comprador' => $correo_comprador,
            'correo_emisor' => $correo_emisor,
            'folio' => $document_number,
            'document_type' => $document_type,
            'nombre_comprador' => $nombre_comprador,
            'document_type_formatted' => $document_type_formatted,
            'order_id' => $order_id
        );

        ob_start();
        include SII_WC_PLUGIN_PATH . 'includes/views/admin/reenviar-dte-modal.php';
        $html = ob_get_clean();

        // Generar un nuevo nonce para la acción de enviar
        $new_nonce = wp_create_nonce('send_reenviar_dte_nonce');

        wp_send_json_success(array('html' => $html, 'data' => $data, 'nonce' => $new_nonce));
    }

    public static function sendReenviarDte() {
        check_ajax_referer('send_reenviar_dte_nonce', 'nonce');

        $correo_comprador = sanitize_email($_POST['correo_comprador']);
        $correo_emisor = sanitize_email($_POST['correo_emisor']);
        $content = sanitize_textarea_field($_POST['content']);
        $subject = sanitize_text_field($_POST['subject']);
        $folio = intval($_POST['folio']);
        $document_type = intval($_POST['document_type']);
        $rut_receptor = sanitize_text_field($_POST['rut_receptor']);
        $order_id = intval($_POST['order_id']);

        if (!$document_type) {
            global $wpdb;
            $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));
            $document_type = intval($dte->document_type);
        }

        $response = ReenviarDteModel::reenviarDte($correo_comprador, $correo_emisor, $content, $subject, $folio, $document_type, $rut_receptor);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        } else {
            wp_send_json_success(array('message' => 'Correo enviado exitosamente'));
        }
    }

    private static function formatDocumentType($type) {
        switch ($type) {
            case 33:
                return 'Factura Electronica';
            case 39:
                return 'Boleta Electronica';
            case 61:
                return 'Nota de Credito';
            default:
                return 'Documento';
        }
    }
}

ReenviarDteController::init();

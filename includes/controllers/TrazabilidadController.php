<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class TrazabilidadController {
    public static function init() {
        add_action('wp_ajax_view_trazabilidad_modal', array(__CLASS__, 'viewTrazabilidadModal'));
    }

    public static function viewTrazabilidadModal() {
        check_ajax_referer('view_trazabilidad_nonce', 'nonce');

        global $wpdb;

        $order_id = intval($_POST['order_id']);
        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

        if (!$dte) {
            wp_send_json_error(array('message' => 'DTE no encontrado'));
            return;
        }

        $token = TrazabilidadModel::get_token();
        if (!$token) {
            wp_send_json_error(array('message' => 'Error al obtener el token'));
            return;
        }

        $response = TrazabilidadModel::get_trazabilidad($dte->document_type, $dte->document_number, $token);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data[0]['stages'])) {
            wp_send_json_error(array('message' => 'Error al procesar la respuesta de la API'));
            return;
        }

        $trazabilidad = $data[0]['stages'];

        // Ordenar trazabilidad por fecha desde el más reciente al más antiguo
        usort($trazabilidad, function($a, $b) {
            return strtotime($b['register_date']) - strtotime($a['register_date']);
        });

        ob_start();
        include SII_WC_PLUGIN_PATH . 'includes/views/admin/ver-trazabilidad-modal.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html, 'trazabilidad' => $trazabilidad));
    }
}

TrazabilidadController::init();

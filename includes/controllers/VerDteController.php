<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class VerDteController {
    public static function init() {
        add_action('wp_ajax_view_dte_modal', array(__CLASS__, 'viewDteModal'));
    }

    private static function refreshToken() {
        global $wpdb;
        $credentials = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");

        if (!$credentials) {
            throw new Exception('Credenciales no disponibles.');
        }

        $response = wp_remote_post('https://apibeta.riosoft.cl/enterprise/v1/authorization/login/service_clients', array(
            'headers' => array(
                'email' => $credentials->email,
                'password' => $credentials->password,
            ),
            'method' => 'GET',
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            throw new Exception('Error al refrescar el token.');
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['access_token'])) {
            throw new Exception('Token no obtenido.');
        }

        update_option('sii_wc_api_token', $data['access_token']);
        return $data['access_token'];
    }

    public static function viewDteModal() {
        try {
            check_ajax_referer('view_dte_nonce', 'nonce');

            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            if (!$order_id) {
                throw new Exception('ID de pedido no válido.');
            }

            global $wpdb;

            $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));
            if (!$dte) {
                throw new Exception('DTE no encontrado');
            }

            $document_type = $dte->document_type;
            $folio = $dte->document_number;

            $token = get_option('sii_wc_api_token');
            if (!$token) {
                $token = self::refreshToken();
            }

            // Realiza la solicitud a la API
            $response = wp_remote_get("https://apibeta.riosoft.cl/dtemanager/v1/manager/dtes/type/{$document_type}/folio/{$folio}/content", array(
                'headers' => array(
                    'product' => 'ERP',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
            ));

            if (is_wp_error($response)) {
                throw new Exception('Error al conectarse con la API.');
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code != 200) {
                throw new Exception('Error en la respuesta de la API. Código: ' . $response_code . ' Respuesta: ' . $body);
            }

            // Verifica y procesa la respuesta de la API
            if (!isset($data['documento'])) {
                throw new Exception('Datos del DTE no encontrados en la respuesta de la API.');
            }

            $documento = $data['documento'];
            $encabezado = $documento['encabezado'];
            $totales = $encabezado['totales'] ?? [];
            $emisor = $encabezado['emisor'] ?? [];
            $receptor = $encabezado['receptor'] ?? [];
            $detalle_productos = $documento['detalle_productos'] ?? [];

            // Registro de datos para depuración
            error_log('Datos obtenidos de la API: ' . print_r($data, true));

            // Paso de datos a la vista
            $view_data = array(
                'document_number' => $encabezado['id_documento']['folio'] ?? '',
                'document_date' => $encabezado['id_documento']['fecha_emision_contable'] ?? '',
                'document_type' => $encabezado['id_documento']['tipo_dte'] ?? '',
                'totales' => $totales,
                'emisor' => $emisor,
                'receptor' => $receptor,
                'detalle_productos' => $detalle_productos
            );

            // Registro de datos enviados a la vista para depuración
            error_log('Datos enviados a la vista: ' . print_r($view_data, true));

            ob_start();
            include SII_WC_PLUGIN_PATH . 'includes/views/admin/ver-dte-modal.php';
            $html = ob_get_clean();

            wp_send_json_success(array('html' => $html));
        } catch (Exception $e) {
            error_log('Error en VerDteController: ' . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}

VerDteController::init();

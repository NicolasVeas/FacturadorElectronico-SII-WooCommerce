<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class EmitterController {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'adminMenu'));
    }

    public static function adminMenu() {
        add_submenu_page(
            'woocommerce',
            'Emisor Configuración',
            'Emisor Configuración',
            'manage_options',
            'emisor-settings',
            array(__CLASS__, 'mostrarPaginaEmisor')
        );
    }

    public static function mostrarPaginaEmisor() {
        global $wpdb;

        // Función para validar el RUT
        function valida_rut($rutCompleto) {
            if (!preg_match('/^[0-9]+-[0-9kK]{1}$/', $rutCompleto)) {
                return false;
            }
            $tmp = explode('-', $rutCompleto);
            $digv = strtolower($tmp[1]);
            $rut = intval($tmp[0]);
            $m = 0;
            $s = 1;
            while ($rut) {
                $s = ($s + $rut % 10 * (9 - $m++ % 6)) % 11;
                $rut = intval($rut / 10);
            }
            return $s ? $s - 1 == $digv : 'k' == $digv;
        }

        // Guardar datos del emisor
        $message = '';
        if (isset($_POST['sii_wc_guardar_emisor'])) {
            $rut = sanitize_text_field($_POST['sii_wc_rut']);
            $razon_social = sanitize_text_field($_POST['sii_wc_razon_social']);
            $acteco = sanitize_text_field($_POST['sii_wc_acteco']);
            $direccion_origen = sanitize_text_field($_POST['sii_wc_direccion_origen']);
            $comuna_origen = sanitize_text_field($_POST['sii_wc_comuna_origen']);
            $giro = sanitize_text_field($_POST['sii_wc_giro']);
            $sucursal = sanitize_text_field($_POST['sii_wc_sucursal']);
            $ciudad_origen = sanitize_text_field($_POST['sii_wc_ciudad_origen']);

            // Validar RUT
            if (!valida_rut($rut)) {
                $message = 'invalid_rut';
            } else {
                $data = array(
                    'rut' => $rut,
                    'razon_social' => $razon_social,
                    'actecos' => json_encode(array(array('acteco' => $acteco))),
                    'direccion_origen' => $direccion_origen,
                    'comuna_origen' => $comuna_origen,
                    'giro' => $giro,
                    'sucursal' => $sucursal,
                    'ciudad_origen' => $ciudad_origen
                );
                if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sii_wc_emitters") > 0) {
                    $result = $wpdb->update("{$wpdb->prefix}sii_wc_emitters", $data, array('id' => 1));
                } else {
                    $result = $wpdb->insert("{$wpdb->prefix}sii_wc_emitters", $data);
                }

                if ($result !== false) {
                    $message = 'success';
                } else {
                    $message = 'error';
                }
            }

            // Redireccionar con el parámetro del resultado
            wp_redirect(add_query_arg('message', $message, wp_get_referer()));
            exit;
        }

        // Obtener el mensaje de la URL
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");
        $acteco = isset($emisor->actecos) ? json_decode($emisor->actecos)[0]->acteco : '';

        include SII_WC_PLUGIN_PATH . 'includes/views/admin/emisor-settings.php';
    }
}

<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once SII_WC_PLUGIN_PATH . 'includes/models/EmisorModel.php';

class EmisorController {
    private $emisorModel;

    public function __construct() {
        $this->emisorModel = new EmisorModel();
    }

    public function handle_request() {
        if (isset($_POST['sii_wc_guardar_emisor'])) {
            $rut = strtoupper(sanitize_text_field($_POST['sii_wc_rut']));
            $razon_social = strtoupper(sanitize_text_field($_POST['sii_wc_razon_social']));
            $acteco = sanitize_text_field($_POST['sii_wc_acteco']); // Solo números
            $direccion_origen = strtoupper(sanitize_text_field($_POST['sii_wc_direccion_origen']));
            $comuna_origen = strtoupper(sanitize_text_field($_POST['sii_wc_comuna_origen']));
            $giro = strtoupper(sanitize_text_field($_POST['sii_wc_giro']));
            $sucursal = strtoupper(sanitize_text_field($_POST['sii_wc_sucursal']));
            $ciudad_origen = strtoupper(sanitize_text_field($_POST['sii_wc_ciudad_origen']));
            $correo = sanitize_email($_POST['sii_wc_correo']);
            
            // Validar RUT
            if (!$this->valida_rut($rut)) {
                wp_redirect(add_query_arg('message', 'invalid_rut', wp_get_referer()));
                exit;
            }

            $data = array(
                'rut' => $rut,
                'razon_social' => $razon_social,
                'actecos' => json_encode(array(array('acteco' => $acteco))),
                'direccion_origen' => $direccion_origen,
                'comuna_origen' => $comuna_origen,
                'giro' => $giro,
                'sucursal' => $sucursal,
                'ciudad_origen' => $ciudad_origen,
                'correo' => $correo
            );

            $result = $this->emisorModel->saveEmisor($data);

            if ($result === false) {
                wp_redirect(add_query_arg('message', 'error', wp_get_referer()));
            } elseif ($result === 0) {
                wp_redirect(add_query_arg('message', 'no_changes', wp_get_referer()));
            } else {
                wp_redirect(add_query_arg('message', 'success', wp_get_referer()));
            }
            exit;
        }
    }

    public function getEmisorData() {
        return $this->emisorModel->getEmisor();
    }

    private function valida_rut($rutCompleto) {
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
}
?>

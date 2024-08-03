<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once SII_WC_PLUGIN_PATH . 'includes/models/CredencialesModel.php';

class CredencialesController {
    private $model;

    public function __construct() {
        $this->model = new CredencialesModel();
    }

    public function handle_request() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = sanitize_text_field($_POST['sii_wc_email']);
            $password = sanitize_text_field($_POST['sii_wc_password']);

            // Verificar las credenciales ingresadas
            $response = $this->model->verificarCredenciales($email, $password);

            if ($response['status'] === 200 && isset($response['access_token'])) {
                $result = $this->model->saveCredenciales($email, $password);

                if ($result !== false) {
                    $message = 'success';
                } else {
                    $message = 'error';
                }
            } else {
                $message = 'error';
            }

            // Redireccionar con el parámetro del resultado
            wp_redirect(add_query_arg('message', $message, wp_get_referer()));
            exit;
        }
    }

    public function getCredenciales() {
        return $this->model->getCredenciales();
    }
}

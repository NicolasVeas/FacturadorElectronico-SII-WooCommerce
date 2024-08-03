<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class CertificateController {
    public static function handle_request() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['certificate_id']) && isset($_POST['delete_certificate'])) {
                self::delete_certificate(intval($_POST['certificate_id']));
            } elseif (isset($_FILES['certificate_file'])) {
                self::handle_file_upload();
            }
        }
    }

    public static function handle_file_upload() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }

        if (!isset($_FILES['certificate_file']) || $_FILES['certificate_file']['error'] != UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('upload_error', 'Error al subir el archivo.'));
            exit;
        }

        $token = CertificateModel::get_token();
        if (!$token) {
            wp_redirect(add_query_arg('upload_error', 'Error al obtener el token.'));
            exit;
        }

        $file = $_FILES['certificate_file'];
        $certificate_owner_rut = sanitize_text_field($_POST['certificate_owner_rut']);
        $certificate_password = sanitize_text_field($_POST['certificate_password']);

        $response = CertificateModel::upload_certificate($token, $certificate_owner_rut, $certificate_password, $file);

        if ($response['status_code'] === 201) {
            wp_redirect(add_query_arg(array('upload_success' => '1', 'upload_error' => false)));
        } else {
            wp_redirect(add_query_arg(array('upload_success' => false, 'upload_error' => $response['body'])));
        }
        exit;
    }

    public static function delete_certificate($certificate_id) {
        $token = CertificateModel::get_token();
        if (!$token) {
            wp_redirect(add_query_arg('delete_error', 'Error al obtener el token.'));
            exit;
        }

        $http_code = CertificateModel::delete_certificate($token, $certificate_id);

        if ($http_code === 204 || $http_code === 202) {
            wp_redirect(add_query_arg(array('delete_success' => $certificate_id, 'delete_rut' => sanitize_text_field($_POST['certificate_rut']))));
        } else {
            wp_redirect(add_query_arg('delete_error', 'Código de estado HTTP: ' . $http_code));
        }
        exit;
    }
}
?>

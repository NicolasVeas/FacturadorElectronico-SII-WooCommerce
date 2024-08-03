<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class FoliosController {
    public static function mostrarFolios() {
        $upload_success = isset($_GET['upload_success']) ? boolval($_GET['upload_success']) : false;
        $upload_error = isset($_GET['upload_error']) ? sanitize_text_field($_GET['upload_error']) : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['caf_file'])) {
            self::handle_file_upload();
        }

        $token = FoliosModel::get_token();
        if (!$token) {
            FoliosView::render_error('Error al obtener el token.');
            return;
        }

        $response = FoliosModel::request_api(FoliosModel::$api_url_folios, $token);

        if (is_wp_error($response)) {
            FoliosView::render_error('Error al obtener los datos de la API: ' . $response->get_error_message());
            error_log('Error al obtener los datos de la API: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            FoliosView::render_error('Error al procesar los datos de la API: ' . json_last_error_msg());
            error_log('Error al procesar los datos de la API: ' . json_last_error_msg());
            error_log('Respuesta completa: ' . $body);
            return;
        }

        // Filtrar datos para tipo de documento 33, 39 y 61
        $filtered_data = array_filter($data, function ($item) {
            return is_array($item) && in_array($item['dte_type'], [33, 39, 61]);
        });

        FoliosView::render($upload_success, $upload_error, $filtered_data);
    }

    public static function handle_file_upload() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }

        if (!isset($_FILES['caf_file']) || $_FILES['caf_file']['error'] != UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('upload_error', 'Error al subir el archivo.'));
            exit;
        }

        $token = FoliosModel::get_token();
        if (!$token) {
            wp_redirect(add_query_arg('upload_error', 'Error al obtener el token.'));
            exit;
        }

        $file = $_FILES['caf_file'];
        $filename = $file['tmp_name'];
        $filetype = $file['type'];
        $filecontent = file_get_contents($filename);

        $boundary = '------WebKitFormBoundary' . wp_generate_password(24, false);

        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'product' => 'ERP'
        );

        $body = "--$boundary\r\n";
        $body .= 'Content-Disposition: form-data; name="caf_file"; filename="' . basename($file['name']) . '"' . "\r\n";
        $body .= "Content-Type: $filetype\r\n\r\n";
        $body .= $filecontent . "\r\n";
        $body .= "--$boundary--\r\n";

        $response = wp_remote_post(FoliosModel::$api_url_upload, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60, // Aumentar el tiempo de espera a 60 segundos
        ));

        error_log('Respuesta del endpoint de subir archivo: ' . print_r($response, true));

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code === 201) {
            wp_redirect(add_query_arg(array('upload_success' => '1', 'upload_error' => false)));
        } else {
            wp_redirect(add_query_arg(array('upload_success' => false, 'upload_error' => $body)));
        }
        exit;
    }
}
?>

<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class CertificateModel {
    private static $api_url_token = 'https://apibeta.riosoft.cl/enterprise/v1/authorization/login/service_clients';
    private static $api_url_upload = 'https://apibeta.riosoft.cl/dtemanager/v1/sii/certificate/upload';
    private static $api_url_certificates = 'https://apibeta.riosoft.cl/dtemanager/v1/manager/certificates';

    public static function get_token() {
        $token = get_option('sii_wc_api_token');
        if (!$token) {
            $token = self::refresh_token();
        }
        return $token;
    }

    private static function refresh_token() {
        global $wpdb;
        $credentials = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");

        if (!$credentials) {
            error_log("Error: Credenciales no encontradas.");
            return false;
        }

        $response = wp_remote_post(self::$api_url_token, array(
            'headers' => array(
                'email' => $credentials->email,
                'password' => $credentials->password,
            ),
            'method' => 'GET',
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            error_log('Error al obtener el token: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error al procesar la respuesta del token (JSON error): ' . json_last_error_msg());
            error_log('Respuesta completa: ' . $body);
            return false;
        }

        if (!isset($data['access_token'])) {
            error_log('Error al procesar la respuesta del token: Access token no encontrado.');
            error_log('Respuesta completa: ' . $body);
            return false;
        }

        update_option('sii_wc_api_token', $data['access_token']);
        return $data['access_token'];
    }

    public static function get_certificates($token) {
        $response = wp_remote_get(self::$api_url_certificates, array(
            'headers' => array(
                'product' => 'ERP',
                'Authorization' => 'Bearer ' . $token,
            ),
            'timeout' => 60,
        ));

        if (wp_remote_retrieve_response_code($response) === 401) {
            $token = self::refresh_token();
            if ($token) {
                $response = wp_remote_get(self::$api_url_certificates, array(
                    'headers' => array(
                        'product' => 'ERP',
                        'Authorization' => 'Bearer ' . $token,
                    ),
                    'timeout' => 60,
                ));
            } else {
                error_log('Error al obtener un nuevo token.');
                return new WP_Error('token_error', 'Error al obtener un nuevo token.');
            }
        }

        if (is_wp_error($response)) {
            error_log('Error al obtener los datos de los certificados: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error al procesar los datos de los certificados: ' . json_last_error_msg());
            error_log('Respuesta completa: ' . $body);
            return [];
        }

        return $data;
    }

    public static function upload_certificate($token, $certificate_owner_rut, $certificate_password, $file) {
        $filename = $file['tmp_name'];
        $filetype = $file['type'];
        $filecontent = file_get_contents($filename);

        $boundary = '------WebKitFormBoundary' . wp_generate_password(24, false);

        $headers = array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'product' => 'ERP',
            'certificate_password' => $certificate_password,
            'certificate_owner_rut' => $certificate_owner_rut,
        );

        $body = "--$boundary\r\n";
        $body .= 'Content-Disposition: form-data; name="certificate_file"; filename="' . basename($file['name']) . '"' . "\r\n";
        $body .= "Content-Type: $filetype\r\n\r\n";
        $body .= $filecontent . "\r\n";
        $body .= "--$boundary--\r\n";

        $response = wp_remote_post(self::$api_url_upload, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60, // Aumentar el tiempo de espera a 60 segundos
        ));

        error_log('Respuesta del endpoint de subir archivo: ' . print_r($response, true));

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        return ['status_code' => $status_code, 'body' => $body];
    }

    public static function delete_certificate($token, $certificate_id) {
        $api_url_delete_certificate = self::$api_url_certificates . '/' . $certificate_id;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url_delete_certificate);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'product: ERP',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ));

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        error_log('Respuesta del endpoint de eliminar certificado: ' . print_r($response, true));
        error_log('Código de estado HTTP: ' . $http_code);

        return $http_code;
    }
}
?>

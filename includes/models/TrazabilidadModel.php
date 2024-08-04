<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class TrazabilidadModel {
    public static $api_url_token = 'https://apibeta.riosoft.cl/enterprise/v1/authorization/login/service_clients';
    public static $api_url_trazabilidad = 'https://apibeta.riosoft.cl/dtemanager/v1/manager/dtes';

    public static function get_token() {
        $token = get_option('sii_wc_api_token');
        if (!$token) {
            $token = self::refresh_token();
        }
        return $token;
    }

    public static function refresh_token() {
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

    public static function get_trazabilidad($document_type, $document_number, $token) {
        $url = self::$api_url_trazabilidad . "?page=1&dte_types=$document_type&dte_numbers=$document_number";

        $response = wp_remote_get($url, array(
            'headers' => array(
                'product' => 'ERP',
                'Authorization' => 'Bearer ' . $token,
            ),
            'timeout' => 60,
        ));

        if (wp_remote_retrieve_response_code($response) === 401) {
            $token = self::refresh_token();
            if ($token) {
                $response = wp_remote_get($url, array(
                    'headers' => array(
                        'product' => 'ERP',
                        'Authorization' => 'Bearer ' . $token,
                    ),
                    'timeout' => 60,
                ));
            } else {
                return new WP_Error('token_error', 'Error al obtener un nuevo token.');
            }
        }

        return $response;
    }
}

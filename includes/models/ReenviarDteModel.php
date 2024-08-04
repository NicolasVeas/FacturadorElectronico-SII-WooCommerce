<?php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class ReenviarDteModel {
    private static $api_url_token = 'https://apibeta.riosoft.cl/enterprise/v1/authorization/login/service_clients';
    private static $api_url_reenviar = 'https://apibeta.riosoft.cl/dtemanager/v1/manager/email/send';

    public static function reenviarDte($correo_comprador, $correo_emisor, $content, $subject, $folio, $document_type, $rut_receptor) {
        $token = self::get_token();
        if (!$token) {
            return new WP_Error('token_error', 'Error al obtener el token.');
        }

        $data = array(
            'receptors' => array($correo_comprador),
            'receptors_cc' => array($correo_emisor),
            'content' => $content,
            'subject' => $subject,
            'dte_info' => array(
                'folio' => $folio,
                'document_type' => $document_type,
                'rut_receptor' => $rut_receptor
            )
        );

        // Agregar registro de depuración
        error_log('Datos enviados a la API: ' . print_r($data, true));

        $response = wp_remote_post(self::$api_url_reenviar, array(
            'method' => 'POST',
            'body' => json_encode($data),
            'headers' => array(
                'product' => 'ERP',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Agregar registro de depuración
        error_log('Respuesta de la API: ' . $response_code . ' ' . $body);

        if ($response_code === 202) {
            return $data;
        } elseif ($response_code === 401) {
            $token = self::refresh_token();
            if ($token) {
                return self::reenviarDte($correo_comprador, $correo_emisor, $content, $subject, $folio, $document_type, $rut_receptor);
            } else {
                return new WP_Error('token_error', 'Error al obtener un nuevo token.');
            }
        } else {
            return new WP_Error('api_error', $body);
        }
    }

    private static function get_token() {
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
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['access_token'])) {
            return false;
        }

        update_option('sii_wc_api_token', $data['access_token']);
        return $data['access_token'];
    }
}

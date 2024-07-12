<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class FoliosView {
    private static $api_url_token = 'https://apibeta.riosoft.cl/enterprise/v1/authorization/login/service_clients';
    private static $api_url_folios = 'https://apibeta.riosoft.cl/dtemanager/v1/cafs/folios/summary';
    private static $api_url_upload = 'https://apibeta.riosoft.cl/dtemanager/v1/cafs/upload';

    public static function mostrarFolios() {
        $upload_success = isset($_GET['upload_success']) ? boolval($_GET['upload_success']) : false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['caf_file'])) {
            self::handle_file_upload();
        }

        $token = self::get_token();
        if (!$token) {
            echo 'Error al obtener el token.';
            return;
        }

        $response = self::request_api(self::$api_url_folios, $token);

        if (is_wp_error($response)) {
            echo 'Error al obtener los datos de la API: ' . $response->get_error_message();
            error_log('Error al obtener los datos de la API: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            echo 'Error al procesar los datos de la API: ' . json_last_error_msg();
            error_log('Error al procesar los datos de la API: ' . json_last_error_msg());
            error_log('Respuesta completa: ' . $body);
            return;
        }

        // Filtrar datos para tipo de documento 33 y 39
        $filtered_data = array_filter($data, function ($item) {
            return is_array($item) && in_array($item['dte_type'], [33, 39]);
        });

        if ($upload_success) {
            echo '<div class="notice notice-success is-dismissible"><p>Archivo CAF subido correctamente</p></div>';
        }

        ?>
        <h2>Subir archivo CAF</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="caf_file" accept=".xml" required />
            <?php submit_button('Subir Archivo'); ?>
        </form>
        <hr />
        <h2>Folios de Documentos</h2>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Tipo de Documento</th>
                    <th>Folio Hasta</th>
                    <th>Último Folio Utilizado</th>
                    <th>Folios Usados</th>
                    <th>Folios Cancelados</th>
                    <th>Folios Liberados</th>
                    <th>Folios Disponibles</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered_data)) : ?>
                    <tr>
                        <td colspan="7">No se encontraron datos.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($filtered_data as $item): ?>
                        <tr>
                            <td><?php echo $item['dte_type'] == 33 ? 'Factura Electrónica' : 'Boleta Electrónica'; ?></td>
                            <td><?php echo esc_html($item['caf_until']); ?></td>
                            <td><?php echo esc_html($item['max_used_folio']); ?></td>
                            <td><?php echo esc_html($item['used_folios_quantity']); ?></td>
                            <td><?php echo esc_html($item['cancelled_folios_quantity']); ?></td>
                            <td><?php echo esc_html($item['released_folios_quantity']); ?></td>
                            <td><?php echo esc_html($item['available_folios_quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    public static function handle_file_upload() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para realizar esta acción.');
        }

        if (!isset($_FILES['caf_file']) || $_FILES['caf_file']['error'] != UPLOAD_ERR_OK) {
            echo 'Error al subir el archivo.';
            error_log('Error al subir el archivo: ' . $_FILES['caf_file']['error']);
            return;
        }

        $token = self::get_token();
        if (!$token) {
            echo 'Error al obtener el token.';
            return;
        }

        $file = $_FILES['caf_file'];
        $filename = $file['tmp_name'];
        $filetype = $file['type'];
        $filecontent = file_get_contents($filename);

        $boundary = wp_generate_password(24);

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

        $response = wp_remote_post(self::$api_url_upload, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 60, // Aumentar el tiempo de espera a 60 segundos
        ));

        if (is_wp_error($response)) {
            echo 'Error al subir el archivo CAF';
            error_log('Error al subir el archivo CAF: ' . $response->get_error_message());
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code === 201) {
            // Redirigir para mostrar el mensaje de éxito
            wp_redirect(add_query_arg('upload_success', '1'));
            exit;
        } elseif ($status_code === 401) {
            // Si el token ha expirado, obtener uno nuevo y volver a intentar
            $token = self::refresh_token();
            if ($token) {
                self::handle_file_upload();
            } else {
                echo 'Error al obtener un nuevo token.';
                error_log('Error al obtener un nuevo token.');
            }
        } else {
            echo 'Error al subir el archivo CAF: ' . $body;
            error_log('Error al subir el archivo CAF: ' . $body);
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
            error_log("Error: Credenciales no encontradas.");
            return false;
        }

        $response = wp_remote_post(self::$api_url_token, array(
            'headers' => array(
                'email' => $credentials->email,
                'password' => $credentials->password,
            ),
            'method' => 'GET', // Corregir el método
            'timeout' => 60, // Aumentar el tiempo de espera a 60 segundos
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

    private static function request_api($url, $token) {
        $response = wp_remote_get($url, array(
            'headers' => array(
                'product' => 'ERP',
                'Authorization' => 'Bearer ' . $token,
            ),
            'timeout' => 60, // Aumentar el tiempo de espera a 60 segundos
        ));

        if (wp_remote_retrieve_response_code($response) === 401) {
            // Si el token ha expirado, obtener uno nuevo y volver a intentar
            $token = self::refresh_token();
            if ($token) {
                $response = wp_remote_get($url, array(
                    'headers' => array(
                        'product' => 'ERP',
                        'Authorization' => 'Bearer ' . $token,
                    ),
                    'timeout' => 60, // Aumentar el tiempo de espera a 60 segundos
                ));
            } else {
                error_log('Error al obtener un nuevo token.');
                return new WP_Error('token_error', 'Error al obtener un nuevo token.');
            }
        }

        return $response;
    }
}

FoliosView::mostrarFolios();

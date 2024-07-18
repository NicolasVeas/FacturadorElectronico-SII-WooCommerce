<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class ApiHandler {
    private $api_url = 'https://apibeta.riosoft.cl/enterprise/v1/authorization/login/service_clients';
    private $token;

    public function __construct() {
        $this->token = $this->obtenerToken();
    }

    public function verificarCredenciales($email, $password) {
        $response = wp_remote_get($this->api_url, array(
            'headers' => array(
                'email' => $email,
                'password' => $password,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return array('status' => 500, 'message' => $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['access_token'])) {
            return array('status' => 200, 'access_token' => $data['access_token']);
        } else {
            return array('status' => 400, 'message' => isset($data['message']) ? $data['message'] : 'Error desconocido');
        }
    }

    // La función obtenerToken permanece igual, utilizando las credenciales almacenadas en la base de datos.
    public function obtenerToken() {
        global $wpdb;
        $credenciales = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");

        if (!$credenciales) {
            return false;
        }

        $response = wp_remote_get($this->api_url, array(
            'headers' => array(
                'email' => $credenciales->email,
                'password' => $credenciales->password
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            error_log("Error en la solicitud a la API: " . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->access_token)) {
            return $data->access_token;
        } else {
            error_log("Error en la respuesta de la API: " . $body);
            return false;
        }
    }

    private function reintentarConNuevoToken($funcion, $params = []) {
        $this->token = $this->obtenerToken();
        if ($this->token) {
            return call_user_func_array([$this, $funcion], $params);
        } else {
            error_log("No se pudo obtener un nuevo token.");
            return ['success' => false, 'response' => 'No se pudo obtener un nuevo token.'];
        }
    }

    public function crearFacturaDTE($order, $datos) {
        error_log("bandera");
        global $wpdb;
        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");

        if (!$emisor) {
            error_log("Error: Datos del emisor no encontrados.");
            return ['success' => false, 'response' => 'Datos del emisor no encontrados.'];
        }

        $rut_emisor = $emisor->rut;
        $razon_social_emisor = $emisor->razon_social;
        $actecos_emisor = json_decode($emisor->actecos, true)[0]['acteco'];
        $direccion_origen_emisor = $emisor->direccion_origen;
        $comuna_origen_emisor = $emisor->comuna_origen;
        $giro_emisor = $emisor->giro;
        $sucursal_emisor = $emisor->sucursal;
        $ciudad_origen_emisor = $emisor->ciudad_origen;

        $rut_receptor = $datos['rut_receptor'];
        $razon_social_receptor = $datos['razon_social_receptor'];
        $giro_receptor = $datos['giro_receptor'];
        $direccion_destino_receptor = $datos['direccion_destino_receptor'];
        $comuna_destino_receptor = $datos['comuna_destino_receptor'];
        $ciudad_destino_receptor = $datos['ciudad_destino_receptor'];

        $totales = array(
            'monto_iva' => $datos['monto_iva'],
            'monto_neto' => $datos['monto_neto'],
            'monto_exento' => $datos['monto_exento'],
            'monto_total' => $datos['monto_total'],
            'tasa_iva' => 19.00
        );

        $detalle_productos = array();
        foreach ($datos['detalle_productos'] as $producto) {
            $detalle_productos[] = array(
                'nombre_item' => $producto['nombre_item'],
                'cantidad_item' => $producto['cantidad_item'],
                'valor_unitario' => $producto['valor_unitario'],
                'monto_item' => $producto['monto_item']
            );
        }

        $data = array(
            array(
                'documento' => array(
                    'encabezado' => array(
                        'id_documento' => array(
                            'tipo_dte' => intval($datos['tipo_dte']),
                            'fecha_emision_contable' => date('Y-m-d')
                        ),
                        'emisor' => array(
                            'rut' => $rut_emisor,
                            'razon_social' => $razon_social_emisor,
                            'actecos' => array($actecos_emisor),
                            'direccion_origen' => $direccion_origen_emisor,
                            'comuna_origen' => $comuna_origen_emisor,
                            'giro' => $giro_emisor,
                            'sucursal' => $sucursal_emisor,
                            'ciudad_origen' => $ciudad_origen_emisor
                        ),
                        'receptor' => array(
                            'rut' => $rut_receptor,
                            'razon_social' => $razon_social_receptor,
                            'giro' => $giro_receptor,
                            'direccion_destino' => $direccion_destino_receptor,
                            'comuna_destino' => $comuna_destino_receptor,
                            'ciudad_destino' => $ciudad_destino_receptor
                        ),
                        'totales' => $totales
                    ),
                    'detalle_productos' => $detalle_productos
                )
            )
        );

        $response = wp_remote_post('https://apibeta.riosoft.cl/dtemanager/v1/manager/sync/dte/sign/simple/electronic-bill', array(
            'method' => 'POST',
            'body' => json_encode($data),
            'headers' => array(
                'product' => 'ERP',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token
            ),
            'timeout' => 15,
        ));

        error_log("Solicitud enviada a la API: " . json_encode($data));
        error_log("Encabezados de la solicitud: " . json_encode(array(
            'product' => 'ERP',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        )));

        if (is_wp_error($response)) {
            error_log("Error en la solicitud a la API: " . $response->get_error_message());
            return array(
                'success' => false,
                'response' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['status']) && $data['status'] === 'ALL_SIGNED') {
            $this->guardarDTEDatos($order->get_id(), $data['documents'][0], $rut_emisor, $rut_receptor);
            return array('success' => true, 'response' => $data);
        } elseif (isset($data['message']) && $data['message'] === 'Please check details for issue explanation') {
            if (in_array('No CAF found for folio ELECTRONIC_BILL', $data['details'])) {
                $this->notificarFaltaDeFolios();
            }
            return array('success' => false, 'response' => $body);
        } elseif (isset($data['status']) && $data['status'] === 'ERROR') {
            return $this->reintentarConNuevoToken(__FUNCTION__, func_get_args());
        } else {
            return array('success' => false, 'response' => $body);
        }
    }

    private function notificarFaltaDeFolios() {
        // Aquí puedes implementar una lógica para notificar al administrador del sitio que se necesitan folios
        // Por ejemplo, enviar un correo electrónico o mostrar una notificación en el panel de administración
        error_log("No quedan folios disponibles. Es necesario cargar un CAF archivo en formato .XML.");
    }

    private function guardarDTEDatos($order_id, $document_data, $rut_emisor, $rut_receptor) {
        global $wpdb;

        $wpdb->insert("{$wpdb->prefix}sii_wc_dtes", array(
            'order_id' => $order_id,
            'document_type' => $document_data['document_type'],
            'document_number' => $document_data['document_number'],
            'document_date' => $document_data['document_date'],
            'rut_emisor' => $rut_emisor,
            'rut_receptor' => $rut_receptor,
            'status' => 'CREATED'
        ));
    }

    public function agruparDTE($order_id) {
        global $wpdb;

        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d AND status = 'CREATED'", $order_id));

        if (!$dte) {
            error_log("Error: Datos del DTE no encontrados o no en estado CREATED para la orden $order_id.");
            return ['success' => false, 'response' => 'Datos del DTE no encontrados o no en estado CREATED.'];
        }

        $data = array(
            array(
                'document_type' => $dte->document_type,
                'document_number' => $dte->document_number
            )
        );

        error_log("Enviando solicitud de agrupación para la orden $order_id con datos: " . json_encode($data));

        $response = wp_remote_post('https://apibeta.riosoft.cl/dtemanager/v1/manager/sync/dte/sign/sending/group', array(
            'method' => 'POST',
            'body' => json_encode($data),
            'headers' => array(
                'product' => 'ERP',
                'rut_emitter' => $dte->rut_emisor,
                'rut_receptor' => '60803000-K',
                'resolution_date' => '2017-02-14',
                'resolution_number' => '0',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            error_log("Error en la solicitud a la API (agrupar DTE) para la orden $order_id: " . $response->get_error_message());
            return array(
                'success' => false,
                'response' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->status) && $data->status === 'ALL_SIGNED') {
            $this->actualizarEstadoDTE($dte->id, 'GROUPED');
            error_log("DTE agrupado exitosamente para la orden $order_id.");
            $this->actualizarFoliosPendientes($order_id);
            return array(
                'success' => true,
                'response' => $data
            );
        } elseif (isset($data['status']) && $data['status'] === 'ERROR') {
            return $this->reintentarConNuevoToken(__FUNCTION__, func_get_args());
        } else {
            error_log("Error en la respuesta de la API (agrupar DTE) para la orden $order_id: " . $body);
            return array(
                'success' => false,
                'response' => $body
            );
        }
    }

    private function actualizarEstadoDTE($dte_id, $estado) {
        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}sii_wc_dtes",
            array(
                'status' => $estado
            ),
            array('id' => $dte_id)
        );
    }

    public function enviarDocumentoAlSII($order_id) {
        global $wpdb;

        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

        if (!$dte || $dte->status !== 'GROUPED' || !$dte->folio) {
            error_log("Error: Datos del DTE no encontrados, no agrupados o sin folio.");
            return ['success' => false, 'response' => 'Datos del DTE no encontrados, no agrupados o sin folio.'];
        }

        $response = wp_remote_request('https://apibeta.riosoft.cl/dtemanager/v1/manager/sync/dte/sendings/sii/upload/' . $dte->folio, array(
            'method' => 'PUT',
            'headers' => array(
                'product' => 'ERP',
                'Authorization' => 'Bearer ' . $this->token
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            error_log("Error en la solicitud a la API (enviar Documento al SII): " . $response->get_error_message());
            return array(
                'success' => false,
                'response' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data['status'] === 'OK' && isset($data['track_id'])) {
            $wpdb->update(
                "{$wpdb->prefix}sii_wc_dtes",
                array(
                    'status' => 'SENT',
                    'track_id' => $data['track_id']
                ),
                array('id' => $dte->id)
            );
            error_log("Documento enviado al SII para la orden $order_id con track_id {$data['track_id']}.");
            return array('success' => true, 'response' => $data);
        } elseif ($data['status'] === 'SYSTEM_ERROR') {
            $this->agruparDTE($order_id); // Reagrupar el DTE en caso de error del sistema
        } else {
            error_log("Error en la respuesta de la API (enviar Documento al SII): " . $body);
            $wpdb->update(
                "{$wpdb->prefix}sii_wc_dtes",
                array(
                    'status' => 'NO_SENT'
                ),
                array('id' => $dte->id)
            );
            return array(
                'success' => false,
                'response' => $data
            );
        }
    }

    public function obtenerFoliosPaginados() {
        $folios = [];
        $page = 1;
        $min_document_number = $this->obtenerMinDocumentNumber();

        do {
            $response = wp_remote_get("https://apibeta.riosoft.cl/dtemanager/v1/manager/sendings/status/summarized?page=$page", array(
                'headers' => array(
                    'product' => 'ERP',
                    'Authorization' => 'Bearer ' . $this->token
                ),
                'timeout' => 15,
            ));

            if (is_wp_error($response)) {
                error_log("Error al obtener los folios paginados (página $page): " . $response->get_error_message());
                break;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($data)) {
                error_log("Error al procesar los folios paginados (página $page): " . json_last_error_msg());
                break;
            }

            foreach ($data as $folio) {
                $folios[] = $folio;
                if ($folio['related'][0]['document_number'] < $min_document_number) {
                    break 2; // Salir de ambos bucles
                }
            }

            $page++;
        } while (!empty($data));

        return $folios;
    }

    private function obtenerMinDocumentNumber() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT MIN(document_number) FROM {$wpdb->prefix}sii_wc_dtes");
        return $result ? intval($result) : PHP_INT_MAX;
    }

    public function actualizarFoliosPendientes($order_id) {
        global $wpdb;

        $folios_pendientes = $this->obtenerFoliosPaginados();

        if (!$folios_pendientes) {
            error_log("No se encontraron folios pendientes para la orden $order_id.");
            return;
        }

        error_log("Folios pendientes obtenidos para la orden $order_id: " . json_encode($folios_pendientes));

        foreach ($folios_pendientes as $document) {
            if (isset($document['related'][0]['document_number']) && isset($document['folio'])) {
                // Actualizar solo si el document_number corresponde al order_id actual
                $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d AND document_number = %d", $order_id, $document['related'][0]['document_number']));
                if ($dte && $document['effective_status'] === 'NOT_SENT') {
                    $updated = $wpdb->update(
                        "{$wpdb->prefix}sii_wc_dtes",
                        array(
                            'folio' => $document['folio'],
                            'status' => 'GROUPED' // Actualiza el estado a 'GROUPED' si se obtiene el folio
                        ),
                        array('id' => $dte->id)
                    );

                    if ($updated === false) {
                        error_log("Error al actualizar el folio para el document_number: {$document['related'][0]['document_number']} en la orden $order_id.");
                    } else {
                        error_log("Folio actualizado para el document_number: {$document['related'][0]['document_number']} en la orden $order_id.");
                        $this->enviarDocumentoAlSII($order_id); // Enviar el documento al SII después de actualizar el folio
                    }

                    // Asignar solo el folio más reciente (primer coincidencia)
                    break;
                }
            }
        }
    }

    public function obtenerEstadoEnvioAlSII($track_id) {
        $response = wp_remote_get("https://apibeta.riosoft.cl/dtemanager/v1/manager/sendings/status/sii/{$track_id}", array(
            'headers' => array(
                'product' => 'ERP',
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            error_log("Error al obtener el estado del envío al SII: " . $response->get_error_message());
            return array(
                'success' => false,
                'response' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['header']['status'])) {
            return array(
                'success' => true,
                'response' => $data
            );
        } else {
            error_log("Error en la respuesta al obtener el estado del envío al SII: " . $body);
            return array(
                'success' => false,
                'response' => $body
            );
        }
    }

    public function actualizarEstadoEnvioAlSII($track_id) {
        $estado = $this->obtenerEstadoEnvioAlSII($track_id);

        if (!$estado['success']) {
            return;
        }

        $data = $estado['response'];
        $status = $data['header']['status'];
        $summary = $data['header']['summary'];
        $ticket_number = $data['header']['ticket_number'];

        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}sii_wc_dtes",
            array(
                'status' => $status
            ),
            array('track_id' => $track_id)
        );

        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE track_id = %d", $track_id));
        if ($dte) {
            $wpdb->insert(
                "{$wpdb->prefix}sii_wc_shipments",
                array(
                    'dte_id' => $dte->id,
                    'fecha' => current_time('mysql'),
                    'estado' => $status,
                    'detalle' => $summary . " - " . $ticket_number
                )
            );
        }

        error_log("Estado del envío con track_id {$track_id} actualizado a {$status}.");
    }

    public function manejarDTESinFolio() {
        global $wpdb;
        $dtes_sin_folio = $wpdb->get_results("SELECT order_id FROM {$wpdb->prefix}sii_wc_dtes WHERE status = 'GROUPED' AND (folio IS NULL OR folio = 0)");

        foreach ($dtes_sin_folio as $dte) {
            $this->actualizarFoliosPendientes($dte->order_id);
        }
    }
}
?>

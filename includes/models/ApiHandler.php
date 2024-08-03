<?php
// En el archivo ApiHandler.php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
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
        error_log(json_encode($datos));

        global $wpdb;
        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");
    
        if (!$emisor) {
            error_log("Error: Datos del emisor no encontrados.");
            return ['success' => false, 'response' => 'Datos del emisor no encontrados.'];
        }
    
        $rut_emisor = strtoupper($emisor->rut);
        $razon_social_emisor = strtoupper($emisor->razon_social);
        $actecos_emisor = strtoupper(json_decode($emisor->actecos, true)[0]['acteco']);
        $direccion_origen_emisor = strtoupper($emisor->direccion_origen);
        $comuna_origen_emisor = strtoupper($emisor->comuna_origen);
        $giro_emisor = strtoupper($emisor->giro);
        $sucursal_emisor = strtoupper($emisor->sucursal);
        $ciudad_origen_emisor = strtoupper($emisor->ciudad_origen);
    
        $rut_receptor = strtoupper($datos['rut_receptor']);
        $razon_social_receptor = strtoupper($datos['razon_social_receptor']);
        $giro_receptor = strtoupper($datos['giro_receptor']);
        $direccion_destino_receptor = strtoupper($datos['direccion_destino_receptor']);
        $comuna_destino_receptor = strtoupper($datos['comuna_destino_receptor']);
        $ciudad_destino_receptor = strtoupper($datos['ciudad_destino_receptor']);
    
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
                'nombre_item' => strtoupper($producto['nombre_item']),
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
    
        if (is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) === 401) {
                $this->token = $this->refreshToken();
                if ($this->token) {
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
                } else {
                    return array(
                        'success' => false,
                        'response' => 'Error al obtener un nuevo token.'
                    );
                }
            } else {
                error_log("Error en la solicitud a la API: " . $response->get_error_message());
                return array(
                    'success' => false,
                    'response' => $response->get_error_message()
                );
            }
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'ALL_SIGNED') {
            $this->guardarDTEDatos($order->get_id(), $data['documents'][0], $rut_emisor, $rut_receptor);
            return array('success' => true, 'response' => $data);
        } elseif (isset($data['message']) && $data['message'] === 'Please check details for issue explanation') {
            error_log(json_encode($data['details']));
            foreach ($data['details'] as $detail) {
                if (strpos($detail, 'No CAF found for folio ELECTRONIC_BILL') !== false) {
                    error_log("d");
                    error_log("Error: No hay folios disponibles para el tipo de documento ELECTRONIC_BILL.");
                    error_log('Antes de agregar admin_notices');
                    add_action('admin_notices', function() {
                        error_log('Dentro del callback de admin_notices'); // Agrega esta línea
                        echo '<div class="notice notice-error is-dismissible"><p>';
                        _e('Error: No hay folios disponibles para emitir facturas electrónicas. Por favor, tome las medidas necesarias.', 'woocommerce');
                        echo '</p></div>';
                    });
                    
                    error_log('Después de agregar admin_notices');
                    return array('success' => false, 'response' => 'No hay folios disponibles para el tipo de documento ELECTRONIC_BILL.');
                }
            }
            return array('success' => false, 'response' => $body);
        } elseif (isset($data['status']) && $data['status'] === 'ERROR') {
            error_log("e");
            return $this->reintentarConNuevoToken(__FUNCTION__, func_get_args());
        } else {
            error_log("f");
            return array('success' => false, 'response' => $body);
        }
    }
    
    
    
    
    
    
    

    private function refreshToken() {
        global $wpdb;
        $credenciales = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");

        if (!$credenciales) {
            return false;
        }

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'email' => $credenciales->email,
                'password' => $credenciales->password
            ),
            'method' => 'GET',
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            error_log("Error en la solicitud a la API: " . $response->get_error_message());
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

    private function notificarFaltaDeFolios() {
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

        $dtes = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d AND status = 'CREATED'", $order_id));

        if (!$dtes) {
            error_log("Error: Datos del DTE no encontrados o no en estado CREATED para la orden $order_id.");
            return ['success' => false, 'response' => 'Datos del DTE no encontrados o no en estado CREATED.'];
        }

        foreach ($dtes as $dte) {
            $data = array(
                array(
                    'document_type' => $dte->document_type,
                    'document_number' => $dte->document_number
                )
            );

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
                if (wp_remote_retrieve_response_code($response) === 401) {
                    $this->token = $this->refreshToken();
                    if ($this->token) {
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
                    } else {
                        return array(
                            'success' => false,
                            'response' => 'Error al obtener un nuevo token.'
                        );
                    }
                } else {
                    error_log("Error en la solicitud a la API: " . $response->get_error_message());
                    return array(
                        'success' => false,
                        'response' => $response->get_error_message()
                    );
                }
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (isset($data->status) && $data->status === 'ALL_SIGNED') {
                $this->actualizarEstadoDTE($dte->id, 'GROUPED');
                error_log("DTE agrupado exitosamente para la orden $order_id.");
                $this->actualizarFoliosPendientes($order_id);
            } elseif (isset($data->status) && $data->status === 'ERROR') {
                return $this->reintentarConNuevoToken(__FUNCTION__, func_get_args());
            } else {
                error_log("Error en la respuesta de la API (agrupar DTE) para la orden $order_id: " . $body);
                return array(
                    'success' => false,
                    'response' => $body
                );
            }
        }

        return array('success' => true, 'response' => 'DTEs agrupados exitosamente.');
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

        $dtes = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d AND status = 'GROUPED'", $order_id));

        foreach ($dtes as $dte) {
            if (!$dte->folio) {
                error_log("Error: DTE con id {$dte->id} no tiene folio.");
                continue;
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
                continue;
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
                sleep(10);

                $this->verificarYActualizarStages($dte->document_type, $dte->document_number);
            } elseif ($data['status'] === 'SYSTEM_ERROR') {
                $this->agruparDTE($order_id);
            } else {
                error_log("Error en la respuesta de la API (enviar Documento al SII): " . $body);
                $wpdb->update(
                    "{$wpdb->prefix}sii_wc_dtes",
                    array(
                        'status' => 'NO_SENT'
                    ),
                    array('id' => $dte->id)
                );
            }
        }

        return array('success' => true, 'response' => 'Documentos enviados al SII.');
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
                    break 2;
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
                $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d AND document_number = %d", $order_id, $document['related'][0]['document_number']));
                if ($dte && $document['effective_status'] === 'NOT_SENT') {
                    $updated = $wpdb->update(
                        "{$wpdb->prefix}sii_wc_dtes",
                        array(
                            'folio' => $document['folio'],
                            'status' => 'GROUPED'
                        ),
                        array('id' => $dte->id)
                    );

                    if ($updated === false) {
                        error_log("Error al actualizar el folio para el document_number: {$document['related'][0]['document_number']} en la orden $order_id.");
                    } else {
                        error_log("Folio actualizado para el document_number: {$document['related'][0]['document_number']} en la orden $order_id.");
                        $this->enviarDocumentoAlSII($order_id);
                    }

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

        if ($status == 'EPR') {
            $status = 'SENT';
        }

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
            sleep(10);

            $this->verificarYActualizarStages($dte->document_type, $dte->document_number);
        }

        error_log("Estado del envío con track_id {$track_id} actualizado a {$status}.");
    }

    public function verificarYActualizarStages($document_type, $document_number, $reintento = false) {
        global $wpdb;
        error_log($document_type . " " . $document_number . " " . $reintento);
        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE document_type = %d AND document_number = %d", $document_type, $document_number));
        if (!$dte) {
            error_log("Error: Datos del DTE no encontrados para document_type {$document_type} y document_number {$document_number}.");
            return;
        }
        $order_id = $dte->order_id;
        $token = $this->obtenerToken();
        $api_url = "https://apibeta.riosoft.cl/dtemanager/v1/manager/dtes?page=1&dte_types={$document_type}&dte_numbers={$document_number}";

        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'product' => 'ERP',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log("Error al obtener los stages del DTE: " . $response->get_error_message());
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code === 401 && !$reintento) {
            $this->token = $this->obtenerToken(true);
            $this->verificarYActualizarStages($document_type, $document_number, true);
            return;
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            error_log("Error al procesar los stages del DTE: " . json_last_error_msg());
            return;
        }

        $stages = $data[0]['stages'] ?? [];

        usort($stages, function ($a, $b) {
            return strtotime($a['register_date']) - strtotime($b['register_date']);
        });

        $ultimo_stage = end($stages)['stage']['stage_emission'] ?? null;

        $nuevo_estado = null;
        if ($ultimo_stage === 'SII_UPLOAD_DTE_STATUS_RCT' || $ultimo_stage === 'SII_DOC_STATUS_FAU' || $ultimo_stage === 'SII_UPLOAD_DTE_STATUS_OTH') {
            $nuevo_estado = 'REJECTED';
        } elseif ($ultimo_stage === 'SII_DOC_STATUS_DOK') {
            $nuevo_estado = 'ACCEPTED';
        }

        if ($nuevo_estado) {
            $wpdb->update(
                "{$wpdb->prefix}sii_wc_dtes",
                array(
                    'status' => $nuevo_estado
                ),
                array(
                    'document_type' => $document_type,
                    'document_number' => $document_number
                )
            );
            error_log("Estado del DTE actualizado a {$nuevo_estado} para el document_type {$document_type} y document_number {$document_number}.");

            if ($nuevo_estado === 'ACCEPTED') {
                $receptor_email = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = '_billing_email'", $order_id));
                $this->enviarCorreoConXML($order_id, $document_type, $document_number, $receptor_email);
            }
        }
    }

    public function enviarCorreoConXML($order_id, $document_type, $document_number, $receptor_email) {
        global $wpdb;
        error_log($document_type);
        error_log($document_number);
        error_log($receptor_email);
        // Obtener los datos del emisor
        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");
        if (!$emisor) {
            error_log("Error: Datos del emisor no encontrados.");
            return ['success' => false, 'response' => 'Datos del emisor no encontrados.'];
        }
    
        // Obtener el correo del emisor
        $correo_emisor = $emisor->correo;
    
        $token = $this->obtenerToken();
        if (!$token) {
            return ['success' => false, 'response' => 'Error al obtener el token.'];
        }
    
        $api_url = 'https://apibeta.riosoft.cl/dtemanager/v1/manager/email/send';
    
        // Ajuste de la estructura del payload
        $data = array(
            'receptors' => [$receptor_email],
            'receptors_cc' => [$correo_emisor],
            'subject' => "Envio de tipo {$document_type} folio {$document_number} facturacionmipyme20@sii.cl",
            'content' => "Adjunto se encuentra el DTE correspondiente.",
            'dte_info' => array(
                'folio' => $document_number,
                'document_type' => $document_type,
                'rut_receptor' => '60803000-K' // Asumimos que el RUT del receptor es el del emisor
            )
        );
    
        $response = $this->sendEmailRequest($api_url, $data, $token);
    
        if (is_wp_error($response)) {
            error_log("Error al enviar el correo con el XML: " . $response->get_error_message());
            return array(
                'success' => false,
                'response' => $response->get_error_message()
            );
        }
    
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if ($response_code === 401) {
            // Si el token ha expirado, obtener uno nuevo y volver a intentar
            $token = $this->refreshToken();
            if ($token) {
                $response = $this->sendEmailRequest($api_url, $data, $token);
    
                if (is_wp_error($response)) {
                    error_log("Error al enviar el correo con el XML después de refrescar el token: " . $response->get_error_message());
                    return array(
                        'success' => false,
                        'response' => $response->get_error_message()
                    );
                }
    
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
            } else {
                return array('success' => false, 'response' => 'Error al obtener un nuevo token.');
            }
        }
    
        if ($response_code === 202) { // El código 202 indica que el correo fue aceptado para envío
            return array('success' => true, 'response' => $data);
        } else {
            error_log("Error en la respuesta de la API al enviar el correo con el XML: " . $body);
            return array('success' => false, 'response' => $body);
        }
    }
    
    private function sendEmailRequest($api_url, $data, $token) {
        return wp_remote_post($api_url, array(
            'method' => 'POST',
            'body' => json_encode($data),
            'headers' => array(
                'product' => 'ERP',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ),
            'timeout' => 30,
        ));
    }
    
    public function manejarDTESinFolio() {
        global $wpdb;

        $dtes_sin_folio = $wpdb->get_results("SELECT order_id, status FROM {$wpdb->prefix}sii_wc_dtes WHERE (status = 'GROUPED' AND (folio IS NULL OR folio = 0)) OR status = 'CREATED'");

        foreach ($dtes_sin_folio as $dte) {
            if ($dte->status === 'CREATED') {
                $this->agruparDTE($dte->order_id);
            } else {
                $this->actualizarFoliosPendientes($dte->order_id);
            }
        }
    }

    
}
?>

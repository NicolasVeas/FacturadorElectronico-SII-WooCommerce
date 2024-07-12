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

    public function obtenerToken() {
        global $wpdb;
        $credenciales = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");

        if (!$credenciales) {
            return false;
        }

        $response = wp_remote_get($this->api_url, array(
            'headers' => array(
                'email' => $credenciales->email,
                'password' => $credenciales->password,
                'Authorization' => 'Bearer ' . $this->token
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

    public function crearFacturaDTE($order) {
        global $wpdb;
        $emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");

        if (!$emisor) {
            error_log("Error: Datos del emisor no encontrados.");
            return ['success' => false, 'response' => 'Datos del emisor no encontrados.'];
        }

        $rut_emisor = $emisor->rut;
        $razon_social_emisor = $emisor->razon_social;
        $actecos_emisor = json_decode($emisor->actecos, true);
        $direccion_origen_emisor = $emisor->direccion_origen;
        $comuna_origen_emisor = $emisor->comuna_origen;
        $giro_emisor = $emisor->giro;
        $sucursal_emisor = $emisor->sucursal;
        $ciudad_origen_emisor = $emisor->ciudad_origen;

        $rut_receptor = get_post_meta($order->get_id(), '_billing_rut', true);
        $razon_social_receptor = get_post_meta($order->get_id(), '_billing_razon_social', true);
        $giro_receptor = get_post_meta($order->get_id(), '_billing_giro', true);
        $direccion_destino_receptor = $order->get_billing_address_1();
        $comuna_destino_receptor = $order->get_billing_city();
        $ciudad_destino_receptor = $order->get_billing_city();

        $totales = array(
            'monto_iva' => round($order->get_total_tax()),
            'monto_neto' => round($order->get_subtotal()),
            'monto_exento' => 0,
            'monto_total' => round($order->get_total()),
            'tasa_iva' => 19.00
        );

        $detalle_productos = array();
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $detalle_productos[] = array(
                'nombre_item' => $product->get_name(),
                'cantidad_item' => $item->get_quantity(),
                'valor_unitario' => round($item->get_total() / $item->get_quantity()),
                'monto_item' => round($item->get_total())
            );
        }

        $data = array(
            array(
                'documento' => array(
                    'encabezado' => array(
                        'id_documento' => array(
                            'tipo_dte' => 33,
                            'fecha_emision_contable' => date('Y-m-d')
                        ),
                        'emisor' => array(
                            'rut' => $rut_emisor,
                            'razon_social' => $razon_social_emisor,
                            'actecos' => $actecos_emisor,
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
                'response' => $response->get_error_message(),
                'request_body' => $data,
                'request_headers' => array(
                    'product' => 'ERP',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->status) && $data->status === 'ALL_SIGNED') {
            $this->guardarDTEDatos($order->get_id(), $data->documents[0], $rut_emisor, $rut_receptor);
            return array(
                'success' => true,
                'response' => $data,
                'request_body' => $data,
                'request_headers' => array(
                    'product' => 'ERP',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                )
            );
        } else {
            error_log("Error en la respuesta de la API: " . $body);
            return array(
                'success' => false,
                'response' => $body,
                'request_body' => $data,
                'request_headers' => array(
                    'product' => 'ERP',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                )
            );
        }
    }

    private function guardarDTEDatos($order_id, $document_data, $rut_emisor, $rut_receptor) {
        global $wpdb;

        $wpdb->insert("{$wpdb->prefix}sii_wc_dtes", array(
            'order_id' => $order_id,
            'document_type' => $document_data->document_type,
            'document_number' => $document_data->document_number,
            'document_date' => $document_data->document_date,
            'rut_emisor' => $rut_emisor,
            'rut_receptor' => $rut_receptor,
            'status' => 'CREATED',
            'last_successful_status' => 'CREATED'
        ));
    }

    public function agruparDTE($order_id) {
        global $wpdb;

        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

        if (!$dte) {
            error_log("Error: Datos del DTE no encontrados.");
            return ['success' => false, 'response' => 'Datos del DTE no encontrados.'];
        }

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
                'rut_receptor' => $dte->rut_receptor,
                'resolution_date' => '2017-02-14',
                'resolution_number' => '0',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token
            ),
            'timeout' => 15,
        ));

        error_log("Solicitud enviada a la API (agrupar DTE): " . json_encode($data));
        error_log("Encabezados de la solicitud (agrupar DTE): " . json_encode(array(
            'product' => 'ERP',
            'rut_emitter' => $dte->rut_emisor,
            'rut_receptor' => $dte->rut_receptor,
            'resolution_date' => '2017-02-14',
            'resolution_number' => '0',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->token
        )));

        if (is_wp_error($response)) {
            error_log("Error en la solicitud a la API (agrupar DTE): " . $response->get_error_message());
            return array(
                'success' => false,
                'response' => $response->get_error_message(),
                'request_body' => $data,
                'request_headers' => array(
                    'product' => 'ERP',
                    'rut_emitter' => $dte->rut_emisor,
                    'rut_receptor' => $dte->rut_receptor,
                    'resolution_date' => '2017-02-14',
                    'resolution_number' => '0',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (isset($data->status) && $data->status === 'ALL_SIGNED') {
            $this->actualizarEstadoDTE($dte->id, 'GROUPED');
            $this->actualizarFolioDTE($dte->id, $data->documents[0]->folio);
            return array(
                'success' => true,
                'response' => $data,
                'request_body' => $data,
                'request_headers' => array(
                    'product' => 'ERP',
                    'rut_emitter' => $dte->rut_emisor,
                    'rut_receptor' => $dte->rut_receptor,
                    'resolution_date' => '2017-02-14',
                    'resolution_number' => '0',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                )
            );
        } else {
            error_log("Error en la respuesta de la API (agrupar DTE): " . $body);
            return array(
                'success' => false,
                'response' => $body,
                'request_body' => $data,
                'request_headers' => array(
                    'product' => 'ERP',
                    'rut_emitter' => $dte->rut_emisor,
                    'rut_receptor' => $dte->rut_receptor,
                    'resolution_date' => '2017-02-14',
                    'resolution_number' => '0',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->token
                )
            );
        }
    }

    private function actualizarEstadoDTE($dte_id, $estado) {
        global $wpdb;
    
        $wpdb->update(
            "{$wpdb->prefix}sii_wc_dtes",
            array(
                'status' => $estado,
                'last_successful_status' => $estado
            ),
            array('id' => $dte_id)
        );
    }
    

    private function actualizarFolioDTE($dte_id, $folio) {
        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}sii_wc_dtes",
            array(
                'folio' => $folio
            ),
            array('id' => $dte_id)
        );
    }

    public function actualizarEnvioEstado($order_id, $estado, $mensaje) {
        global $wpdb;

        $dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

        if ($dte) {
            $wpdb->insert(
                "{$wpdb->prefix}sii_wc_shipments",
                array(
                    'dte_id' => $dte->id,
                    'fecha' => current_time('mysql'),
                    'estado' => $estado
                )
            );

            $this->actualizarEstadoDTE($dte->id, $estado);
        } else {
            error_log("Error: No se encontró el DTE para la orden $order_id.");
        }
    }

    public function obtenerFoliosPendientes() {
        $response = wp_remote_get('https://apibeta.riosoft.cl/dtemanager/v1/manager/sendings/status/summarized/pendings', array(
            'headers' => array(
                'product' => 'ERP',
                'Authorization' => 'Bearer ' . $this->token
            ),
            'timeout' => 15,
        ));
    
        if (is_wp_error($response)) {
            error_log("Error al obtener los folios pendientes: " . $response->get_error_message());
            return false;
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
    
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data)) {
            error_log("Error al procesar los folios pendientes: " . json_last_error_msg());
            return false;
        }
    
        return $data;
    }
    
    

    public function actualizarFoliosPendientes() {
        global $wpdb;
    
        $folios_pendientes = $this->obtenerFoliosPendientes();
    
        if (!$folios_pendientes) {
            return;
        }
    
        foreach ($folios_pendientes as $document) {
            if (isset($document['related'][0]['document_number']) && isset($document['folio'])) {
                $updated = $wpdb->update(
                    "{$wpdb->prefix}sii_wc_dtes",
                    array(
                        'folio' => $document['folio'],
                        'status' => 'grouped' // Actualiza el estado a 'grouped' si se obtiene el folio
                    ),
                    array('document_number' => $document['related'][0]['document_number'])
                );
    
                if ($updated === false) {
                    error_log("Error al actualizar el folio para el document_number: {$document['related'][0]['document_number']}");
                }
            }
        }
    
        // Establecer folio y estado a NULL y 'no_sent' respectivamente si no se encuentra coincidencia
        $wpdb->query("UPDATE {$wpdb->prefix}sii_wc_dtes SET folio = NULL, status = 'no_sent' WHERE folio IS NULL");
    }
    
    
    
    
}

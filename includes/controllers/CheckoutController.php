<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class CheckoutController {
    public static function init() {
        add_action('woocommerce_after_order_notes', array(__CLASS__, 'mostrarCamposCheckout'));
        add_action('woocommerce_checkout_process', array(__CLASS__, 'validarCamposCheckout'));
        add_action('woocommerce_checkout_update_order_meta', array(__CLASS__, 'guardarCamposCheckout'));
        add_action('woocommerce_admin_order_data_after_billing_address', array(__CLASS__, 'mostrarDatosFacturaAdmin'), 10, 1);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('woocommerce_thankyou', array(__CLASS__, 'mostrarDatosFacturaCheckout'), 10, 1);
        add_action('woocommerce_order_status_completed', array(__CLASS__, 'emitir_documento'));
    }

    public static function enqueue_scripts() {
        if (is_checkout()) {
            $custom_js = "
            jQuery(document).ready(function($) {
                $('#tipo_documento').change(function() {
                    var tipoDocumento = $(this).val();
                    if (tipoDocumento === 'factura_electronica') {
                        $('#campos_factura_electronica').show();
                        $('#billing_rut').prop('required', true);
                        $('#billing_razon_social').prop('required', true);
                        $('#billing_giro').prop('required', true);
                    } else {
                        $('#campos_factura_electronica').hide();
                        $('#billing_rut').prop('required', false);
                        $('#billing_razon_social').prop('required', false);
                        $('#billing_giro').prop('required', false);
                    }
                }).change();
            });
            ";
            wp_add_inline_script('jquery', $custom_js);
        }
    }

    public static function mostrarCamposCheckout($checkout) {
        $documentos_habilitados = get_option('sii_wc_documentos', array());

        if (!is_array($documentos_habilitados)) {
            $documentos_habilitados = array();
        }

        $opciones_documento = array();
        if (in_array('factura_electronica', $documentos_habilitados)) {
            $opciones_documento['factura_electronica'] = __('Factura Electrónica', 'woocommerce');
        }
        if (in_array('boleta_electronica', $documentos_habilitados)) {
            $opciones_documento['boleta_electronica'] = __('Boleta Electrónica', 'woocommerce');
        }

        if (!empty($opciones_documento)) {
            echo '<div id="documento_selection"><h3>' . __('Seleccione tipo de documento', 'woocommerce') . '</h3>';

            woocommerce_form_field('tipo_documento', array(
                'type' => 'select',
                'class' => array('form-row-wide'),
                'label' => __('Tipo de Documento'),
                'options' => array_merge(array('' => __('Seleccione un tipo de documento', 'woocommerce')), $opciones_documento),
                'required' => true,
            ), $checkout->get_value('tipo_documento'));

            echo '<div id="campos_factura_electronica">';
            woocommerce_form_field('billing_rut', array(
                'type' => 'text',
                'class' => array('form-row-wide'),
                'label' => __('RUT'),
                'required' => true,
            ), $checkout->get_value('billing_rut'));

            woocommerce_form_field('billing_razon_social', array(
                'type' => 'text',
                'class' => array('form-row-wide'),
                'label' => __('Razón Social'),
                'required' => true,
            ), $checkout->get_value('billing_razon_social'));

            woocommerce_form_field('billing_giro', array(
                'type' => 'text',
                'class' => array('form-row-wide'),
                'label' => __('Giro'),
                'required' => true,
            ), $checkout->get_value('billing_giro'));
            echo '</div></div>';
        }
    }

    public static function validarCamposCheckout() {
        if ($_POST['tipo_documento'] === 'factura_electronica') {
            if (!isset($_POST['billing_rut']) || empty($_POST['billing_rut'])) wc_add_notice(__('Por favor ingrese su RUT.', 'woocommerce'), 'error');
            if (!isset($_POST['billing_razon_social']) || empty($_POST['billing_razon_social'])) wc_add_notice(__('Por favor ingrese su Razón Social.', 'woocommerce'), 'error');
            if (!isset($_POST['billing_giro']) || empty($_POST['billing_giro'])) wc_add_notice(__('Por favor ingrese su Giro.', 'woocommerce'), 'error');
        }
    }

    public static function guardarCamposCheckout($order_id) {
        if (!empty($_POST['tipo_documento'])) {
            update_post_meta($order_id, 'tipo_documento', sanitize_text_field($_POST['tipo_documento']));
        }

        if (!empty($_POST['billing_rut'])) {
            update_post_meta($order_id, '_billing_rut', sanitize_text_field($_POST['billing_rut']));
        }

        if (!empty($_POST['billing_razon_social'])) {
            update_post_meta($order_id, '_billing_razon_social', sanitize_text_field($_POST['billing_razon_social']));
        }

        if (!empty($_POST['billing_giro'])) {
            update_post_meta($order_id, '_billing_giro', sanitize_text_field($_POST['billing_giro']));
        }
    }

    public static function mostrarDatosFacturaAdmin($order) {
        $tipo_documento = get_post_meta($order->get_id(), 'tipo_documento', true);
        $rut = get_post_meta($order->get_id(), '_billing_rut', true);
        $razon_social = get_post_meta($order->get_id(), '_billing_razon_social', true);
        $giro = get_post_meta($order->get_id(), '_billing_giro', true);

        if ($tipo_documento === 'factura_electronica') {
            echo '<p><strong>' . __('Tipo de Documento:') . '</strong> ' . __('Factura Electrónica') . '</p>';
            echo '<p><strong>' . __('RUT:') . '</strong> ' . esc_html($rut) . '</p>';
            echo '<p><strong>' . __('Razón Social:') . '</strong> ' . esc_html($razon_social) . '</p>';
            echo '<p><strong>' . __('Giro:') . '</strong> ' . esc_html($giro) . '</p>';
        } else if ($tipo_documento === 'boleta_electronica') {
            echo '<p><strong>' . __('Tipo de Documento:') . '</strong> ' . __('Boleta Electrónica') . '</p>';
        }
    }

    public static function emitir_documento($order_id) {
        $order = wc_get_order($order_id);
        $tipo_documento = get_post_meta($order_id, 'tipo_documento', true);
    
        if ($order && $tipo_documento) {
            $api_handler = new ApiHandler();
            $datos = array(
                'rut_receptor' => get_post_meta($order_id, '_billing_rut', true),
                'razon_social_receptor' => get_post_meta($order_id, '_billing_razon_social', true),
                'giro_receptor' => get_post_meta($order_id, '_billing_giro', true),
                'direccion_destino_receptor' => $order->get_billing_address_1(),
                'comuna_destino_receptor' => $order->get_billing_city(),
                'ciudad_destino_receptor' => $order->get_billing_city(),
                'monto_iva' => $order->get_total_tax(),
                'monto_neto' => $order->get_total() - $order->get_total_tax(),
                'monto_exento' => 0,
                'monto_total' => $order->get_total(),
                'tipo_dte' => ($tipo_documento === 'factura_electronica') ? 33 : 39, // Asumimos 33 para Factura Electrónica y 39 para Boleta Electrónica
                'detalle_productos' => array()
            );

            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $datos['detalle_productos'][] = array(
                    'nombre_item' => $product->get_name(),
                    'cantidad_item' => $item->get_quantity(),
                    'valor_unitario' => $item->get_total() / $item->get_quantity(),
                    'monto_item' => $item->get_total()
                );
            }

            $resultado = $api_handler->crearFacturaDTE($order, $datos);
    
            if ($resultado['success']) {
                $order->add_order_note(
                    __('Documento ' . $tipo_documento . ' generado correctamente.', 'woocommerce')
                );
                $order->add_order_note(
                    __('Respuesta de la API: ' . json_encode($resultado['response']), 'woocommerce')
                );
                $order->add_order_note(
                    __('Solicitud enviada: ' . json_encode($resultado['request_body'] ?? ''), 'woocommerce')
                );
                $order->add_order_note(
                    __('Encabezados de la solicitud: ' . json_encode($resultado['request_headers'] ?? ''), 'woocommerce')
                );

                // Agrupar DTE
                $agrupacion_resultado = $api_handler->agruparDTE($order_id);

                if ($agrupacion_resultado['success']) {
                    $order->add_order_note(
                        __('Documento ' . $tipo_documento . ' agrupado correctamente.', 'woocommerce')
                    );
                    $order->add_order_note(
                        __('Respuesta de la API (agrupar): ' . json_encode($agrupacion_resultado['response']), 'woocommerce')
                    );
                    $order->add_order_note(
                        __('Solicitud enviada (agrupar): ' . json_encode($agrupacion_resultado['request_body'] ?? ''), 'woocommerce')
                    );
                    $order->add_order_note(
                        __('Encabezados de la solicitud (agrupar): ' . json_encode($agrupacion_resultado['request_headers'] ?? ''), 'woocommerce')
                    );
                } else {
                    $order->add_order_note(
                        __('Error al agrupar documento ' . $tipo_documento . '.', 'woocommerce')
                    );
                    $order->add_order_note(
                        __('Respuesta de la API (agrupar): ' . json_encode($agrupacion_resultado['response']), 'woocommerce')
                    );
                    $order->add_order_note(
                        __('Solicitud enviada (agrupar): ' . json_encode($agrupacion_resultado['request_body'] ?? ''), 'woocommerce')
                    );
                    $order->add_order_note(
                        __('Encabezados de la solicitud (agrupar): ' . json_encode($agrupacion_resultado['request_headers'] ?? ''), 'woocommerce')
                    );
                }
            } else {
                $order->add_order_note(
                    __('Error al generar documento ' . $tipo_documento . '.', 'woocommerce')
                );
                $order->add_order_note(
                    __('Respuesta de la API: ' . json_encode($resultado['response']), 'woocommerce')
                );
                $order->add_order_note(
                    __('Solicitud enviada: ' . json_encode($resultado['request_body'] ?? ''), 'woocommerce')
                );
                $order->add_order_note(
                    __('Encabezados de la solicitud: ' . json_encode($resultado['request_headers'] ?? ''), 'woocommerce')
                );
            }
        }
    }

    public static function mostrarDatosFacturaCheckout($order_id) {
        $order = wc_get_order($order_id);
        $tipo_documento = get_post_meta($order_id, 'tipo_documento', true);
        $rut = get_post_meta($order_id, '_billing_rut', true);
        $razon_social = get_post_meta($order_id, '_billing_razon_social', true);
        $giro = get_post_meta($order_id, '_billing_giro', true);

        if ($tipo_documento) {
            echo '<h2>' . __('Información de Facturación') . '</h2>';
            echo '<p><strong>' . __('Tipo de Documento:') . '</strong> ' . ($tipo_documento === 'factura_electronica' ? 'Factura Electrónica' : 'Boleta Electrónica') . '</p>';
            if ($tipo_documento === 'factura_electronica') {
                echo '<p><strong>' . __('RUT:') . '</strong> ' . esc_html($rut) . '</p>';
                echo '<p><strong>' . __('Razón Social:') . '</strong> ' . esc_html($razon_social) . '</p>';
                echo '<p><strong>' . __('Giro:') . '</strong> ' . esc_html($giro) . '</p>';
            }
        }
    }
}

CheckoutController::init();

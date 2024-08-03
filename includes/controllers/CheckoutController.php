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
            $custom_js = '
            jQuery(document).ready(function($) {
                $("#tipo_documento").change(function() {
                    var tipoDocumento = $(this).val();
                    if (tipoDocumento === "factura_electronica") {
                        $("#campos_factura_electronica").show();
                        $("#billing_rut").prop("required", true);
                        $("#billing_razon_social").prop("required", true);
                        $("#billing_giro").prop("required", true);
                    } else {
                        $("#campos_factura_electronica").hide();
                        $("#billing_rut").prop("required", false);
                        $("#billing_razon_social").prop("required", false);
                        $("#billing_giro").prop("required", false);
                    }
                }).change();

                // Validación y formateo de RUT
                var Fn = {
                    validaRut: function(rutCompleto) {
                        if (!/^[0-9]+-[0-9kK]{1}$/.test(rutCompleto)) {
                            return false;
                        }
                        var tmp = rutCompleto.split("-");
                        var digv = tmp[1];
                        var rut = tmp[0];
                        if (digv == "K") digv = "k";
                        return (Fn.dv(rut) == digv);
                    },
                    dv: function(T) {
                        var M = 0,
                            S = 1;
                        for (; T; T = Math.floor(T / 10))
                            S = (S + T % 10 * (9 - M++ % 6)) % 11;
                        return S ? S - 1 : "k";
                    },
                    formateaRut: function(rut) {
                        var actual = rut.replace(/^0+/, "").replace(/\./g, "").replace(/-/g, "").toUpperCase();
                        if (actual.length <= 1) {
                            return actual;
                        }
                        var inicio = actual.slice(0, -1);
                        var dv = actual.slice(-1);
                        return inicio + "-" + dv;
                    }
                };

                // Formatear y validar RUT al escribir
                $(document).on("input", "#billing_rut", function() {
                    var rut = $(this).val().replace(/[^0-9kK]/g, "").toUpperCase();
                    $(this).val(Fn.formateaRut(rut));
                });

                $(document).on("blur", "#billing_rut", function() {
                    var rut = $(this).val().replace(/[^0-9kK]/g, "").toUpperCase();
                    $(this).val(Fn.formateaRut(rut));
                    if (!Fn.validaRut($(this).val())) {
                        
                        $(this).addClass("is-invalid");
                    } else {
                        $(this).removeClass("is-invalid");
                    }
                });

                // Limitar longitud del RUT
                $("#billing_rut").attr("maxlength", "10").attr("minlength", "9");
            });
            ';
            wp_add_inline_script('jquery', $custom_js);
        }
    }

    public static function mostrarCamposCheckout($checkout) {
        $opciones_documento = array(
            'factura_electronica' => __('Factura Electrónica', 'woocommerce'),
            'boleta_electronica' => __('Boleta Electrónica', 'woocommerce')
        );

        // Agregar el campo de tipo de documento
        woocommerce_form_field('tipo_documento', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => __('Tipo de Documento'),
            'options' => $opciones_documento,
            'default' => 'factura_electronica',
            'required' => true,
        ), $checkout->get_value('tipo_documento') ? $checkout->get_value('tipo_documento') : 'factura_electronica');

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

        echo '</div>';

        // Verificar y agregar otros campos necesarios
        self::verificarYOAgregarCampos($checkout);
    }

    public static function verificarYOAgregarCampos($checkout) {
        $campos_necesarios = array(
            'billing_first_name' => __('Nombre', 'woocommerce'),
            'billing_last_name' => __('Apellidos', 'woocommerce'),
            'billing_address_1' => __('Dirección', 'woocommerce'),
            'billing_city' => __('Ciudad', 'woocommerce'),
            'billing_postcode' => __('Código Postal', 'woocommerce'),
            'billing_email' => __('Correo Electrónico', 'woocommerce')
        );

        foreach ($campos_necesarios as $campo => $etiqueta) {
            if (!isset($checkout->checkout_fields['billing'][$campo])) {
                woocommerce_form_field($campo, array(
                    'type' => 'text',
                    'class' => array('form-row-wide'),
                    'label' => $etiqueta,
                    'required' => true,
                ), $checkout->get_value($campo));
            }
        }
    }

    public static function validarCamposCheckout() {
        if (empty($_POST['tipo_documento'])) {
            wc_add_notice(__('Por favor seleccione un tipo de documento.', 'woocommerce'), 'error');
        }
        if ($_POST['tipo_documento'] === 'factura_electronica') {
            if (empty($_POST['billing_rut']) || !preg_match('/^[0-9]+-[0-9kK]{1}$/', $_POST['billing_rut'])) {
                wc_add_notice(__('Por favor ingrese un RUT válido.', 'woocommerce'), 'error');
            }
            if (empty($_POST['billing_razon_social'])) {
                wc_add_notice(__('Por favor ingrese su Razón Social.', 'woocommerce'), 'error');
            }
            if (empty($_POST['billing_giro'])) {
                wc_add_notice(__('Por favor ingrese su Giro.', 'woocommerce'), 'error');
            }
        }
        if (empty($_POST['billing_email']) || !is_email($_POST['billing_email'])) {
            wc_add_notice(__('Por favor ingrese un correo electrónico válido.', 'woocommerce'), 'error');
        }
    }

    public static function guardarCamposCheckout($order_id) {
        if (!empty($_POST['tipo_documento'])) {
            update_post_meta($order_id, 'tipo_documento', strtoupper(sanitize_text_field($_POST['tipo_documento'])));
        }

        if (!empty($_POST['billing_rut'])) {
            update_post_meta($order_id, '_billing_rut', strtoupper(sanitize_text_field($_POST['billing_rut'])));
        }

        if (!empty($_POST['billing_razon_social'])) {
            update_post_meta($order_id, '_billing_razon_social', strtoupper(sanitize_text_field($_POST['billing_razon_social'])));
        }

        if (!empty($_POST['billing_giro'])) {
            update_post_meta($order_id, '_billing_giro', strtoupper(sanitize_text_field($_POST['billing_giro'])));
        }

        if (!empty($_POST['billing_email'])) {
            update_post_meta($order_id, '_billing_email', sanitize_email($_POST['billing_email']));
        }
    }

    public static function mostrarDatosFacturaAdmin($order) {
        $tipo_documento = get_post_meta($order->get_id(), 'tipo_documento', true);
        $rut = get_post_meta($order->get_id(), '_billing_rut', true);
        $razon_social = get_post_meta($order->get_id(), '_billing_razon_social', true);
        $giro = get_post_meta($order->get_id(), '_billing_giro', true);
        $email = get_post_meta($order->get_id(), '_billing_email', true);

        if ($tipo_documento === 'factura_electronica') {
            echo '<p><strong>' . __('Tipo de Documento:') . '</strong> ' . __('Factura Electrónica') . '</p>';
            echo '<p><strong>' . __('RUT:') . '</strong> ' . esc_html($rut) . '</p>';
            echo '<p><strong>' . __('Razón Social:') . '</strong> ' . esc_html($razon_social) . '</p>';
            echo '<p><strong>' . __('Giro:') . '</strong> ' . esc_html($giro) . '</p>';
        } else if ($tipo_documento === 'boleta_electronica') {
            echo '<p><strong>' . __('Tipo de Documento:') . '</strong> ' . __('Boleta Electrónica') . '</p>';
        }
        echo '<p><strong>' . __('Correo Electrónico:') . '</strong> ' . esc_html($email) . '</p>';
    }

    public static function emitir_documento($order_id) {
        $order = wc_get_order($order_id);
        $tipo_documento = get_post_meta($order_id, 'tipo_documento', true);
    
        if ($order && $tipo_documento === 'FACTURA_ELECTRONICA') {
            $api_handler = new ApiHandler();
            $datos = array(
                'rut_receptor' => strtoupper(get_post_meta($order_id, '_billing_rut', true)),
                'razon_social_receptor' => strtoupper(get_post_meta($order_id, '_billing_razon_social', true)),
                'giro_receptor' => strtoupper(get_post_meta($order_id, '_billing_giro', true)),
                'direccion_destino_receptor' => strtoupper($order->get_billing_address_1()),
                'comuna_destino_receptor' => strtoupper($order->get_billing_city()),
                'ciudad_destino_receptor' => strtoupper($order->get_billing_city()),
                'monto_iva' => $order->get_total_tax(),
                'monto_neto' => $order->get_total() - $order->get_total_tax(),
                'monto_exento' => 0,
                'monto_total' => $order->get_total(),
                'tipo_dte' => 33, // Tipo 33 para Factura Electrónica
                'detalle_productos' => array()
            );
    
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $datos['detalle_productos'][] = array(
                    'nombre_item' => strtoupper($product->get_name()),
                    'cantidad_item' => $item->get_quantity(),
                    'valor_unitario' => $item->get_total() / $item->get_quantity(),
                    'monto_item' => $item->get_total()
                );
            }
            
            $api_handler->crearFacturaDTE($order, $datos);
    
            
        }
    }
    

    public static function mostrarDatosFacturaCheckout($order_id) {
        $order = wc_get_order($order_id);
        $tipo_documento = get_post_meta($order_id, 'tipo_documento', true);
        $rut = get_post_meta($order_id, '_billing_rut', true);
        $razon_social = get_post_meta($order_id, '_billing_razon_social', true);
        $giro = get_post_meta($order_id, '_billing_giro', true);
        $email = get_post_meta($order_id, '_billing_email', true);

        if ($tipo_documento) {
            echo '<h2>' . __('Información de Facturación') . '</h2>';
            echo '<p><strong>' . __('Tipo de Documento:') . '</strong> ' . ($tipo_documento === 'factura_electronica' ? 'Factura Electrónica' : 'Boleta Electrónica') . '</p>';
            if ($tipo_documento === 'factura_electronica') {
                echo '<p><strong>' . __('RUT:') . '</strong> ' . esc_html($rut) . '</p>';
                echo '<p><strong>' . __('Razón Social:') . '</strong> ' . esc_html($razon_social) . '</p>';
                echo '<p><strong>' . __('Giro:') . '</strong> ' . esc_html($giro) . '</p>';
            }
            echo '<p><strong>' . __('Correo Electrónico:') . '</strong> ' . esc_html($email) . '</p>';
        }
    }
}

CheckoutController::init();
?>

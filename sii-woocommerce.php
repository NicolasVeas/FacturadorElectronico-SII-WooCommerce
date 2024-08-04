<?php
/**
 * Plugin Name: SII - Facturación Electrónica
 * Description: Plugin para integrar la facturación electrónica del SII con WooCommerce.
 * Version: 1.0.0
 * Author: NICOLAS VEAS
 * License: BSD
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

define('SII_WC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Incluir funciones comunes
require_once SII_WC_PLUGIN_PATH . 'includes/common-functions.php';

class SiiWooCommerce {
    public function __construct() {
        require_once SII_WC_PLUGIN_PATH . 'includes/Activator.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/AdminController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/CheckoutController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/CertificateController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/FoliosController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/EmisorController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/CredencialesController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/PedidosEmitidosController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/VerDteController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/TrazabilidadController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/ReenviarDteController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/CertificateModel.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/FoliosModel.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/EmisorModel.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/CredencialesModel.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/ApiHandler.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/PedidosEmitidosModel.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/TrazabilidadModel.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/ReenviarDteModel.php';
    }

    public function init() {
        register_activation_hook(__FILE__, array('Activator', 'activar'));
        AdminController::init();
        CheckoutController::init();
        PedidosEmitidosController::init();
        VerDteController::init();
        TrazabilidadController::init();
        ReenviarDteController::init();
    }
}

$sii_woocommerce = new SiiWooCommerce();
$sii_woocommerce->init();

function sii_wc_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('popper-js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js', array('jquery'), null, true);
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery', 'popper-js'), null, true);
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
    wp_enqueue_style('animate-css', 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js', array('jquery'), null, true);
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css');
    wp_enqueue_style('custom-css', plugin_dir_url(__FILE__) . 'assets/css/estilos-personalizados.css');

    if (is_admin()) {
        // Enqueue the common AJAX script first
        wp_enqueue_script('sii-wc-ajax', plugins_url('assets/js/sii-wc-ajax.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('sii-wc-ajax', 'ajax_object', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonces' => array(
                'get_pedidos_emitidos_nonce' => wp_create_nonce('get_pedidos_emitidos_nonce'),
                'view_dte_nonce' => wp_create_nonce('view_dte_nonce'),
                'view_trazabilidad_nonce' => wp_create_nonce('view_trazabilidad_nonce'),
                'send_reenviar_dte_nonce' => wp_create_nonce('send_reenviar_dte_nonce')
            )
        ));

        wp_enqueue_script('custom-rut-validator', plugins_url('assets/js/custom-rut-validator.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_script('certificate-scripts', plugins_url('assets/js/certificate-scripts.js', __FILE__), array('jquery', 'sweetalert2', 'custom-rut-validator'), null, true);
        wp_enqueue_script('folios-scripts', plugins_url('assets/js/folios-scripts.js', __FILE__), array('jquery', 'sweetalert2'), null, true);
        wp_enqueue_script('emisor-scripts', plugins_url('assets/js/emisor-scripts.js', __FILE__), array('jquery', 'sweetalert2', 'custom-rut-validator'), null, true);
        wp_enqueue_script('credenciales-scripts', plugins_url('assets/js/credenciales-scripts.js', __FILE__), array('jquery', 'sweetalert2'), null, true);
        wp_enqueue_script('pedidos-emitidos-scripts', plugins_url('assets/js/pedidos-emitidos.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_script('ver-dte-scripts', plugins_url('assets/js/ver-dte.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_script('trazabilidad-scripts', plugins_url('assets/js/trazabilidad.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_script('reenviar-dte-scripts', plugins_url('assets/js/reenviar-dte.js', __FILE__), array('jquery'), null, true);

        wp_enqueue_script('admin-scripts', plugins_url('assets/js/admin-scripts.js', __FILE__), array('jquery'), null, true);
    }
}
add_action('admin_enqueue_scripts', 'sii_wc_enqueue_scripts');

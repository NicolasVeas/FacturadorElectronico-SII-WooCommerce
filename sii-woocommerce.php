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

class SiiWooCommerce {
    public function __construct() {
        require_once SII_WC_PLUGIN_PATH . 'includes/Activator.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/AdminController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/DTEController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/EmitterController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/CheckoutController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/MainController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/ApiHandler.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/functions.php'; // Incluir funciones comunes
    }

    public function init() {
        register_activation_hook(__FILE__, array('Activator', 'activar'));
        AdminController::init();
        DTEController::init();
        EmitterController::init();
        CheckoutController::init();
    }
}

$sii_woocommerce = new SiiWooCommerce();
$sii_woocommerce->init();

// Incluir los scripts y estilos necesarios
function cargar_scripts_estilos() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('popper-js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js', array('jquery'), null, true);
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery', 'popper-js'), null, true);
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
    wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.js', array('jquery'), null, true);
    wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@10.16.6/dist/sweetalert2.min.css');
    wp_enqueue_script('sii-wc-script', plugins_url('../assets/js/main.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('sii-wc-script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
    wp_enqueue_style('custom-css', plugin_dir_url(__FILE__) . '../assets/css/estilos-personalizados.css');
}
add_action('admin_enqueue_scripts', 'cargar_scripts_estilos');

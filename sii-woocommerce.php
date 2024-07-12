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
        require_once SII_WC_PLUGIN_PATH . 'includes/controllers/CheckoutController.php';
        require_once SII_WC_PLUGIN_PATH . 'includes/models/ApiHandler.php';
    }

    public function init() {
        register_activation_hook(__FILE__, array('Activator', 'activar'));
        AdminController::init();
        CheckoutController::init();
    }
}

$sii_woocommerce = new SiiWooCommerce();
$sii_woocommerce->init();
?>

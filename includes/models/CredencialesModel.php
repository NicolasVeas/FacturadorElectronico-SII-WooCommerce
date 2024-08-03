<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once SII_WC_PLUGIN_PATH . 'includes/models/ApiHandler.php';

class CredencialesModel {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'sii_wc_credentials';
    }

    public function getCredenciales() {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM $this->table LIMIT 1");
    }

    public function saveCredenciales($email, $password) {
        global $wpdb;
        $credenciales = array(
            'email' => $email,
            'password' => $password,
        );

        if ($wpdb->get_var("SELECT COUNT(*) FROM $this->table") > 0) {
            return $wpdb->update($this->table, $credenciales, array('id' => 1));
        } else {
            return $wpdb->insert($this->table, $credenciales);
        }
    }

    public function verificarCredenciales($email, $password) {
        $api_handler = new ApiHandler();
        return $api_handler->verificarCredenciales($email, $password);
    }
}

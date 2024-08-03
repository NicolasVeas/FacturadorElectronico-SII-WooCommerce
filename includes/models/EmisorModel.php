<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class EmisorModel {
    public function getEmisor() {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");
    }

    public function saveEmisor($data) {
        global $wpdb;
        if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sii_wc_emitters") > 0) {
            return $wpdb->update("{$wpdb->prefix}sii_wc_emitters", $data, array('id' => 1));
        } else {
            return $wpdb->insert("{$wpdb->prefix}sii_wc_emitters", $data);
        }
    }
}
?>

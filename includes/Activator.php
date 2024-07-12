<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class Activator {
    public static function activar() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Desactivar restricciones de clave foránea
        $wpdb->query("SET FOREIGN_KEY_CHECKS=0");

        $sql = "
        CREATE TABLE {$wpdb->prefix}sii_wc_credentials (
            id INT NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sii_wc_emitters (
            id INT NOT NULL AUTO_INCREMENT,
            rut VARCHAR(10) NOT NULL,
            razon_social VARCHAR(100) NOT NULL,
            actecos TEXT NOT NULL,
            direccion_origen VARCHAR(60) NOT NULL,
            comuna_origen VARCHAR(20) NOT NULL,
            giro VARCHAR(80) NOT NULL,
            sucursal VARCHAR(20) DEFAULT NULL,
            ciudad_origen VARCHAR(20) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sii_wc_receivers (
            id INT NOT NULL AUTO_INCREMENT,
            rut VARCHAR(10) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            comuna VARCHAR(255) NOT NULL,
            ciudad VARCHAR(255) NOT NULL,
            giro VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sii_wc_dtes (
            id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            document_type INT NOT NULL,
            document_number INT NOT NULL,
            document_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            rut_emisor VARCHAR(10) NOT NULL,
            rut_receptor VARCHAR(10) NOT NULL,
            status VARCHAR(255) NOT NULL,
            last_successful_status VARCHAR(255) DEFAULT NULL,
            folio INT DEFAULT NULL,
            created TIMESTAMP NULL,
            grouped TIMESTAMP NULL,
            sent TIMESTAMP NULL,
            accepted TIMESTAMP NULL,
            repaired TIMESTAMP NULL,
            rejected TIMESTAMP NULL,
            not_sent TIMESTAMP NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE {$wpdb->prefix}sii_wc_shipments (
            id INT NOT NULL AUTO_INCREMENT,
            dte_id INT NOT NULL,
            fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            estado VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (dte_id) REFERENCES {$wpdb->prefix}sii_wc_dtes(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) $charset_collate;
        ";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Reactivar restricciones de clave foránea
        $wpdb->query("SET FOREIGN_KEY_CHECKS=1");

        // Verificar que las tablas estén creadas
        $table_names = [
            "{$wpdb->prefix}sii_wc_credentials",
            "{$wpdb->prefix}sii_wc_emitters",
            "{$wpdb->prefix}sii_wc_receivers",
            "{$wpdb->prefix}sii_wc_dtes",
            "{$wpdb->prefix}sii_wc_shipments"
        ];

        foreach ($table_names as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                error_log("Éxito: la tabla $table fue creada correctamente.");
            } else {
                error_log("Error: la tabla $table no pudo ser creada.");
            }
        }
    }
}
?>

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
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sii_wc_credentials (
            id INT NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sii_wc_emitters (
            id INT NOT NULL AUTO_INCREMENT,
            rut VARCHAR(10) NOT NULL,
            razon_social VARCHAR(100) NOT NULL,
            actecos TEXT NOT NULL,
            direccion_origen VARCHAR(60) NOT NULL,
            comuna_origen VARCHAR(20) NOT NULL,
            giro VARCHAR(80) NOT NULL,
            sucursal VARCHAR(20) DEFAULT NULL,
            ciudad_origen VARCHAR(20) DEFAULT NULL,
            correo VARCHAR(100) DEFAULT NULL,  // Nueva columna para el correo del emisor
            PRIMARY KEY (id)
        ) $charset_collate;


        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sii_wc_receivers (
            id INT NOT NULL AUTO_INCREMENT,
            rut VARCHAR(10) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            comuna VARCHAR(255) NOT NULL,
            ciudad VARCHAR(255) NOT NULL,
            giro VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sii_wc_dtes (
            id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            document_type INT NOT NULL,
            document_number INT NOT NULL,
            document_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            rut_emisor VARCHAR(10) NOT NULL,
            rut_receptor VARCHAR(10) NOT NULL,
            status VARCHAR(255) NOT NULL,
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
            "{$wpdb->prefix}sii_wc_dtes"
        ];

        foreach ($table_names as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                error_log("Éxito: la tabla $table fue creada correctamente.");
            } else {
                error_log("Error: la tabla $table no pudo ser creada.");
            }
        }

        // Programar el cron job
        self::schedule_cron_jobs();
    }

    public static function desactivar() {
        // Desprogramar el cron job
        self::unschedule_cron_jobs();
    }

    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('sii_wc_dtes_cron_job')) {
            wp_schedule_event(time(), 'hourly', 'sii_wc_dtes_cron_job');
        }
    }

    private static function unschedule_cron_jobs() {
        $timestamp = wp_next_scheduled('sii_wc_dtes_cron_job');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'sii_wc_dtes_cron_job');
        }
    }

    public static function sii_wc_dtes_cron_job() {
        $api_handler = new ApiHandler();
        $api_handler->manejarDTESinFolio();

        // Obtener el estado de envíos pendientes y actualizar
        global $wpdb;
        $envios_pendientes = $wpdb->get_results("SELECT track_id FROM {$wpdb->prefix}sii_wc_dtes WHERE status = 'SENT'");
        foreach ($envios_pendientes as $envio) {
            $api_handler->actualizarEstadoEnvioAlSII($envio->track_id);
        }
    }
}

// Hooks de activación y desactivación
register_activation_hook(__FILE__, array('Activator', 'activar'));
register_deactivation_hook(__FILE__, array('Activator', 'desactivar'));

// Hook para el cron job
add_action('sii_wc_dtes_cron_job', array('Activator', 'sii_wc_dtes_cron_job'));
?>

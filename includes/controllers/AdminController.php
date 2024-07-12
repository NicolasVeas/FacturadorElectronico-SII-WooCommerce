<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class AdminController {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'adminMenu'));
        add_action('admin_init', array(__CLASS__, 'settings'));
    }

    public static function adminMenu() {
        add_submenu_page(
            'woocommerce',
            'Plugin - Facturador Electrónico',
            'Plugin - Facturador Electrónico',
            'manage_options',
            'sii-woocommerce',
            array(__CLASS__, 'mostrarPaginaPrincipal')
        );
    }

    public static function settings() {
        register_setting('sii_wc_settings_group', 'sii_wc_settings');
    }

    public static function mostrarPaginaPrincipal() {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'documentos';
        ?>
        <div class="wrap">
            <h1>Plugin - Facturador Electrónico</h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=sii-woocommerce&tab=emisor" class="nav-tab <?php echo $tab == 'emisor' ? 'nav-tab-active' : ''; ?>">Datos del Emisor</a>
                <a href="?page=sii-woocommerce&tab=documentos" class="nav-tab <?php echo $tab == 'documentos' ? 'nav-tab-active' : ''; ?>">Documentos</a>
                <a href="?page=sii-woocommerce&tab=condiciones" class="nav-tab <?php echo $tab == 'condiciones' ? 'nav-tab-active' : ''; ?>">Condiciones para Emitir</a>
                <a href="?page=sii-woocommerce&tab=credenciales" class="nav-tab <?php echo $tab == 'credenciales' ? 'nav-tab-active' : ''; ?>">Credenciales</a>
                <a href="?page=sii-woocommerce&tab=folios" class="nav-tab <?php echo $tab == 'folios' ? 'nav-tab-active' : ''; ?>">Folios</a>
                <a href="?page=sii-woocommerce&tab=pedidos" class="nav-tab <?php echo $tab == 'pedidos' ? 'nav-tab-active' : ''; ?>">Pedidos</a>
            </nav>
            <?php
            switch ($tab) {
                case 'emisor':
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/emisor-settings.php';
                    break;
                case 'documentos':
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/documentos.php';
                    break;
                case 'condiciones':
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/condiciones.php';
                    break;
                case 'credenciales':
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/credenciales.php';
                    break;
                case 'folios':
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/folios.php';
                    break;
                case 'pedidos':
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/pedidos.php';
                    break;
                default:
                    include SII_WC_PLUGIN_PATH . 'includes/views/admin/documentos.php';
                    break;
            }
            ?>
        </div>
        <?php
    }
}

AdminController::init();
?>
    
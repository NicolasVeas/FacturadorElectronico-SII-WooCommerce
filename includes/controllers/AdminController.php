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
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'pedidos-emitidos';
        ?>
        <div class="wrap">
            <h1>Plugin - Facturador Electrónico</h1>
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'pedidos-emitidos' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=pedidos-emitidos">Pedidos Emitidos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'pedidos-no-emitidos' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=pedidos-no-emitidos">Pedidos No Emitidos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'emisor-settings' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=emisor-settings">Emisor</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'credenciales' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=credenciales">Credenciales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'folios' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=folios">Folios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'documentos' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=documentos">Documentos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $tab == 'condiciones' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=condiciones">Condiciones</a>
                </li>
            </ul>
            <div class="tab-content mt-4">
                <?php
                switch ($tab) {
                    case 'pedidos-emitidos':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/pedidos-emitidos.php';
                        break;
                    case 'pedidos-no-emitidos':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/pedidos-no-emitidos.php';
                        break;
                    case 'emisor-settings':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/emisor-settings.php';
                        break;
                    case 'credenciales':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/credenciales.php';
                        break;
                    case 'folios':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/folios.php';
                        break;
                    case 'documentos':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/documentos.php';
                        break;
                    case 'condiciones':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/condiciones.php';
                        break;
                    default:
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/pedidos-emitidos.php';
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
}

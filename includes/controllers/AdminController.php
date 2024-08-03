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
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'credenciales';
        ?>
        <div class="wrap">
            <h1>Plugin - Facturador Electrónico</h1>
            <ul class="nav nav-tabs sii-woocommerce-menu">
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'credenciales' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=credenciales">
                        <i class="fas fa-user"></i> Credenciales
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'emisor-settings' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=emisor-settings">
                        <i class="fas fa-building"></i> Emisor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'pedidos-emitidos' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=pedidos-emitidos">
                        <i class="fas fa-check"></i> Pedidos Emitidos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'pedidos-no-emitidos' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=pedidos-no-emitidos">
                        <i class="fas fa-times"></i> Pedidos No Emitidos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'folios' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=folios">
                        <i class="fas fa-list"></i> Folios
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'documentos' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=documentos">
                        <i class="fas fa-file"></i> Documentos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link tab-link <?php echo $tab == 'certificado' ? 'active' : ''; ?>" href="?page=sii-woocommerce&tab=certificado">
                        <i class="fas fa-certificate"></i> Certificado
                    </a>
                </li>
            </ul>
            <div class="tab-content mt-4" id="tab-content">
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
                    case 'certificado':
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/certificado.php';
                        break;
                    default:
                        include SII_WC_PLUGIN_PATH . 'includes/views/admin/credenciales.php';
                        break;
                }
                ?>
            </div>
        </div>

        <!-- Modal de carga fuera del #tab-content -->
        <div id="loadingModal" style="display: none;">
            <div class="loader"></div>
        </div>

        <style>
        /* General styles for the plugin page */
        .wrap {
            font-family: sans-serif; /* Modernize font */
        }

        .sii-woocommerce-menu {
            list-style: none;
            padding: 0;
            display: flex; /* Use flexbox for even spacing */
            gap: 10px; /* Adjust spacing between tabs */
        }

        .sii-woocommerce-menu .nav-link {
            padding: 8px 12px;
            border-radius: 5px; 
            text-decoration: none;
            color: #333;
            background-color: #f5f5f5;
        }

        .sii-woocommerce-menu .nav-link:hover,
        .sii-woocommerce-menu .nav-link.active {
            background-color: #007bff;
            color: white;
        }

        /* Spinner styles */
        #loadingModal {
            display: flex; /* Center the modal */
            align-items: center;
            justify-content: center;
            position: fixed; 
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3); /* Darker background */
            z-index: 9999; /* Ensure it's on top */
        }

        .loader {
            width: 100px; /* Much larger width */
            aspect-ratio: 1;
            display: flex;
            animation: l10-0 2s infinite steps(1);
        }
        .loader::before,
        .loader::after {
            content: "";
            flex: 1;
            animation: 
                l10-1 1s infinite linear alternate,
                l10-2 2s infinite steps(1) -.5s;
        }
        .loader::after {
            --s:-1,-1;
        }
        @keyframes l10-0 {
            0%  {transform: scaleX(1)  rotate(0deg)}
            50% {transform: scaleX(-1) rotate(-90deg)}
        }
        @keyframes l10-1 {
            0%,
            5%   {transform:scale(var(--s,1)) translate(0px)   perspective(150px) rotateY(0deg) }
            33%  {transform:scale(var(--s,1)) translate(-10px) perspective(150px) rotateX(0deg) }
            66%  {transform:scale(var(--s,1)) translate(-10px) perspective(150px) rotateX(-180deg)}
            95%,
            100% {transform:scale(var(--s,1)) translate(0px)   perspective(150px) rotateX(-180deg)}
        }
        @keyframes l10-2 {
            0%  {background:#007bff;border-radius: 0} /* Bootstrap blue */
            50% {background:#f5f5f5;border-radius: 100px 0 0 100px} /* Bootstrap blue */
        }
        </style>
        <?php
    }
}

AdminController::init();

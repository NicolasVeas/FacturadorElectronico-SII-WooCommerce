<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class MainController {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'adminMenu'));
    }

    public static function adminMenu() {
        AdminController::init();
        DTEController::init();
        EmitterController::init();
    }
}

MainController::init();

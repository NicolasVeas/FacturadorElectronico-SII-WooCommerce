<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

if (!function_exists('render_pagination')) {
    function render_pagination($total_pages, $current_page, $section) {
        ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="#" data-page="<?php echo $i; ?>" data-section="<?php echo $section; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php
    }
}

add_action('wp_ajax_cargar_pagina', 'cargar_pagina');
add_action('wp_ajax_nopriv_cargar_pagina', 'cargar_pagina');

function cargar_pagina() {
    if (!isset($_POST['page']) || !isset($_POST['section'])) {
        wp_send_json_error('Parámetros faltantes.');
    }

    $page = intval($_POST['page']);
    $section = sanitize_text_field($_POST['section']);

    ob_start();
    if ($section === 'emitidos') {
        include plugin_dir_path(__FILE__) . 'views/admin/pedidos-emitidos.php';
    } else if ($section === 'no_emitidos') {
        include plugin_dir_path(__FILE__) . 'views/admin/pedidos-no-emitidos.php';
    } else {
        wp_send_json_error('Sección no válida.');
    }
    $data = ob_get_clean();

    wp_send_json_success($data);
}

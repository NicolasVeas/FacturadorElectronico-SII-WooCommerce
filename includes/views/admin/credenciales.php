<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text_field($_POST['sii_wc_email']);
    $password = sanitize_text_field($_POST['sii_wc_password']);

    // Verificar las credenciales ingresadas
    $api_handler = new ApiHandler();
    $response = $api_handler->verificarCredenciales($email, $password);

    if ($response['status'] === 200 && isset($response['access_token'])) {
        $credenciales = array(
            'email' => $email,
            'password' => $password,
        );

        // Comprobar si ya existen credenciales y actualizar o insertar en consecuencia
        if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sii_wc_credentials") > 0) {
            $wpdb->update("{$wpdb->prefix}sii_wc_credentials", $credenciales, array('id' => 1));
        } else {
            $wpdb->insert("{$wpdb->prefix}sii_wc_credentials", $credenciales);
        }

        $message = 'success';
    } else {
        $message = 'error';
    }

    // Redireccionar con el parámetro del resultado
    wp_redirect(add_query_arg('message', $message, wp_get_referer()));
    exit;
}

// Obtener el mensaje de la URL
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

$credenciales = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");
?>

<div class="container mt-5" style="max-width: 400px;">
    <h1 class="text-center mb-4">Configuración de Credenciales</h1>
    <p class="text-center">Estas credenciales son para utilizar el plugin y se agregan una única vez. También permiten utilizar el endpoint de API para facturación electrónica de Riosoft SpA.</p>
    <div id="message-container">
        <?php if ($message === 'success'): ?>
            <div class="alert alert-success text-center" role="alert">
                ¡Éxito! Las credenciales se han guardado y verificado correctamente.
            </div>
        <?php elseif ($message === 'error'): ?>
            <div class="alert alert-danger text-center" role="alert">
                Error al verificar las credenciales. Por favor, verifica los datos e inténtalo nuevamente.
            </div>
        <?php endif; ?>
    </div>
    <form id="credenciales-form" method="post" action="">
        <div class="form-group">
            <label for="sii_wc_email">Email <span style="color: red;">*</span></label>
            <input type="email" class="form-control" id="sii_wc_email" name="sii_wc_email" value="<?php echo isset($credenciales->email) ? esc_attr($credenciales->email) : ''; ?>" required />
        </div>
        <div class="form-group">
            <label for="sii_wc_password">Password <span style="color: red;">*</span></label>
            <input type="password" class="form-control" id="sii_wc_password" name="sii_wc_password" value="<?php echo isset($credenciales->password) ? esc_attr($credenciales->password) : ''; ?>" required />
        </div>
        <div class="form-group text-center">
            <button type="button" id="guardar-credenciales-btn" class="btn btn-primary btn-block">Guardar Credenciales</button>
        </div>
    </form>
</div>

<!-- Spinner Modal -->
<div class="modal fade" id="spinnerModal" tabindex="-1" role="dialog" aria-labelledby="spinnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Comprobando...</span>
                </div>
                <p>Comprobando Credenciales... Por favor, espera un momento.</p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#guardar-credenciales-btn').on('click', function() {
        // Mostrar el spinner modal
        $('#spinnerModal').modal('show');

        // Enviar el formulario
        $('#credenciales-form').submit();
    });
});
</script>

<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_text_field($_POST['sii_wc_email']);
    $password = sanitize_text_field($_POST['sii_wc_password']);
    $credenciales = array(
        'email' => $email,
        'password' => $password,
    );

    $wpdb->replace(
        $wpdb->prefix . 'sii_wc_credentials',
        $credenciales
    );

    $token = (new ApiHandler())->obtenerToken();

    if ($token) {
        echo '<div class="updated"><p>Credenciales guardadas y verificadas correctamente.</p></div>';
    } else {
        echo '<div class="error"><p>Error al verificar las credenciales. Por favor, verifique los datos e inténtelo nuevamente.</p></div>';
    }
}

$credenciales = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_credentials LIMIT 1");
?>

<div class="wrap">
    <h1>Configuración de Credenciales</h1>
    <form method="post" action="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Email</th>
                <td><input type="email" name="sii_wc_email" value="<?php echo isset($credenciales->email) ? esc_attr($credenciales->email) : ''; ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Password</th>
                <td><input type="password" name="sii_wc_password" value="<?php echo isset($credenciales->password) ? esc_attr($credenciales->password) : ''; ?>" required /></td>
            </tr>
        </table>
        <?php submit_button('Guardar Credenciales'); ?>
    </form>
</div>

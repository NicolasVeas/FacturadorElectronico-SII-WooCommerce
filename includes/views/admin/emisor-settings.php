<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;

$emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");
$acteco = isset($emisor->actecos) ? json_decode($emisor->actecos)[0]->acteco : '';
?>
<div class="wrap">
    <h1>Configuración del Emisor</h1>
    <form method="post" action="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">RUT <span style="color: red;">*</span></th>
                <td><input type="text" name="sii_wc_rut" value="<?php echo isset($emisor->rut) ? esc_attr($emisor->rut) : ''; ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Razón Social <span style="color: red;">*</span></th>
                <td><input type="text" name="sii_wc_razon_social" value="<?php echo isset($emisor->razon_social) ? esc_attr($emisor->razon_social) : ''; ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Código de Actividad Económica (ACTECO) <span style="color: red;">*</span> 
                    <button type="button" class="button-info" onclick="mostrarInfoActeco()">Información</button>
                </th>
                <td>
                    <input type="text" name="sii_wc_acteco" value="<?php echo esc_attr($acteco); ?>" required />
                    <p><i>ACTECO: Se acepta un máximo de 4 códigos de actividad económica del emisor del DTE. Se puede incluir sólo el código que corresponde a la transacción.</i></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Dirección Origen <span style="color: red;">*</span></th>
                <td><input type="text" name="sii_wc_direccion_origen" value="<?php echo isset($emisor->direccion_origen) ? esc_attr($emisor->direccion_origen) : ''; ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Comuna Origen <span style="color: red;">*</span></th>
                <td><input type="text" name="sii_wc_comuna_origen" value="<?php echo isset($emisor->comuna_origen) ? esc_attr($emisor->comuna_origen) : ''; ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Giro <span style="color: red;">*</span></th>
                <td><input type="text" name="sii_wc_giro" value="<?php echo isset($emisor->giro) ? esc_attr($emisor->giro) : ''; ?>" required /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Sucursal (Opcional)</th>
                <td><input type="text" name="sii_wc_sucursal" value="<?php echo isset($emisor->sucursal) ? esc_attr($emisor->sucursal) : ''; ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">Ciudad Origen (Opcional)</th>
                <td><input type="text" name="sii_wc_ciudad_origen" value="<?php echo isset($emisor->ciudad_origen) ? esc_attr($emisor->ciudad_origen) : ''; ?>" /></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="sii_wc_guardar_emisor" class="button-primary" value="Guardar Datos del Emisor" />
        </p>
    </form>
</div>
<script>
function mostrarInfoActeco() {
    alert('ACTECO: Se acepta un máximo de 4 Códigos de actividad económica del emisor del DTE. Se puede incluir sólo el código que corresponde a la transacción.\n\nEnlaces útiles:\n- Codigos de actividad económica: https://www.sii.cl/ayudas/ayudas_por_servicios/1956-codigos-1959.html\n- Consultar situación tributaria de terceros: https://zeus.sii.cl/cvc/stc/stc.html');
}
</script>

<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update_option('sii_wc_documentos', isset($_POST['sii_wc_documentos']) ? $_POST['sii_wc_documentos'] : array());
}

$documentos_habilitados = get_option('sii_wc_documentos', array());

if (!is_array($documentos_habilitados)) {
    $documentos_habilitados = array();
}
?>

<div class="wrap">
    <h1>Configuración de Documentos</h1>
    <form method="post" action="">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Documentos Tributarios Habilitados</th>
                <td>
                    <fieldset>
                        <label for="factura_electronica">
                            <input type="checkbox" name="sii_wc_documentos[]" value="factura_electronica" <?php checked(in_array('factura_electronica', $documentos_habilitados)); ?> />
                            Factura Electrónica
                        </label><br />
                        <label for="boleta_electronica">
                            <input type="checkbox" name="sii_wc_documentos[]" value="boleta_electronica" <?php checked(in_array('boleta_electronica', $documentos_habilitados)); ?> />
                            Boleta Electrónica
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>

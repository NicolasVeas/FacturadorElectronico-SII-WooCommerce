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

<div class="container mt-5">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title">Configuración de Documentos Tributarios Electrónicos</h2>
            <p class="card-text">Activa o desactiva los tipos de documentos tributarios electrónicos (DTE) que tus clientes podrán seleccionar al finalizar la compra.</p>
            <form id="document-form" method="post" action="">
                <fieldset class="border p-4 rounded">
                    <legend class="w-auto">Documentos Tributarios Habilitados</legend>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="factura_electronica" name="sii_wc_documentos[]" value="factura_electronica" <?php checked(in_array('factura_electronica', $documentos_habilitados)); ?>>
                        <label class="form-check-label" for="factura_electronica">Factura Electrónica</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="boleta_electronica" name="sii_wc_documentos[]" value="boleta_electronica" <?php checked(in_array('boleta_electronica', $documentos_habilitados)); ?>>
                        <label class="form-check-label" for="boleta_electronica">Boleta Electrónica</label>
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Spinner Modal -->
<div class="modal fade" id="spinnerModal" tabindex="-1" role="dialog" aria-labelledby="spinnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <p>Guardando cambios... Por favor, espera un momento.</p>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        $('#document-form').on('submit', function() {
            // Mostrar el spinner modal
            $('#spinnerModal').modal('show');
        });

        // Mostrar mensaje de éxito si los cambios se guardaron correctamente
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>
            Swal.fire({
                title: 'Cambios Guardados',
                text: 'La configuración de documentos se ha guardado correctamente.',
                icon: 'success',
                confirmButtonText: 'Cerrar'
            }).then(function() {
                $('#spinnerModal').modal('hide');
            });
        <?php endif; ?>
    });
</script>

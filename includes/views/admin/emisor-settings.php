<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once SII_WC_PLUGIN_PATH . 'includes/controllers/EmisorController.php';

// Instanciar el controlador
$emisorController = new EmisorController();
$emisorController->handle_request(); // Manejar la solicitud (si existe)

// Obtener datos del emisor a través del controlador
$emisor = $emisorController->getEmisorData();
$acteco = isset($emisor->actecos) ? json_decode($emisor->actecos)[0]->acteco : '';

// Obtener el mensaje de la URL
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>

<div class="container d-flex justify-content-center" style="max-width: 1100px;">
    <div class="border p-3 w-100 shadow-sm" style="background-color: #f8f9fa;">
        <h1 class="text-center mb-4">Configuración del Emisor</h1>
        <p class="lead text-center small text-muted mb-4">Estos datos se utilizarán para ser el emisor en los DTE que generes. En caso de cambiar el emisor contribuyente, debes actualizar la información aquí.</p>

        <h3 class="h5 mb-3">Datos del Emisor</h3>

        <form id="emisor-form" method="post" action="" class="text-small" data-message="<?php echo $message; ?>">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="sii_wc_rut" class="text-right">RUT <span style="color: red;">*</span></label>
                    <input type="text" class="form-control form-control-lg rut-input" id="sii_wc_rut" name="sii_wc_rut" value="<?php echo isset($emisor->rut) ? esc_attr($emisor->rut) : ''; ?>" maxlength="10" minlength="9" required data-toggle="tooltip" title="Formato: sin puntos ni guion" />
                </div>
                <div class="form-group col-md-4">
                    <label for="sii_wc_razon_social" class="text-right">Razón Social <span style="color: red;">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_razon_social" name="sii_wc_razon_social" value="<?php echo isset($emisor->razon_social) ? esc_attr($emisor->razon_social) : ''; ?>" required />
                </div>
                <div class="form-group col-md-4">
                    <label for="sii_wc_direccion_origen" class="text-right">Dirección Origen <span style="color: red;">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_direccion_origen" name="sii_wc_direccion_origen" value="<?php echo isset($emisor->direccion_origen) ? esc_attr($emisor->direccion_origen) : ''; ?>" required />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="sii_wc_comuna_origen" class="text-right">Comuna Origen <span style="color: red;">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_comuna_origen" name="sii_wc_comuna_origen" value="<?php echo isset($emisor->comuna_origen) ? esc_attr($emisor->comuna_origen) : ''; ?>" required />
                </div>
                <div class="form-group col-md-4">
                    <label for="sii_wc_giro" class="text-right">Giro <span style="color: red;">*</span></label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_giro" name="sii_wc_giro" value="<?php echo isset($emisor->giro) ? esc_attr($emisor->giro) : ''; ?>" required />
                </div>
                <div class="form-group col-md-4">
                    <label for="sii_wc_sucursal" class="text-right">Sucursal (Opcional)</label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_sucursal" name="sii_wc_sucursal" value="<?php echo isset($emisor->sucursal) ? esc_attr($emisor->sucursal) : ''; ?>" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="sii_wc_ciudad_origen" class="text-right">Ciudad Origen (Opcional)</label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_ciudad_origen" name="sii_wc_ciudad_origen" value="<?php echo isset($emisor->ciudad_origen) ? esc_attr($emisor->ciudad_origen) : ''; ?>" />
                </div>
                <div class="form-group col-md-4">
                    <label for="sii_wc_correo" class="text-right">Correo <span style="color: red;">*</span></label>
                    <input type="email" class="form-control form-control-lg" id="sii_wc_correo" name="sii_wc_correo" value="<?php echo isset($emisor->correo) ? esc_attr($emisor->correo) : ''; ?>" required />
                </div>
                <div class="form-group col-md-4">
                    <label for="sii_wc_acteco" class="text-right">
                        ACTECO (Código actividad económica) <span style="color: red;">*</span>
                        <i class="fas fa-info-circle" data-toggle="tooltip" title="ACTECO: Se acepta un máximo de 4 códigos de actividad económica del emisor del DTE. Se puede incluir sólo el código que corresponde a la transacción."></i>
                    </label>
                    <input type="text" class="form-control form-control-lg" id="sii_wc_acteco" name="sii_wc_acteco" value="<?php echo esc_attr($acteco); ?>" required pattern="^\d{1,6}$" title="Debe ser un número de hasta 6 dígitos" maxlength="6" />
                </div>
            </div>
            <div class="form-group text-right mt-4">
                <button type="button" id="guardar-btn" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Guardar Datos del Emisor</button>
                <input type="hidden" name="sii_wc_guardar_emisor" value="1" />
            </div>
        </form>
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
                <p>Guardando datos... Por favor, espera un momento.</p>
            </div>
        </div>
    </div>
</div>



<!-- Font Awesome for the icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

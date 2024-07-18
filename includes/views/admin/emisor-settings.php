<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;

// Función para validar el RUT
function valida_rut($rutCompleto) {
    if (!preg_match('/^[0-9]+-[0-9kK]{1}$/', $rutCompleto)) {
        return false;
    }
    $tmp = explode('-', $rutCompleto);
    $digv = strtolower($tmp[1]);
    $rut = intval($tmp[0]);
    $m = 0;
    $s = 1;
    while ($rut) {
        $s = ($s + $rut % 10 * (9 - $m++ % 6)) % 11;
        $rut = intval($rut / 10);
    }
    return $s ? $s - 1 == $digv : 'k' == $digv;
}

// Guardar datos del emisor
$message = '';
if (isset($_POST['sii_wc_guardar_emisor'])) {
    $rut = sanitize_text_field($_POST['sii_wc_rut']);
    $razon_social = sanitize_text_field($_POST['sii_wc_razon_social']);
    $acteco = sanitize_text_field($_POST['sii_wc_acteco']);
    $direccion_origen = sanitize_text_field($_POST['sii_wc_direccion_origen']);
    $comuna_origen = sanitize_text_field($_POST['sii_wc_comuna_origen']);
    $giro = sanitize_text_field($_POST['sii_wc_giro']);
    $sucursal = sanitize_text_field($_POST['sii_wc_sucursal']);
    $ciudad_origen = sanitize_text_field($_POST['sii_wc_ciudad_origen']);

    // Validar RUT
    if (!valida_rut($rut)) {
        $message = 'invalid_rut';
    } else {
        $data = array(
            'rut' => $rut,
            'razon_social' => $razon_social,
            'actecos' => json_encode(array(array('acteco' => $acteco))),
            'direccion_origen' => $direccion_origen,
            'comuna_origen' => $comuna_origen,
            'giro' => $giro,
            'sucursal' => $sucursal,
            'ciudad_origen' => $ciudad_origen
        );
        if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sii_wc_emitters") > 0) {
            $result = $wpdb->update("{$wpdb->prefix}sii_wc_emitters", $data, array('id' => 1));
        } else {
            $result = $wpdb->insert("{$wpdb->prefix}sii_wc_emitters", $data);
        }

        if ($result !== false) {
            $message = 'success';
        } else {
            $message = 'error';
        }
    }

    // Redireccionar con el parámetro del resultado
    wp_redirect(add_query_arg('message', $message, wp_get_referer()));
    exit;
}

// Obtener el mensaje de la URL
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

$emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");
$acteco = isset($emisor->actecos) ? json_decode($emisor->actecos)[0]->acteco : '';
?>

<div class="container mt-5" style="max-width: 600px;">
    <div class="card">
        <div class="card-body">
            <h1 class="text-center mb-4">Configuración del Emisor</h1>
            <p class="lead text-center">Estos datos se utilizarán para ser el emisor en los DTE que generes. En caso de cambiar el emisor contribuyente, debes actualizar la información aquí.</p>
            <?php if ($message === 'success'): ?>
                <div class="alert alert-success" role="alert">
                    ¡Éxito! Los datos del emisor se han guardado correctamente.
                </div>
            <?php elseif ($message === 'error'): ?>
                <div class="alert alert-danger" role="alert">
                    Hubo un error al guardar los datos del emisor. Por favor, intenta de nuevo.
                </div>
            <?php elseif ($message === 'invalid_rut'): ?>
                <div class="alert alert-warning" role="alert">
                    El RUT ingresado no es válido. Por favor, verifica y corrige.
                </div>
            <?php endif; ?>
            <form id="emisor-form" method="post" action="">
                <div class="form-group">
                    <label for="sii_wc_rut">RUT <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="sii_wc_rut" name="sii_wc_rut" value="<?php echo isset($emisor->rut) ? esc_attr($emisor->rut) : ''; ?>" maxlength="10" minlength="9" required />
                </div>
                <div class="form-group">
                    <label for="sii_wc_razon_social">Razón Social <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="sii_wc_razon_social" name="sii_wc_razon_social" value="<?php echo isset($emisor->razon_social) ? esc_attr($emisor->razon_social) : ''; ?>" required />
                </div>
                <div class="form-group">
                    <label for="sii_wc_acteco">ACTECO <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="sii_wc_acteco" name="sii_wc_acteco" value="<?php echo esc_attr($acteco); ?>" required pattern="^\d{1,6}$" title="Debe ser un número de hasta 6 dígitos" maxlength="6" />
                    <small class="form-text text-muted">ACTECO: Se acepta un máximo de 4 códigos de actividad económica del emisor del DTE. Se puede incluir sólo el código que corresponde a la transacción.</small>
                    <button type="button" class="btn btn-sm btn-info mt-2" onclick="mostrarInfoActeco()">Información</button>
                </div>
                <div class="form-group">
                    <label for="sii_wc_direccion_origen">Dirección Origen <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="sii_wc_direccion_origen" name="sii_wc_direccion_origen" value="<?php echo isset($emisor->direccion_origen) ? esc_attr($emisor->direccion_origen) : ''; ?>" required />
                </div>
                <div class="form-group">
                    <label for="sii_wc_comuna_origen">Comuna Origen <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="sii_wc_comuna_origen" name="sii_wc_comuna_origen" value="<?php echo isset($emisor->comuna_origen) ? esc_attr($emisor->comuna_origen) : ''; ?>" required />
                </div>
                <div class="form-group">
                    <label for="sii_wc_giro">Giro <span style="color: red;">*</span></label>
                    <input type="text" class="form-control" id="sii_wc_giro" name="sii_wc_giro" value="<?php echo isset($emisor->giro) ? esc_attr($emisor->giro) : ''; ?>" required />
                </div>
                <div class="form-group">
                    <label for="sii_wc_sucursal">Sucursal (Opcional)</label>
                    <input type="text" class="form-control" id="sii_wc_sucursal" name="sii_wc_sucursal" value="<?php echo isset($emisor->sucursal) ? esc_attr($emisor->sucursal) : ''; ?>" />
                </div>
                <div class="form-group">
                    <label for="sii_wc_ciudad_origen">Ciudad Origen (Opcional)</label>
                    <input type="text" class="form-control" id="sii_wc_ciudad_origen" name="sii_wc_ciudad_origen" value="<?php echo isset($emisor->ciudad_origen) ? esc_attr($emisor->ciudad_origen) : ''; ?>" />
                </div>
                <div class="form-group text-center">
                    <button type="button" id="guardar-btn" class="btn btn-primary">Guardar Datos del Emisor</button>
                    <input type="hidden" name="sii_wc_guardar_emisor" value="1" />
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirmación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas guardar los datos del emisor?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmar-guardar-btn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de advertencia -->
<div class="modal fade" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="alertModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertModalLabel">Advertencia</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                El RUT ingresado no es válido. Por favor, verifica y corrige.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function mostrarInfoActeco() {
    Swal.fire({
        title: 'Información ACTECO',
        html: 'ACTECO: Se acepta un máximo de 4 códigos de actividad económica del emisor del DTE. Se puede incluir sólo el código que corresponde a la transacción.<br><br>' +
              '<a href="https://www.sii.cl/ayudas/ayudas_por_servicios/1956-codigos-1959.html" target="_blank">Códigos de actividad económica</a><br>' +
              '<a href="https://zeus.sii.cl/cvc/stc/stc.html" target="_blank">Consultar situación tributaria de terceros</a>',
        icon: 'info',
        confirmButtonText: 'Cerrar'
    });
}

jQuery(document).ready(function($) {
    // Validar y formatear RUT al escribir
    $('#sii_wc_rut').on('input', function() {
        var rut = $(this).val().replace(/[^0-9kK]/g, '').toUpperCase();
        $(this).val(formateaRut(rut));
    });

    $('#guardar-btn').on('click', function() {
        var rut = $('#sii_wc_rut').val();
        if (!validaRut(rut)) {
            $('#alertModal').modal('show');
        } else {
            $('#confirmModal').modal('show');
        }
    });

    $('#confirmar-guardar-btn').on('click', function() {
        $('#confirmModal').modal('hide');
        $('#emisor-form').submit();
    });

    function validaRut(rutCompleto) {
        if (!/^[0-9]+-[0-9kK]{1}$/.test(rutCompleto)) {
            return false;
        }
        var tmp = rutCompleto.split('-');
        var digv = tmp[1].toLowerCase();
        var rut = parseInt(tmp[0], 10);
        var m = 0,
            s = 1;
        while (rut) {
            s = (s + rut % 10 * (9 - m++ % 6)) % 11;
            rut = Math.floor(rut / 10);
        }
        return s ? s - 1 == digv : 'k' == digv;
    }

    function formateaRut(rut) {
        var actual = rut.replace(/^0+/, "").replace(/\./g, "").replace(/-/g, "").toUpperCase();
        if (actual.length <= 1) {
            return actual;
        }
        var inicio = actual.slice(0, -1);
        var dv = actual.slice(-1);
        return inicio + '-' + dv;
    }
});
</script>

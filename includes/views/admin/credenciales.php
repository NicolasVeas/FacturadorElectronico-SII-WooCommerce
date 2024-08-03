<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

$credencialesController = new CredencialesController();
$credencialesController->handle_request();

$credenciales = $credencialesController->getCredenciales();
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>

<div class="container mt-5 mx-auto" style="max-width: 450px;">
    <div class="card shadow-sm border-rounded">
        <div class="card-body p-4">
            <h2 class="h4 mb-3 text-center text-primary font-weight-bold">Configuración de Credenciales</h2>
            <p class="text-center mb-4 text-muted">Estas credenciales son para utilizar el plugin y se agregan una única vez. También permiten utilizar el endpoint de API para facturación electrónica de Riosoft SpA. Para obtener licencia/credenciales, visita <a href="https://riosoft.cl/" target="_blank" style="color: #007bff;">Riosoft</a>.</p>

            <form id="credenciales-form" method="post" action="" data-message="<?php echo $message; ?>">
                <div class="form-group position-relative">
                    <label for="sii_wc_email">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="email" class="form-control" id="sii_wc_email" name="sii_wc_email" value="<?php echo isset($credenciales->email) ? esc_attr($credenciales->email) : ''; ?>" required />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                    </div>
                    <div class="invalid-feedback">Correo inválido.</div>
                </div>
                <div class="form-group position-relative">
                    <label for="sii_wc_password">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="sii_wc_password" name="sii_wc_password" value="<?php echo isset($credenciales->password) ? esc_attr($credenciales->password) : ''; ?>" required />
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        </div>
                    </div>
                    <div class="invalid-feedback">Contraseña inválida.</div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" id="guardar-credenciales-btn" class="btn btn-primary btn-lg shadow-sm">
                        <i class="fas fa-save"></i> Guardar Credenciales
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de carga -->
<div class="modal fade" id="spinnerModal" tabindex="-1" role="dialog" aria-labelledby="spinnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
                <p class="mt-3">Comprobando credenciales... por favor, espera un momento.</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos personalizados */
.input-group .form-control.is-invalid {
    border-color: #e3342f;
}

.input-group .form-control.is-invalid ~ .input-group-append .input-group-text {
    border-color: #e3342f;
    background-color: #f8d7da;
}

.input-group .form-control.is-invalid ~ .input-group-append .input-group-text i {
    color: #e3342f;
}

.form-control.is-invalid {
    background-image: none;
}


/* Ajuste de iconos en los inputs */
.input-group-text {
    background-color: #fff;
    border-left: 0;
}
</style>

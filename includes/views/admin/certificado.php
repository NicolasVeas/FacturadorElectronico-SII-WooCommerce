<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class CertificateView {
    public static function mostrarCertificado() {
        CertificateController::handle_request();

        $upload_success = isset($_GET['upload_success']) ? boolval($_GET['upload_success']) : false;
        $upload_error = isset($_GET['upload_error']) ? sanitize_text_field($_GET['upload_error']) : '';
        $delete_success = isset($_GET['delete_success']) ? sanitize_text_field($_GET['delete_success']) : false;
        $delete_error = isset($_GET['delete_error']) ? sanitize_text_field($_GET['delete_error']) : '';

        $token = CertificateModel::get_token();
        if (!$token) {
            echo '<div class="alert alert-danger text-center" role="alert">Error al obtener el token.</div>';
            return;
        }

        $certificates = CertificateModel::get_certificates($token);

        ?>
        <div class="container mt-5">
            <div id="message-container"></div>
            <div class="card mx-auto" style="max-width: 500px; border-radius: 10px;">
                <div class="card-body text-center">
                    <h3 class="h4 mb-4">Subir archivo de Certificado Digital</h3>
                    <form id="certificate-upload-form" method="post" enctype="multipart/form-data" class="form">
                        <div class="form-group">
                            <label for="certificate_owner_rut" class="sr-only">RUT Propietario</label>
                            <input type="text" class="form-control form-control-sm rut-input" id="certificate_owner_rut" name="certificate_owner_rut" placeholder="RUT Propietario" required data-toggle="tooltip" data-placement="right" title="Formato: sin puntos ni guion">
                        </div>
                        <div class="form-group">
                            <label for="certificate_password" class="sr-only">Contraseña</label>
                            <input type="password" class="form-control form-control-sm" id="certificate_password" name="certificate_password" placeholder="Contraseña" required>
                        </div>
                        <div class="form-group">
                            <input type="file" name="certificate_file" accept=".pfx" class="form-control-file form-control-sm" required />
                            <small class="form-text text-muted">Solo se aceptan archivos .pfx</small>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload"></i> Subir Certificado Digital
                        </button>
                    </form>
                </div>
            </div>
            <hr class="my-4">
            <h3 class="h4 mb-4">Certificados Digitales</h3>
            <table class="table table-striped table-bordered table-sm mt-4">
                <thead class='thead-dark'>
                    <tr>
                        <th>ID</th>
                        <th>RUT Propietario</th>
                        <th>Nombre Propietario</th>
                        <th>Email Propietario</th>
                        <th>RUT Empresa</th>
                        <th>Fecha de Expiración</th>
                        <th>Fecha de Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($certificates)) : ?>
                        <tr>
                            <td colspan="8" class="text-center">No se encontraron certificados.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($certificates as $certificate): ?>
                            <tr>
                                <td><?php echo esc_html($certificate['id']); ?></td>
                                <td><?php echo esc_html($certificate['owner_rut']); ?></td>
                                <td><?php echo esc_html($certificate['owner_name']); ?></td>
                                <td><?php echo esc_html($certificate['owner_email']); ?></td>
                                <td><?php echo esc_html($certificate['enterprise_rut']); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($certificate['expiration_date'])); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($certificate['creation_date'])); ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm delete-certificate-btn" data-certificate-id="<?php echo esc_attr($certificate['id']); ?>" data-certificate-rut="<?php echo esc_attr($certificate['owner_rut']); ?>">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Spinner Modal -->
        <div class="modal fade" id="spinnerModal" tabindex="-1" role="dialog" aria-labelledby="spinnerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <p>Subiendo certificado... Por favor, espera un momento.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Eliminación -->
        <div class="modal fade" id="deleteLoadingModal" tabindex="-1" role="dialog" aria-labelledby="deleteLoadingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only">Eliminando...</span>
                        </div>
                        <p>Eliminando certificado... Por favor, espera un momento.</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            var uploadSuccessPHP = <?php echo json_encode($upload_success); ?>;
            var uploadErrorPHP = <?php echo json_encode($upload_error); ?>;
            var deleteSuccessPHP = <?php echo json_encode($delete_success); ?>;
            var deleteRutPHP = <?php echo json_encode(isset($_GET['delete_rut']) ? sanitize_text_field($_GET['delete_rut']) : ''); ?>;
            var deleteErrorPHP = <?php echo json_encode($delete_error); ?>;
        </script>
        <?php
    }
}

CertificateView::mostrarCertificado();

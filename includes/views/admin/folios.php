<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class FoliosView {
    public static function render($upload_success, $upload_error, $filtered_data) {
        ?>
        <div class="container mt-5"> <!-- Uso de "container" en lugar de "wrap" -->
            <div id="message-container"></div>
            <h2><?php esc_html_e('Administración de CAF', 'sii-woocommerce'); ?></h2>

            <div class="card my-4">
                <div class="card-body">
                    <h3><?php esc_html_e('Cargar Nuevo CAF', 'sii-woocommerce'); ?></h3>
                    <p>
                        Para emitir documentos tributarios electrónicos (DTE), debes cargar un Código de Autorización de Folios (CAF) válido. Este archivo, proporcionado por el SII, contiene un rango de folios autorizados para tus documentos.
                        <a href="https://www.sii.cl/preguntas_frecuentes/catastro/001_012_2020.htm" target="_blank"><?php esc_html_e('Consulta la guía del SII sobre cómo obtener un CAF', 'sii-woocommerce'); ?></a>
                    </p>
                    <form id="caf-upload-form" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="caf_file"><?php esc_html_e('Selecciona el archivo CAF (.xml):', 'sii-woocommerce'); ?></label>
                            <input type="file" class="form-control-file" id="caf_file" name="caf_file" accept=".xml" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> <?php esc_html_e('Subir CAF', 'sii-woocommerce'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <hr />

            <h2><?php esc_html_e('Folios Disponibles', 'sii-woocommerce'); ?></h2>
            <p>Aquí puedes ver un resumen de los folios disponibles para cada tipo de documento tributario electrónico (DTE). Asegúrate de tener suficientes folios disponibles antes de emitir nuevos documentos.</p>

            <table class="table table-striped table-bordered mt-4">
                <thead class='thead-dark'>
                    <tr>
                        <th><?php esc_html_e('Tipo de Documento', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Folio Hasta', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Último Folio Utilizado', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Folios Usados', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Folios Cancelados', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Folios Liberados', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Folios Disponibles', 'sii-woocommerce'); ?></th>
                        <th><?php esc_html_e('Consumo de Folios (%)', 'sii-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filtered_data)) : ?>
                        <tr>
                            <td colspan="8" class="text-center"><?php esc_html_e('No se encontraron datos.', 'sii-woocommerce'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($filtered_data as $item): ?>
                            <?php 
                            $total_folios = $item['caf_until'];
                            $used_folios = $item['used_folios_quantity'] + $item['cancelled_folios_quantity'];
                            $available_folios = $item['available_folios_quantity'];
                            $progress_percentage = ($used_folios / $total_folios) * 100;

                            // Determinar color del progreso
                            $progress_color = 'bg-success';
                            if ($progress_percentage > 75) {
                                $progress_color = 'bg-danger';
                            } elseif ($progress_percentage > 50) {
                                $progress_color = 'bg-warning';
                            }
                            ?>
                            <tr>
                                <td><?php echo $item['dte_type'] == 33 ? 'Factura Electrónica' : ($item['dte_type'] == 39 ? 'Boleta Electrónica' : 'Nota de Crédito'); ?></td>
                                <td><?php echo esc_html($item['caf_until']); ?></td>
                                <td><?php echo esc_html($item['max_used_folio']); ?></td>
                                <td><?php echo esc_html($item['used_folios_quantity']); ?></td>
                                <td><?php echo esc_html($item['cancelled_folios_quantity']); ?></td>
                                <td><?php echo esc_html($item['released_folios_quantity']); ?></td>
                                <td><?php echo esc_html($item['available_folios_quantity']); ?></td>
                                <td class="text-center">
                                    <div class="progress">
                                        <div class="progress-bar <?php echo $progress_color; ?>" role="progressbar" style="width: <?php echo $progress_percentage; ?>%;" aria-valuenow="<?php echo $progress_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo round($progress_percentage); ?>%
                                        </div>
                                    </div>
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
                            <span class="sr-only"><?php esc_html_e('Cargando...', 'sii-woocommerce'); ?></span>
                        </div>
                        <p><?php esc_html_e('Cargando archivo... Por favor, espera un momento.', 'sii-woocommerce'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            var uploadSuccessPHP = <?php echo json_encode($upload_success); ?>;
            var uploadErrorPHP = <?php echo json_encode($upload_error); ?>;
        </script>
        <?php
    }

    public static function render_error($message) {
        ?>
        <script>
        Swal.fire("Error", "<?php echo esc_js($message); ?>", "error");
        </script>
        <?php
    }
}

FoliosController::mostrarFolios();

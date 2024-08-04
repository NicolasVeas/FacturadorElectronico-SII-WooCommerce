<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

$trazabilidad = isset($trazabilidad) ? $trazabilidad : array();
?>

<div class="container-fluid">
    <div class="modal-header">
        <h5 class="modal-title">Trazabilidad del Documento Tributario Electrónico</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-12">
                <h6 class="border-bottom pb-2">Etapas del Documento</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($trazabilidad)) : ?>
                                <?php foreach ($trazabilidad as $etapa) : ?>
                                    <tr>
                                        <td><?php echo esc_html(date('d-m-Y H:i:s', strtotime($etapa['register_date']))); ?></td>
                                        <td><?php echo esc_html($etapa['stage']['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="2" class="text-center">No se encontraron etapas de trazabilidad.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-body p {
    margin-bottom: 0.5rem;
    color: #343a40;
}

.modal-body .border-bottom {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 1rem;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
}

.table tbody tr:last-child td {
    border-bottom: 0;
}

.row {
    margin-bottom: 1.5rem;
}

.modal-body {
    background-color: #f8f9fa;
}

.table th, .table td {
    padding: 0.75rem;
}
</style>

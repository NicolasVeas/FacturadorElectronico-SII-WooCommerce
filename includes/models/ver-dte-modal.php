<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}
?>

<div class="container-fluid">
    <div class="modal-header">
        <h5 class="modal-title">Documento Tributario Electrónico</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Datos del Documento</h6>
                <p><strong>Folio:</strong> <?php echo esc_html($document_number); ?></p>
                <p><strong>Fecha:</strong> <?php echo esc_html($document_date); ?></p>
                <p><strong>Tipo de Documento:</strong> <?php echo esc_html($document_type); ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Totales</h6>
                <p><strong>Monto Neto:</strong> <?php echo esc_html(format_currency($totales['monto_neto'])); ?></p>
                <p><strong>Monto Exento:</strong> <?php echo esc_html(format_currency($totales['monto_exento'])); ?></p>
                <p><strong>Monto IVA:</strong> <?php echo esc_html(format_currency($totales['monto_iva'])); ?></p>
                <p><strong>Monto Total:</strong> <?php echo esc_html(format_currency($totales['monto_total'])); ?></p>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Datos del Emisor</h6>
                <p><strong>RUT Emisor:</strong> <?php echo esc_html($rut_emisor); ?></p>
                <p><strong>Razón Social:</strong> <?php echo esc_html($razon_social_emisor); ?></p>
                <p><strong>Giro:</strong> <?php echo esc_html($giro_emisor); ?></p>
                <p><strong>Dirección Origen:</strong> <?php echo esc_html($direccion_origen_emisor); ?></p>
                <p><strong>Comuna Origen:</strong> <?php echo esc_html($comuna_origen_emisor); ?></p>
                <p><strong>Ciudad Origen:</strong> <?php echo esc_html($ciudad_origen_emisor); ?></p>
                <p><strong>Actecos:</strong> <?php echo esc_html($actecos_emisor); ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Datos del Receptor</h6>
                <p><strong>RUT Receptor:</strong> <?php echo esc_html($rut_receptor); ?></p>
                <p><strong>Razón Social:</strong> <?php echo esc_html($razon_social_receptor); ?></p>
                <p><strong>Giro:</strong> <?php echo esc_html($giro_receptor); ?></p>
                <p><strong>Dirección Destino:</strong> <?php echo esc_html($direccion_destino_receptor); ?></p>
                <p><strong>Comuna Destino:</strong> <?php echo esc_html($comuna_destino_receptor); ?></p>
                <p><strong>Ciudad Destino:</strong> <?php echo esc_html($ciudad_destino_receptor); ?></p>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="border-bottom pb-2">Detalle Productos</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Valor Unitario</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalle_productos as $producto) : ?>
                                <tr>
                                    <td><?php echo esc_html($producto['nombre_item']); ?></td>
                                    <td class="text-right"><?php echo esc_html($producto['cantidad_item']); ?></td>
                                    <td class="text-right"><?php echo esc_html(format_currency($producto['valor_unitario'])); ?></td>
                                    <td class="text-right"><?php echo esc_html(format_currency($producto['monto_item'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
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

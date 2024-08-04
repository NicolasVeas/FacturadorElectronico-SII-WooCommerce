<?php if (!defined('ABSPATH')) exit; ?>

<?php
// Asegurarse de que los datos están disponibles
$document_number = !empty($view_data['document_number']) ? esc_html($view_data['document_number']) : 'N/A';
$document_date = !empty($view_data['document_date']) ? esc_html($view_data['document_date']) : 'N/A';
$document_type = !empty($view_data['document_type']) ? esc_html($view_data['document_type']) : 'N/A';

$totales = $view_data['totales'] ?? array();
$monto_neto = !empty($totales['monto_neto']) ? esc_html($totales['monto_neto']) : 'N/A';
$tasa_iva = !empty($totales['tasa_iva']) ? esc_html($totales['tasa_iva']) : 'N/A';
$monto_iva = !empty($totales['monto_iva']) ? esc_html($totales['monto_iva']) : 'N/A';
$monto_total = !empty($totales['monto_total']) ? esc_html($totales['monto_total']) : 'N/A';

$emisor = $view_data['emisor'] ?? array();
$emisor_rut = !empty($emisor['rut']) ? esc_html($emisor['rut']) : 'N/A';
$emisor_razon_social = !empty($emisor['razon_social']) ? esc_html($emisor['razon_social']) : 'N/A';
$emisor_giro = !empty($emisor['giro']) ? esc_html($emisor['giro']) : 'N/A';
$emisor_direccion = !empty($emisor['direccion_origen']) ? esc_html($emisor['direccion_origen']) : 'N/A';
$emisor_comuna = !empty($emisor['comuna_origen']) ? esc_html($emisor['comuna_origen']) : 'N/A';
$emisor_ciudad = !empty($emisor['ciudad_origen']) ? esc_html($emisor['ciudad_origen']) : 'N/A';
$emisor_actecos = !empty($emisor['actecos']) ? implode(', ', array_column($emisor['actecos'], 'acteco')) : 'N/A';

$receptor = $view_data['receptor'] ?? array();
$receptor_rut = !empty($receptor['rut']) ? esc_html($receptor['rut']) : 'N/A';
$receptor_razon_social = !empty($receptor['razon_social']) ? esc_html($receptor['razon_social']) : 'N/A';
$receptor_giro = !empty($receptor['giro']) ? esc_html($receptor['giro']) : 'N/A';
$receptor_direccion = !empty($receptor['direccion_destino']) ? esc_html($receptor['direccion_destino']) : 'N/A';
$receptor_comuna = !empty($receptor['comuna_destino']) ? esc_html($receptor['comuna_destino']) : 'N/A';
$receptor_ciudad = !empty($receptor['ciudad_destino']) ? esc_html($receptor['ciudad_destino']) : 'N/A';

$detalle_productos = $view_data['detalle_productos'] ?? array();
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
                <p><strong>Folio:</strong> <?php echo $document_number; ?></p>
                <p><strong>Fecha de Emisión:</strong> <?php echo $document_date; ?></p>
                <p><strong>Tipo de Documento:</strong> <?php echo $document_type; ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Totales</h6>
                <p><strong>Monto Neto:</strong> <?php echo $monto_neto; ?></p>
                <p><strong>Monto IVA:</strong> <?php echo $monto_iva; ?></p>
                <p><strong>Tasa IVA:</strong> <?php echo $tasa_iva; ?></p>
                <p><strong>Monto Total:</strong> <?php echo $monto_total; ?></p>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Datos del Emisor</h6>
                <p><strong>RUT Emisor:</strong> <?php echo $emisor_rut; ?></p>
                <p><strong>Razón Social:</strong> <?php echo $emisor_razon_social; ?></p>
                <p><strong>Giro:</strong> <?php echo $emisor_giro; ?></p>
                <p><strong>Dirección Origen:</strong> <?php echo $emisor_direccion; ?></p>
                <p><strong>Comuna Origen:</strong> <?php echo $emisor_comuna; ?></p>
                <p><strong>Ciudad Origen:</strong> <?php echo $emisor_ciudad; ?></p>
                <p><strong>Actecos:</strong> <?php echo $emisor_actecos; ?></p>
            </div>
            <div class="col-md-6">
                <h6 class="border-bottom pb-2">Datos del Receptor</h6>
                <p><strong>RUT Receptor:</strong> <?php echo $receptor_rut; ?></p>
                <p><strong>Razón Social:</strong> <?php echo $receptor_razon_social; ?></p>
                <p><strong>Giro:</strong> <?php echo $receptor_giro; ?></p>
                <p><strong>Dirección Destino:</strong> <?php echo $receptor_direccion; ?></p>
                <p><strong>Comuna Destino:</strong> <?php echo $receptor_comuna; ?></p>
                <p><strong>Ciudad Destino:</strong> <?php echo $receptor_ciudad; ?></p>
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
                            <?php if (!empty($detalle_productos)) : ?>
                                <?php foreach ($detalle_productos as $producto) : ?>
                                    <tr>
                                        <td><?php echo esc_html($producto['nombre_item']); ?></td>
                                        <td class="text-right"><?php echo esc_html($producto['cantidad_item']); ?></td>
                                        <td class="text-right"><?php echo esc_html($producto['valor_unitario']); ?></td>
                                        <td class="text-right"><?php echo esc_html($producto['monto_item']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="text-center">No se encontraron productos.</td>
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

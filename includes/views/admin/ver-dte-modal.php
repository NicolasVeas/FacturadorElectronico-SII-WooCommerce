<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

global $wpdb;

$order_id = intval($_POST['order_id']);
$order = wc_get_order($order_id);

if (!$order) {
    echo 'Pedido no encontrado';
    return;
}

$dte = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sii_wc_dtes WHERE order_id = %d", $order_id));

if (!$dte) {
    echo 'DTE no encontrado';
    return;
}

$emisor = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}sii_wc_emitters LIMIT 1");

$rut_emisor = $emisor ? $emisor->rut : '';
$razon_social_emisor = $emisor ? $emisor->razon_social : '';
$actecos_emisor = $emisor ? json_decode($emisor->actecos, true)[0]['acteco'] : '';
$direccion_origen_emisor = $emisor ? $emisor->direccion_origen : '';
$comuna_origen_emisor = $emisor ? $emisor->comuna_origen : '';
$giro_emisor = $emisor ? $emisor->giro : '';
$sucursal_emisor = $emisor ? $emisor->sucursal : '';
$ciudad_origen_emisor = $emisor ? $emisor->ciudad_origen : '';

$rut_receptor = get_post_meta($order_id, '_billing_rut', true);
$razon_social_receptor = get_post_meta($order_id, '_billing_razon_social', true);
$giro_receptor = get_post_meta($order_id, '_billing_giro', true);
$direccion_destino_receptor = $order->get_billing_address_1();
$comuna_destino_receptor = $order->get_billing_city();
$ciudad_destino_receptor = $order->get_billing_city();

$totales = array(
    'monto_iva' => round($order->get_total_tax()),
    'monto_neto' => round($order->get_subtotal()),
    'monto_exento' => 0,
    'monto_total' => round($order->get_total()),
    'tasa_iva' => 19.00
);

$detalle_productos = array();
foreach ($order->get_items() as $item_id => $item) {
    $product = $item->get_product();
    $detalle_productos[] = array(
        'nombre_item' => $product->get_name(),
        'cantidad_item' => $item->get_quantity(),
        'valor_unitario' => round($item->get_total() / $item->get_quantity()),
        'monto_item' => round($item->get_total())
    );
}

$document_number = $dte->document_number;
$document_date = date('Y-m-d', strtotime($dte->document_date));
$document_type = $dte->document_type == 33 ? 'Factura Electrónica' : ($dte->document_type == 39 ? 'Boleta Electrónica' : 'N/A');
?>

<div class="container-fluid">
    <div class="form-section">
        <h5 class="form-section-title">Datos del Documento</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Folio:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($document_number); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Fecha:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($document_date); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Tipo de Documento:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($document_type); ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5 class="form-section-title">Datos del Emisor</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>RUT Emisor:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($rut_emisor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Razón Social:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($razon_social_emisor); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Giro:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($giro_emisor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Dirección Origen:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($direccion_origen_emisor); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Comuna Origen:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($comuna_origen_emisor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Ciudad Origen:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($ciudad_origen_emisor); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Actecos:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($actecos_emisor); ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5 class="form-section-title">Datos del Receptor</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>RUT Receptor:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($rut_receptor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Razón Social:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($razon_social_receptor); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Giro:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($giro_receptor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Dirección Destino:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($direccion_destino_receptor); ?>" readonly>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Comuna Destino:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($comuna_destino_receptor); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Ciudad Destino:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($ciudad_destino_receptor); ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5 class="form-section-title">Totales</h5>
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Monto IVA:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($totales['monto_iva']); ?>" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Monto Neto:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($totales['monto_neto']); ?>" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Monto Exento:</label>
                    <input type="text" class="form-control" value="0" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Monto Total:</label>
                    <input type="text" class="form-control" value="<?php echo esc_attr($totales['monto_total']); ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h5 class="form-section-title">Detalle Productos</h5>
        <?php foreach ($detalle_productos as $index => $producto): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Producto:</label>
                        <input type="text" class="form-control" value="<?php echo esc_attr($producto['nombre_item']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Cantidad:</label>
                        <input type="text" class="form-control" value="<?php echo esc_attr($producto['cantidad_item']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Valor Unitario:</label>
                        <input type="text" class="form-control" value="<?php echo esc_attr($producto['valor_unitario']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Monto:</label>
                        <input type="text" class="form-control" value="<?php echo esc_attr($producto['monto_item']); ?>" readonly>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.form-section {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.form-section-title {
    margin-bottom: 10px;
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.form-group label {
    font-weight: bold;
}

.form-group input {
    background-color: #fff;
    border: 1px solid #ccc;
}

.form-group input[readonly] {
    background-color: #eee;
}
</style>

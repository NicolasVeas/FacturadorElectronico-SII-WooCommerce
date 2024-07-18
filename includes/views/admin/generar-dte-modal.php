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

$order_date = date('Y-m-d', strtotime($order->get_date_created()));
?>
<div>
    <form id="generar-dte-form">
        <h5>Documento</h5>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="tipo_dte">Tipo de documento</label>
                    <select class="form-control" id="tipo_dte" name="tipo_dte" required>
                        <option value="33">Factura Electrónica</option>
                        <option value="39">Boleta Electrónica</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="fecha_compra">Fecha de Compra:</label>
                    <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" value="<?php echo esc_attr($order_date); ?>" required>
                </div>
            </div>
        </div>
        <div id="factura_fields">
            <h5>Datos del Emisor</h5>
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Actecos:</label>
                        <input type="text" class="form-control" value="<?php echo esc_attr($actecos_emisor); ?>" readonly>
                    </div>
                </div>
            </div>
        </div>
        <div id="receptor_fields">
            <h5>Datos del Receptor</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="rut_receptor">RUT Receptor:</label>
                        <input type="text" class="form-control" id="rut_receptor" name="rut_receptor" value="<?php echo esc_attr($rut_receptor); ?>" pattern="^[0-9]+-[0-9kK]{1}$" maxlength="10" minlength="9" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="razon_social_receptor">Razón Social:</label>
                        <input type="text" class="form-control" id="razon_social_receptor" name="razon_social_receptor" value="<?php echo esc_attr($razon_social_receptor); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="giro_receptor">Giro:</label>
                        <input type="text" class="form-control" id="giro_receptor" name="giro_receptor" value="<?php echo esc_attr($giro_receptor); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="direccion_destino_receptor">Dirección Destino:</label>
                        <input type="text" class="form-control" id="direccion_destino_receptor" name="direccion_destino_receptor" value="<?php echo esc_attr($direccion_destino_receptor); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="comuna_destino_receptor">Comuna Destino:</label>
                        <input type="text" class="form-control" id="comuna_destino_receptor" name="comuna_destino_receptor" value="<?php echo esc_attr($comuna_destino_receptor); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="ciudad_destino_receptor">Ciudad Destino:</label>
                        <input type="text" class="form-control" id="ciudad_destino_receptor" name="ciudad_destino_receptor" value="<?php echo esc_attr($ciudad_destino_receptor); ?>" required>
                    </div>
                </div>
            </div>
        </div>
        <h5>Totales</h5>
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="monto_iva">Monto IVA:</label>
                    <input type="text" class="form-control" id="monto_iva" name="monto_iva" value="<?php echo esc_attr($totales['monto_iva']); ?>" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="monto_neto">Monto Neto:</label>
                    <input type="text" class="form-control" id="monto_neto" name="monto_neto" value="<?php echo esc_attr($totales['monto_neto']); ?>" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="monto_exento">Monto Exento:</label>
                    <input type="text" class="form-control" id="monto_exento" name="monto_exento" value="0" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="monto_total">Monto Total:</label>
                    <input type="text" class="form-control" id="monto_total" name="monto_total" value="<?php echo esc_attr($totales['monto_total']); ?>" readonly>
                </div>
            </div>
        </div>
        <h5>Detalle Productos</h5>
        <?php foreach ($detalle_productos as $index => $producto): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="nombre_item_<?php echo $index; ?>">Producto:</label>
                        <input type="text" class="form-control" id="nombre_item_<?php echo $index; ?>" name="detalle_productos[<?php echo $index; ?>][nombre_item]" value="<?php echo esc_attr($producto['nombre_item']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="cantidad_item_<?php echo $index; ?>">Cantidad:</label>
                        <input type="text" class="form-control" id="cantidad_item_<?php echo $index; ?>" name="detalle_productos[<?php echo $index; ?>][cantidad_item]" value="<?php echo esc_attr($producto['cantidad_item']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="valor_unitario_<?php echo $index; ?>">Valor Unitario:</label>
                        <input type="text" class="form-control" id="valor_unitario_<?php echo $index; ?>" name="detalle_productos[<?php echo $index; ?>][valor_unitario]" value="<?php echo esc_attr($producto['valor_unitario']); ?>" readonly>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="monto_item_<?php echo $index; ?>">Monto:</label>
                        <input type="text" class="form-control" id="monto_item_<?php echo $index; ?>" name="detalle_productos[<?php echo $index; ?>][monto_item]" value="<?php echo esc_attr($producto['monto_item']); ?>" readonly>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            <button type="button" id="submit-generar-dte-btn" class="btn btn-primary" data-order-id="<?php echo esc_attr($order_id); ?>">Emitir Documento</button>
        </div>
    </form>
</div>

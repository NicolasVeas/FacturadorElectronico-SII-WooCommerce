<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

$metodos_pago = WC()->payment_gateways->payment_gateways();
$condiciones_pago = get_option('sii_wc_condiciones_pago', array());
$estado_documento = get_option('sii_wc_estado_documento', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $condiciones_pago = isset($_POST['condiciones_pago']) ? $_POST['condiciones_pago'] : array();
    update_option('sii_wc_condiciones_pago', $condiciones_pago);

    $estado_documento = isset($_POST['estado_documento']) ? $_POST['estado_documento'] : '';
    update_option('sii_wc_estado_documento', $estado_documento);
}
?>

<div class="wrap">
    <h1>Condiciones para Emitir</h1>
    <form method="post" action="">
        <h2>Emitir al completar la compra según Métodos de pago</h2>
        <p>Selecciona según el método de pago si quieres generar el documento de manera automática o manual en pedidos.</p>
        <p>
            <b>Automática:</b> Los documentos se generarán automáticamente para esta forma de pago cuando el pedido llegue al estado EN PROCESO o COMPLETADO según lo que indiques en la pestaña de estados de pedido.<br>
            <b>Manual:</b> Podrás generar manualmente los documentos en el administrador de WooCommerce al ingresar a cada pedido.
        </p>

        <?php foreach ($metodos_pago as $metodo_pago) : ?>
            <label for="condiciones_pago_<?php echo esc_attr($metodo_pago->id); ?>"><?php echo esc_html($metodo_pago->title); ?></label>
            <select name="condiciones_pago[<?php echo esc_attr($metodo_pago->id); ?>]" id="condiciones_pago_<?php echo esc_attr($metodo_pago->id); ?>">
                <option value="manual" <?php selected(isset($condiciones_pago[$metodo_pago->id]) && $condiciones_pago[$metodo_pago->id] === 'manual'); ?>>Manual</option>
                <option value="automatica" <?php selected(isset($condiciones_pago[$metodo_pago->id]) && $condiciones_pago[$metodo_pago->id] === 'automatica'); ?>>Automática</option>
            </select>
            <br>
        <?php endforeach; ?>

        <h2>Emitir cuando el estado de pedido cambie</h2>
        <p>Selecciona en qué estado del pedido se generará el documento.</p>
        <p>Recuerda que algunos métodos de pago como transferencia bancaria dejan los pedidos EN PROCESO mientras tú confirmas manualmente el pago, mientras que los medios de pago con tarjeta, dejan los pedidos COMPLETADOS una vez se ha recibido el pago.</p>
        <p>Te recomendamos elegir COMPLETADO como primera opción y ajustar estas opciones dependiendo de los plugins de métodos de pago con los que trabajes.</p>

        <label for="estado_documento_proceso">
            <input type="radio" name="estado_documento" value="proceso" id="estado_documento_proceso" <?php checked($estado_documento, 'proceso'); ?>>
            Facturar cuando el estado del pedido está en PROCESO
        </label><br>
        <label for="estado_documento_completado">
            <input type="radio" name="estado_documento" value="completado" id="estado_documento_completado" <?php checked($estado_documento, 'completado'); ?>>
            Facturar cuando el estado del pedido está en COMPLETADO
        </label>

        <?php submit_button('Guardar Cambios'); ?>
    </form>
</div>

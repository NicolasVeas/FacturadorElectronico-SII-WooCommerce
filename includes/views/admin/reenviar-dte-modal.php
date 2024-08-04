<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

function formatDocumentType($type) {
    switch ($type) {
        case 33:
            return 'Factura Electrónica';
        case 39:
            return 'Boleta Electrónica';
        case 61:
            return 'Nota de Crédito';
        default:
            return 'Documento';
    }
}
?>

<div class="container-fluid">
    <div class="modal-header">
        <h5 class="modal-title">Reenviar DTE</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <form id="reenviarDteForm">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="folio"><i class="fas fa-file-alt"></i> Folio</label>
                    <input type="text" class="form-control non-editable" id="folio" name="folio" value="<?php echo esc_attr($data['folio']); ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="document_type"><i class="fas fa-file-alt"></i> Tipo de Documento</label>
                    <input type="text" class="form-control non-editable" id="document_type" name="document_type" value="<?php echo esc_attr($data['document_type_formatted']); ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="order_id"><i class="fas fa-hashtag"></i> Número de Orden</label>
                    <input type="text" class="form-control non-editable" id="order_id" name="order_id" value="<?php echo esc_attr($data['order_id']); ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <label for="subject"><i class="fas fa-heading"></i> Asunto</label>
                <input type="text" class="form-control" id="subject" name="subject" value="Envio de <?php echo esc_attr($data['document_type_formatted']); ?> con folio <?php echo esc_attr($data['folio']); ?>" required>
                <div class="invalid-feedback">El asunto es obligatorio.</div>
            </div>
            <div class="form-group">
                <label for="content"><i class="fas fa-comment-alt"></i> Mensaje</label>
                <textarea class="form-control" id="content" name="content" rows="4">Hola <?php echo esc_html($data['nombre_comprador']); ?>, adjunto el DTE correspondiente a tu compra.</textarea>
            </div>
            <div class="form-group">
                <label for="correo_comprador"><i class="fas fa-envelope"></i> Correo del Comprador</label>
                <input type="email" class="form-control" id="correo_comprador" name="correo_comprador" value="<?php echo esc_attr($data['correo_comprador']); ?>" required>
                <div class="invalid-feedback">Por favor, ingresa un correo electrónico válido.</div>
            </div>
            <div class="form-group">
                <label for="correo_emisor"><i class="fas fa-envelope"></i> Correo del Emisor (CC)</label>
                <input type="email" class="form-control" id="correo_emisor" name="correo_emisor" value="<?php echo esc_attr($data['correo_emisor']); ?>" required>
                <div class="invalid-feedback">Por favor, ingresa un correo electrónico válido.</div>
            </div>
            <input type="hidden" id="rut_receptor" name="rut_receptor" value="60803000-K">
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="sendReenviarDteBtn">Enviar</button>
    </div>
</div>

<style>
body {
    font-family: 'Roboto', sans-serif;
}

.modal-header {
    border-bottom: 2px solid #dee2e6;
}

.modal-body {
    padding: 20px;
    background-color: #f5f5f5; /* Fondo gris claro */
}

.modal-body .form-group {
    margin-bottom: 1.5rem;
}

.modal-body .form-group label {
    font-weight: bold;
    display: block;
    margin-bottom: 10px;
    color: #555;
    text-align: left;
}

.modal-body .form-control {
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 8px;
    background-color: #ffffff;
    transition: box-shadow 0.3s;
}

.modal-body .form-control:focus {
    background-color: #e9ecef;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.modal-body .form-control.non-editable {
    background-color: #e9ecef;
    color: #6c757d;
    font-weight: bold;
}

.modal-body .form-control.non-editable:focus {
    background-color: #e9ecef;
    box-shadow: none;
}

.modal-footer {
    border-top: 2px solid #dee2e6;
}

.modal-footer .btn {
    padding: 10px 20px;
    border-radius: 20px; /* Bordes redondeados */
    transition: background-color 0.3s, box-shadow 0.3s; /* Transición para el efecto hover */
}

.modal-footer .btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.modal-footer .btn-primary:hover {
    background-color: #0056b3;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Sombra en hover */
}

.modal-footer .btn-secondary {
    background-color: #ccc;
    border-color: #ccc;
}

.modal-footer .btn-secondary:hover {
    background-color: #999;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Sombra en hover */
}

.modal-footer .btn-success {
    background-color: #28a745; /* Botón enviar color verde */
    border-color: #28a745;
    font-size: 1.1rem; /* Tamaño de fuente ligeramente mayor */
}

.modal-footer .btn-success:hover {
    background-color: #218838;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Sombra en hover */
}

.invalid-feedback {
    display: none;
    color: red;
    margin-top: 5px;
}
</style>

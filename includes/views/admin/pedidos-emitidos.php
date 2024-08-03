<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

?>
<style>
/* Estilos de tabla */
#pedidos-emitidos-table {
    width: 100%;
    border-collapse: collapse;
}

#pedidos-emitidos-table th, #pedidos-emitidos-table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: center;
}

#pedidos-emitidos-table th:first-child,
#pedidos-emitidos-table td:first-child,
#pedidos-emitidos-table th:nth-child(2),
#pedidos-emitidos-table td:nth-child(2),
#pedidos-emitidos-table th:nth-child(3),
#pedidos-emitidos-table td:nth-child(3) {
    text-align: left;
}

#pedidos-emitidos-table td.text-right {
    text-align: right;
}

#pedidos-emitidos-table tbody tr:hover {
    background-color: #f1f1f1;
}

#pagination-container .pagination {
    margin: 0;
}

#pagination-container .page-numbers {
    padding: 10px 15px;
    border: 1px solid #ddd;
    color: #007bff;
    text-decoration: none;
    margin: 0 5px;
}

#pagination-container .page-numbers:hover {
    background-color: #007bff;
    color: white;
}

#pagination-container .current {
    background-color: #007bff;
    color: white;
    border: 1px solid #007bff;
}
</style>

<div class="container mt-5">
    <h2>Pedidos Emitidos</h2>
    <table class="table table-bordered table-striped" id="pedidos-emitidos-table">
        <thead class="thead-dark">
            <tr>
                <th>ID Pedido</th>
                <th>Tipo de Documento</th>
                <th>Folios</th>
                <th>Fecha</th>
                <th>RUT Receptor</th>
                <th>Monto Total</th>
                <th>Progreso</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="pedidos-emitidos-body">
            <!-- Aquí se cargará el contenido de los pedidos emitidos a través de AJAX -->
        </tbody>
    </table>

    <div id="pagination-container" class="d-flex justify-content-center mt-3">
        <!-- Aquí se cargará la paginación a través de AJAX -->
    </div>

    <!-- Modal de carga -->
    <div class="modal fade" id="spinnerModal" tabindex="-1" role="dialog" aria-labelledby="spinnerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-3">Cambiando de página... por favor, espera un momento.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ver DTE -->
    <div class="modal fade" id="viewDteModal" tabindex="-1" role="dialog" aria-labelledby="viewDteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDteModalLabel">Ver DTE</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-3">Cargando datos del DTE...</p>
                </div>
                <div class="modal-body-content"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

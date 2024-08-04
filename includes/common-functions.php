<?php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

if (!function_exists('format_document_type')) {
    function format_document_type($type) {
        switch ($type) {
            case '33':
                return 'Factura Electrónica';
            case '39':
                return 'Boleta Electrónica';
            case '61':
                return 'Nota de Crédito';
            default:
                return $type;
        }
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount) {
        return '$' . number_format($amount, 0, ',', '.'); // Formato a pesos chilenos
    }
}


if (!function_exists('format_status_class')) {
    function format_status_class($status) {
        switch ($status) {
            case 'ACCEPTED':
                return 'badge badge-success';
            case 'REJECTED':
                return 'badge badge-danger';
            case 'GROUPED':
                return 'badge badge-secondary';
            default:
                return 'badge badge-warning';
        }
    }
}

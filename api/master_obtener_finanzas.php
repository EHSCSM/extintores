<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    $stmt = $conexion->query("
        SELECT p.id, p.monto, p.fecha_pago, p.metodo_pago, t.nombre as nombre_taller 
        FROM pagos_saas p
        JOIN talleres t ON p.taller_id = t.id
        ORDER BY p.id DESC
    ");
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "pagos" => $pagos]);
} catch(Exception $e) {
    echo json_encode(["error" => true, "mensaje" => "Error al obtener finanzas."]);
}
?>
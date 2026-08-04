<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    // Blindado: Relacionamos la empresa mediante el extintor, no la inspección
    $stmt = $conexion->prepare("
        SELECT e.nombre_comercial, x.codigo_qr, x.tipo_agente, x.capacidad, d.estatus_final_extintor, '' as foto_anomalia_url
        FROM inspeccion_detalles d
        JOIN inspecciones i ON d.inspeccion_id = i.id
        JOIN extintores x ON d.extintor_id = x.id
        JOIN empresas e ON x.empresa_id = e.id
        WHERE e.taller_id = ?
        ORDER BY i.fecha_inspeccion DESC
    ");
    $stmt->execute([$taller_id]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $datos]);
} catch (Exception $e) {
    echo json_encode(["error" => true, "mensaje" => "Error SQL."]);
}
?>
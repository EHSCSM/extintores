<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : 0;

try {
    // 1. Contar total de equipos del cliente
    $stmtTotal = $conexion->prepare("SELECT COUNT(*) FROM extintores WHERE empresa_id = ?");
    $stmtTotal->execute([$empresa_id]);
    $total = $stmtTotal->fetchColumn();

    // 2. Contar cuántos ha revisado el técnico HOY en esa empresa
    $stmtHoy = $conexion->prepare("
        SELECT COUNT(d.id) 
        FROM inspeccion_detalles d 
        JOIN inspecciones i ON d.inspeccion_id = i.id 
        WHERE i.empresa_id = ? AND i.usuario_id = ? AND DATE(i.fecha_inspeccion) = CURDATE()
    ");
    $stmtHoy->execute([$empresa_id, $tecnico_id]);
    $auditados = $stmtHoy->fetchColumn();

    // 3. Obtener los últimos 5 equipos revisados hoy (Historial)
    $stmtHist = $conexion->prepare("
        SELECT x.codigo_qr, d.estatus_final_extintor 
        FROM inspeccion_detalles d 
        JOIN inspecciones i ON d.inspeccion_id = i.id 
        JOIN extintores x ON d.extintor_id = x.id 
        WHERE i.empresa_id = ? AND i.usuario_id = ? AND DATE(i.fecha_inspeccion) = CURDATE()
        ORDER BY d.id DESC LIMIT 5
    ");
    $stmtHist->execute([$empresa_id, $tecnico_id]);
    $historial = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "total" => $total, "auditados" => $auditados, "historial" => $historial]);
} catch(Exception $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD"]);
}
?>
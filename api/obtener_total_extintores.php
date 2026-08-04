<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;

if ($empresa_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "ID de empresa inválido."]); exit;
}

try {
    // 1. Contar el gran total de extintores que tiene registrados esa empresa
    $stmtTotal = $conexion->prepare("SELECT COUNT(*) as total FROM extintores WHERE empresa_id = :emp");
    $stmtTotal->execute([':emp' => $empresa_id]);
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Contar cuántos de esos extintores ya fueron revisados el día de hoy
    $hoy = date('Y-m-d');
    $stmtAuditados = $conexion->prepare("SELECT COUNT(*) as auditados FROM extintores WHERE empresa_id = :emp AND DATE(fecha_ultima_revision) = :hoy");
    $stmtAuditados->execute([':emp' => $empresa_id, ':hoy' => $hoy]);
    $auditados = $stmtAuditados->fetch(PDO::FETCH_ASSOC)['auditados'];

    echo json_encode([
        "error" => false, 
        "total" => $total, 
        "auditados" => $auditados
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => $e->getMessage()]);
}
?>
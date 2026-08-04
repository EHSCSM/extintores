<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : 0;

if ($tecnico_id === 0) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "ID inválido."]);
    exit;
}

try {
    // 1. Buscamos la ruta más urgente (Pendiente o En Proceso) para este técnico
    $stmt = $conexion->prepare("
        SELECT r.id as ruta_id, r.empresa_id, e.nombre_comercial, r.estatus
        FROM rutas r
        JOIN empresas e ON r.empresa_id = e.id
        WHERE r.tecnico_id = ? AND r.estatus != 'Terminada'
        ORDER BY r.fecha_programada ASC 
        LIMIT 1
    ");
    $stmt->execute([$tecnico_id]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruta) {
        ob_end_clean();
        echo json_encode(["error" => false, "hay_ruta" => false]);
        exit;
    }

    $empresa_id = $ruta['empresa_id'];
    $ruta_id = $ruta['ruta_id'];

    // 2. Magia Automática: Si la ruta estaba "Pendiente", la pasamos a "En Proceso"
    if ($ruta['estatus'] === 'Pendiente') {
        $update = $conexion->prepare("UPDATE rutas SET estatus = 'En Proceso' WHERE id = ?");
        $update->execute([$ruta_id]);
    }

    // 3. Contar TODOS los extintores que tiene esta empresa
    $stmtTotal = $conexion->prepare("SELECT COUNT(*) FROM extintores WHERE empresa_id = ?");
    $stmtTotal->execute([$empresa_id]);
    $total_extintores = $stmtTotal->fetchColumn();

    // 4. Contar cuántos extintores YA auditó hoy el técnico
    $stmtAuditados = $conexion->prepare("
        SELECT COUNT(*) FROM inspecciones 
        WHERE empresa_id = ? AND DATE(fecha_inspeccion) = CURDATE()
    ");
    $stmtAuditados->execute([$empresa_id]);
    $auditados = $stmtAuditados->fetchColumn();

    // 5. Contar cuántos se van al Taller (Rechazados / Condicionados)
    $stmtTaller = $conexion->prepare("
        SELECT COUNT(d.id) FROM inspeccion_detalles d
        JOIN inspecciones i ON d.inspeccion_id = i.id
        WHERE i.empresa_id = ? AND DATE(i.fecha_inspeccion) = CURDATE() AND d.estatus_final_extintor != 'Aprobado'
    ");
    $stmtTaller->execute([$empresa_id]);
    $para_taller = $stmtTaller->fetchColumn();

    ob_end_clean();
    echo json_encode([
        "error" => false, 
        "hay_ruta" => true,
        "datos" => [
            "ruta_id" => $ruta_id,
            "empresa_id" => $empresa_id,
            "nombre_comercial" => $ruta['nombre_comercial'],
            "total_extintores" => $total_extintores,
            "auditados" => $auditados,
            "para_taller" => $para_taller
        ]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error interno: " . $e->getMessage()]);
}
?>
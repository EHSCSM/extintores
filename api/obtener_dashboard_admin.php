<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    // 1. KPIs Principales (BLINDADO)
    $stmtKpis = $conexion->prepare("
        SELECT 
            (SELECT COUNT(*) FROM empresas WHERE taller_id = ?) as clientes,
            (SELECT COUNT(d.id) 
             FROM inspeccion_detalles d 
             JOIN extintores x ON d.extintor_id = x.id 
             JOIN empresas e ON x.empresa_id = e.id 
             WHERE e.taller_id = ? AND d.estatus_final_extintor != 'Aprobado') as taller,
            0 as ventas
    ");
    $stmtKpis->execute([$taller_id, $taller_id]);
    $kpis = $stmtKpis->fetch(PDO::FETCH_ASSOC);

    // 2. Resumen Clientes (BLINDADO)
    $stmtClientes = $conexion->prepare("
        SELECT e.id as empresa_id, e.nombre_comercial,
               (SELECT COUNT(*) FROM extintores x WHERE x.empresa_id = e.id) as total_extintores,
               (SELECT COUNT(d.id) 
                FROM inspeccion_detalles d 
                JOIN extintores x ON d.extintor_id = x.id 
                WHERE x.empresa_id = e.id AND d.estatus_final_extintor != 'Aprobado') as en_taller
        FROM empresas e
        WHERE e.taller_id = ?
    ");
    $stmtClientes->execute([$taller_id]);
    $resumen_clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Requisiciones / Cotizaciones Globales (BLINDADO)
    $stmtCot = $conexion->prepare("
        SELECT e.id as empresa_id, e.nombre_comercial, COUNT(d.id) as total_equipos
        FROM inspeccion_detalles d
        JOIN extintores x ON d.extintor_id = x.id
        JOIN empresas e ON x.empresa_id = e.id
        WHERE e.taller_id = ? AND d.estatus_final_extintor != 'Aprobado'
        GROUP BY e.id, e.nombre_comercial
    ");
    $stmtCot->execute([$taller_id]);
    $cotizaciones = $stmtCot->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        "error" => false, 
        "kpis" => $kpis, 
        "resumen_clientes" => $resumen_clientes, 
        "cotizaciones" => $cotizaciones
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
}
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : 0;

if ($tecnico_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "Falta ID del técnico."]); exit;
}

try {
    // 1. Buscamos la misión
    $query = "
        SELECT r.id as ruta_id, e.id as empresa_id, e.nombre_comercial, 'Ubicación registrada' as direccion, r.estatus
        FROM rutas r
        JOIN empresas e ON r.empresa_id = e.id
        WHERE r.tecnico_id = :tec AND (r.estatus = 'Pendiente' OR r.estatus = 'En Progreso')
        ORDER BY r.fecha_programada ASC LIMIT 1
    ";
    
    $stmt = $conexion->prepare($query);
    $stmt->execute([':tec' => $tecnico_id]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ruta) {
        echo json_encode(["error" => false, "hay_ruta" => false]); exit;
    }

    $empresa_id = $ruta['empresa_id'];

    // 2. Contar Total de Extintores de esa empresa
    $stmtTotal = $conexion->prepare("SELECT COUNT(*) as total FROM extintores WHERE empresa_id = :emp");
    $stmtTotal->execute([':emp' => $empresa_id]);
    $total_extintores = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    // 3. Contar el Avance de HOY
    $fecha_hoy = date('Y-m-d');
    $stmtInsp = $conexion->prepare("SELECT id FROM inspecciones WHERE empresa_id = :emp AND fecha_inspeccion = :fecha LIMIT 1");
    $stmtInsp->execute([':emp' => $empresa_id, ':fecha' => $fecha_hoy]);
    $inspeccion = $stmtInsp->fetch(PDO::FETCH_ASSOC);

    $auditados = 0;
    $para_taller = 0;

    if ($inspeccion) {
        $insp_id = $inspeccion['id'];
        
        // Contar todos los que ya revisó
        $stmtAud = $conexion->prepare("SELECT COUNT(*) as cant FROM inspeccion_detalles WHERE inspeccion_id = :insp");
        $stmtAud->execute([':insp' => $insp_id]);
        $auditados = $stmtAud->fetch(PDO::FETCH_ASSOC)['cant'];

        // Contar los que NO fueron aprobados (Rechazados / Condicionados)
        $stmtTal = $conexion->prepare("SELECT COUNT(*) as cant FROM inspeccion_detalles WHERE inspeccion_id = :insp AND estatus_final_extintor != 'Aprobado'");
        $stmtTal->execute([':insp' => $insp_id]);
        $para_taller = $stmtTal->fetch(PDO::FETCH_ASSOC)['cant'];
    }

    // Armar el paquete de datos
    $ruta['total_extintores'] = $total_extintores;
    $ruta['auditados'] = $auditados;
    $ruta['para_taller'] = $para_taller;

    echo json_encode(["error" => false, "hay_ruta" => true, "datos" => $ruta]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
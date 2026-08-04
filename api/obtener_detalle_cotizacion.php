<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

if ($empresa_id === 0) {
    ob_end_clean(); echo json_encode(["error" => true, "mensaje" => "Empresa no válida."]); exit;
}

try {
    // 1. Obtener equipos rechazados
    $stmt = $conexion->prepare("
        SELECT x.id as extintor_id, x.codigo_qr, x.tipo_agente, x.capacidad, d.estatus_final_extintor
        FROM inspeccion_detalles d
        JOIN inspecciones i ON d.inspeccion_id = i.id
        JOIN extintores x ON d.extintor_id = x.id
        WHERE i.empresa_id = ? AND d.estatus_final_extintor != 'Aprobado'
    ");
    $stmt->execute([$empresa_id]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar si ya hay un precio sugerido en el historial del taller
    foreach ($equipos as &$eq) {
        $concepto_sugerido = "Mantenimiento " . $eq['tipo_agente'] . " " . $eq['capacidad'];
        
        $stmtPrecio = $conexion->prepare("SELECT precio FROM catalogo_precios WHERE taller_id = ? AND concepto = ?");
        $stmtPrecio->execute([$taller_id, $concepto_sugerido]);
        $precio = $stmtPrecio->fetchColumn();
        
        $eq['concepto'] = $concepto_sugerido;
        $eq['precio_sugerido'] = $precio ? floatval($precio) : '';
    }

    ob_end_clean();
    echo json_encode(["error" => false, "datos" => $equipos]);
} catch(PDOException $e) {
    ob_end_clean(); echo json_encode(["error" => true, "mensaje" => "Error de BD"]);
}
?>
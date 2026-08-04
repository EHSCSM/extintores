<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    // Blindado: Usamos e.taller_id en lugar de x.taller_id por si hay registros viejos
    $stmt = $conexion->prepare("
        SELECT x.id, x.codigo_qr, x.tipo_agente, x.capacidad, x.ubicacion_especifica, x.estatus,
               e.nombre_comercial as cliente,
               'Operativo' as etapa_taller
        FROM extintores x
        JOIN empresas e ON x.empresa_id = e.id
        WHERE e.taller_id = ?
        ORDER BY x.id DESC
    ");
    $stmt->execute([$taller_id]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["error" => false, "datos" => $datos]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
}
?>
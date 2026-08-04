<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;

if ($taller_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "Falta ID del taller."]); exit;
}

try {
    $query = "
        SELECT 
            ex.codigo_qr, 
            ex.tipo_agente, 
            ex.capacidad, 
            ex.proxima_recarga, 
            e.id as empresa_id,
            e.nombre_comercial as cliente,
            e.tiene_poliza,
            DATEDIFF(ex.proxima_recarga, CURDATE()) as dias_restantes
        FROM extintores ex
        JOIN empresas e ON ex.empresa_id = e.id
        WHERE e.taller_id = :taller 
          AND ex.proxima_recarga IS NOT NULL 
          AND ex.proxima_recarga <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY ex.proxima_recarga ASC
    ";
    
    $stmt = $conexion->prepare($query);
    $stmt->execute([':taller' => $taller_id]);
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $alertas]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
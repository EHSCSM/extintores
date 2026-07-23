<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

// Recibimos el gafete del Taller
$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;

if ($taller_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "Acceso denegado. Faltan credenciales del taller."]); exit;
}

try {
    // Extraemos las inspecciones uniendo extintores y empresas, filtrando por taller_id
    $query = "
        SELECT 
            i.id,
            e.nombre_comercial,
            ex.codigo_qr,
            ex.tipo_agente,
            ex.capacidad,
            ex.ubicacion_especifica,
            i.estatus_final_extintor,
            i.foto_anomalia_url,
            i.fecha_registro
        FROM inspecciones i
        JOIN extintores ex ON i.extintor_id = ex.id
        JOIN empresas e ON ex.empresa_id = e.id
        WHERE e.taller_id = :taller
        ORDER BY i.fecha_registro DESC
        LIMIT 100
    ";
    
    $stmt = $conexion->prepare($query);
    $stmt->execute([':taller' => $taller_id]);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $reportes]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD: " . $e->getMessage()]);
}
?>
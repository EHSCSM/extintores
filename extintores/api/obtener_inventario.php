<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;
$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;

if ($taller_id === 0 && $empresa_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "Faltan credenciales de acceso."]); exit;
}

try {
    if ($taller_id > 0) {
        $query = "
            SELECT 
                ex.id, ex.codigo_qr, ex.tipo_agente, ex.capacidad, ex.ubicacion_especifica, ex.estatus, ex.etapa_taller,
                ex.ano_fabricacion, ex.ultimo_mantenimiento, ex.proxima_recarga,
                e.nombre_comercial as cliente
            FROM extintores ex
            JOIN empresas e ON ex.empresa_id = e.id
            WHERE e.taller_id = :id
            ORDER BY e.nombre_comercial ASC, ex.codigo_qr ASC
        ";
        $stmt = $conexion->prepare($query);
        $stmt->execute([':id' => $taller_id]);
    } else {
        $query = "
            SELECT 
                ex.id, ex.codigo_qr, ex.tipo_agente, ex.capacidad, ex.ubicacion_especifica, ex.estatus, ex.etapa_taller,
                ex.ano_fabricacion, ex.ultimo_mantenimiento, ex.proxima_recarga,
                e.nombre_comercial as cliente
            FROM extintores ex
            JOIN empresas e ON ex.empresa_id = e.id
            WHERE ex.empresa_id = :id
            ORDER BY ex.codigo_qr ASC
        ";
        $stmt = $conexion->prepare($query);
        $stmt->execute([':id' => $empresa_id]);
    }
    
    $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["error" => false, "datos" => $inventario]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD: " . $e->getMessage()]);
}
?>
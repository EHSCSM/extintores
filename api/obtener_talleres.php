<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    $stmt = $conexion->query("
        SELECT 
            t.id, 
            t.nombre, 
            t.estatus, 
            t.plan_contratado as plan_suscripcion, 
            t.fecha_corte as fecha_proximo_pago,
            (SELECT COUNT(*) FROM usuarios WHERE taller_id = t.id AND rol = 'tecnico') as total_tecnicos,
            (SELECT COUNT(*) FROM empresas WHERE taller_id = t.id) as total_clientes,
            (SELECT COUNT(*) FROM extintores x JOIN empresas e ON x.empresa_id = e.id WHERE e.taller_id = t.id) as total_extintores,
            u.nombre as admin_nombre, 
            u.email as admin_email
        FROM talleres t
        LEFT JOIN usuarios u ON t.id = u.taller_id AND u.rol = 'admin'
        ORDER BY t.id DESC
    ");
    
    $talleres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["error" => false, "datos" => $talleres]);

} catch(Exception $e) { 
    echo json_encode(["error" => true, "mensaje" => "Error de conexión a la BD."]); 
}
?>
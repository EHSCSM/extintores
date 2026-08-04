<?php
require_once 'seguridad.php'; // <--- ESTE ES TU NUEVO CANDADO INFRANQUEABLE
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';


try {
    // Obtenemos todos los talleres y calculamos cuántas empresas/clientes tiene cada uno
    $stmt = $conexion->query("
        SELECT t.id, t.nombre, t.estatus, t.plan_contratado, t.fecha_corte,
               (SELECT COUNT(*) FROM empresas e WHERE e.taller_id = t.id) as total_clientes
        FROM talleres t 
        ORDER BY t.id DESC
    ");
    $talleres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $talleres]);
} catch(Exception $e) {
    echo json_encode(["error" => true, "mensaje" => "Error al cargar talleres."]);
}
?>
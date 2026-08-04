<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : 0;

if ($tecnico_id === 0) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Autenticación inválida."]);
    exit;
}

try {
    // Buscamos las rutas asignadas a este técnico que NO estén terminadas
    $stmt = $conexion->prepare("
        SELECT r.id as ruta_id, r.fecha_programada, r.estatus, 
               e.id as empresa_id, e.nombre_comercial as empresa, e.direccion, e.tiene_poliza
        FROM rutas r
        JOIN empresas e ON r.empresa_id = e.id
        WHERE r.tecnico_id = ? AND r.estatus != 'Terminada'
        ORDER BY r.fecha_programada ASC
    ");
    $stmt->execute([$tecnico_id]);
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode(["error" => false, "datos" => $rutas]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error de conexión."]);
}
?>
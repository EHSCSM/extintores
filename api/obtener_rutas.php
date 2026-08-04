<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    // Traemos el ID de la ruta para poder modificarla
    $stmt = $conexion->prepare("
        SELECT r.id, r.fecha_programada, r.estatus, 
               e.nombre_comercial as empresa, 
               u.nombre as tecnico 
        FROM rutas r
        JOIN empresas e ON r.empresa_id = e.id
        JOIN usuarios u ON r.tecnico_id = u.id
        WHERE r.taller_id = ?
        ORDER BY r.fecha_programada DESC
    ");
    $stmt->execute([$taller_id]);
    $rutas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode(["error" => false, "datos" => $rutas]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
}
?>
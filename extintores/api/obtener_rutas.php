<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;

try {
    $query = "SELECT r.id, r.fecha_programada, r.estatus, u.nombre as tecnico, e.nombre_comercial as empresa 
              FROM rutas r 
              JOIN usuarios u ON r.tecnico_id = u.id 
              JOIN empresas e ON r.empresa_id = e.id 
              WHERE r.taller_id = :taller ORDER BY r.fecha_programada DESC";
    
    $stmt = $conexion->prepare($query);
    $stmt->execute([':taller' => $taller_id]);
    echo json_encode(["error" => false, "datos" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
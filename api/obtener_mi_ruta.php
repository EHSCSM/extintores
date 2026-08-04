<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

// Recibimos la credencial del técnico desde su celular
$tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : 0;
// Obtenemos la fecha exacta del servidor (Hoy)
$fecha_hoy = date('Y-m-d');

if ($tecnico_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "Técnico no identificado."]); exit;
}

try {
    // Buscamos si el técnico tiene una ruta asignada para HOY
    $query = "SELECT r.id as ruta_id, r.empresa_id, e.nombre_comercial 
              FROM rutas r 
              JOIN empresas e ON r.empresa_id = e.id 
              WHERE r.tecnico_id = :tecnico AND r.fecha_programada = :fecha 
              LIMIT 1";
              
    $stmt = $conexion->prepare($query);
    $stmt->execute([':tecnico' => $tecnico_id, ':fecha' => $fecha_hoy]);
    $ruta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ruta) {
        echo json_encode(["error" => false, "hay_ruta" => true, "datos" => $ruta]);
    } else {
        // Si no hay ruta, le avisamos a la app
        echo json_encode(["error" => false, "hay_ruta" => false]);
    }

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD: " . $e->getMessage()]);
}
?>
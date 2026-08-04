<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Ahora verificamos que 'tecnicos' sea un arreglo válido y no esté vacío
if (empty($data['taller_id']) || empty($data['tecnicos']) || empty($data['empresa_id']) || empty($data['fecha'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos para asignar la ruta."]); exit;
}

try {
    $conexion->beginTransaction();
    
    $stmt = $conexion->prepare("INSERT INTO rutas (taller_id, tecnico_id, empresa_id, fecha_programada) VALUES (:taller, :tecnico, :empresa, :fecha)");
    
    // Hacemos un bucle para asignarle la ruta a cada técnico seleccionado
    foreach($data['tecnicos'] as $tec_id) {
        $stmt->execute([
            ':taller' => $data['taller_id'],
            ':tecnico' => $tec_id,
            ':empresa' => $data['empresa_id'],
            ':fecha' => $data['fecha']
        ]);
    }
    
    $conexion->commit();
    echo json_encode(["error" => false, "mensaje" => "✅ Ruta asignada a " . count($data['tecnicos']) . " técnico(s)."]);
} catch (PDOException $e) {
    $conexion->rollBack();
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
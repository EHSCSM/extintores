<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['id'])) {
    echo json_encode(["error" => true, "mensaje" => "Falta el ID del técnico."]); exit;
}

try {
    // Cambiamos el estatus a Inactivo en lugar de borrarlo
    $stmt = $conexion->prepare("UPDATE usuarios SET estatus = 'Inactivo' WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    
    echo json_encode(["error" => false, "mensaje" => "Técnico dado de baja exitosamente."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
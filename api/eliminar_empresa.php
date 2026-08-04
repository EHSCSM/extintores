<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['id'])) {
    echo json_encode(["error" => true, "mensaje" => "Falta el ID de la empresa."]); exit;
}

try {
    // Cambiamos el estatus a Inactivo
    $stmt = $conexion->prepare("UPDATE empresas SET estatus = 'Inactivo' WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    
    echo json_encode(["error" => false, "mensaje" => "Empresa dada de baja exitosamente."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
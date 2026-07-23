<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['id']) || empty($data['nombre_comercial'])) {
    echo json_encode(["error" => true, "mensaje" => "Falta el nombre de la empresa."]); exit;
}

try {
    $poliza = isset($data['tiene_poliza']) ? intval($data['tiene_poliza']) : 0;
    
    $stmt = $conexion->prepare("UPDATE empresas SET nombre_comercial = :nombre, tiene_poliza = :poliza WHERE id = :id");
    $stmt->execute([
        ':nombre' => $data['nombre_comercial'],
        ':poliza' => $poliza,
        ':id'     => $data['id']
    ]);
    
    echo json_encode(["error" => false, "mensaje" => "Empresa actualizada con éxito."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
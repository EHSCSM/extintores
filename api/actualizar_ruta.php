<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['ruta_id']) || empty($data['nuevo_estatus'])) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Faltan datos para actualizar."]);
    exit;
}

try {
    $stmt = $conexion->prepare("UPDATE rutas SET estatus = ? WHERE id = ?");
    $stmt->execute([$data['nuevo_estatus'], $data['ruta_id']]);

    ob_end_clean();
    echo json_encode(["error" => false, "mensaje" => "Estatus actualizado correctamente."]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
}
?>
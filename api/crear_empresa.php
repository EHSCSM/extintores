<?php
ob_start(); // Escudo anti-basura
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validamos que venga el nombre de la empresa y el ID del taller
if (empty($data['nombre_comercial']) || empty($data['taller_id'])) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "El nombre de la empresa es obligatorio."]);
    exit;
}

try {
    // Insertamos la empresa con los 4 datos (incluyendo dirección y póliza)
    $stmt = $conexion->prepare("INSERT INTO empresas (taller_id, nombre_comercial, direccion, tiene_poliza, estatus) VALUES (?, ?, ?, ?, 'Activo')");
    $stmt->execute([
        $data['taller_id'],
        $data['nombre_comercial'],
        $data['direccion'] ?? '',
        $data['tiene_poliza'] ?? 0
    ]);

    ob_end_clean();
    echo json_encode(["error" => false, "mensaje" => "Cliente registrado exitosamente."]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error interno del servidor."]);
}
?>
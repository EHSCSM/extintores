<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['extintor_id']) || empty($data['nueva_etapa'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos."]); exit;
}

try {
    $stmt = $conexion->prepare("UPDATE extintores SET etapa_taller = :etapa WHERE id = :id");
    $stmt->execute([
        ':etapa' => $data['nueva_etapa'],
        ':id' => $data['extintor_id']
    ]);
    echo json_encode(["error" => false, "mensaje" => "Equipo movido con éxito."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
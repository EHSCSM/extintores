<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['extintor_id']) && isset($data['nueva_etapa'])) {
    $stmt = $conexion->prepare("UPDATE extintores SET etapa_taller = ? WHERE id = ?");
    $stmt->execute([$data['nueva_etapa'], $data['extintor_id']]);
    echo json_encode(["error" => false]);
} else {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos"]);
}
?>
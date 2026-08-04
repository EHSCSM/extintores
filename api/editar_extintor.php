<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['id'])) {
    echo json_encode(["error" => true, "mensaje" => "Falta el ID del equipo."]); exit;
}

try {
    $query = "UPDATE extintores SET 
                tipo_agente = :tipo_agente,
                capacidad = :capacidad,
                ubicacion_especifica = :ubicacion,
                ano_fabricacion = :ano,
                ultimo_mantenimiento = :ult_mant,
                proxima_recarga = :prox_rec
              WHERE id = :id";
              
    $stmt = $conexion->prepare($query);
    $stmt->execute([
        ':tipo_agente' => $data['tipo_agente'],
        ':capacidad'   => $data['capacidad'],
        ':ubicacion'   => $data['ubicacion_especifica'],
        ':ano'         => empty($data['ano_fabricacion']) ? null : $data['ano_fabricacion'],
        ':ult_mant'    => empty($data['ultimo_mantenimiento']) ? null : $data['ultimo_mantenimiento'],
        ':prox_rec'    => empty($data['proxima_recarga']) ? null : $data['proxima_recarga'],
        ':id'          => $data['id']
    ]);

    echo json_encode(["error" => false, "mensaje" => "✅ Equipo actualizado correctamente."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
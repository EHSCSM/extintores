<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;

try {
    // Buscamos solo los extintores que NO están en planta y pertenecen a los clientes de este taller
    $query = "SELECT ex.id, ex.codigo_qr, ex.tipo_agente, ex.capacidad, ex.etapa_taller, e.nombre_comercial 
              FROM extintores ex 
              JOIN empresas e ON ex.empresa_id = e.id 
              WHERE e.taller_id = :taller AND ex.etapa_taller != 'En Planta'
              ORDER BY ex.id DESC";
              
    $stmt = $conexion->prepare($query);
    $stmt->execute([':taller' => $taller_id]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $equipos]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
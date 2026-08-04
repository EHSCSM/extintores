<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    $stmt = $conexion->prepare("SELECT nombre, logo_url FROM talleres WHERE id = ?");
    $stmt->execute([$taller_id]);
    $taller = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $taller]);
} catch (Exception $e) {
    echo json_encode(["error" => true]);
}
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

// Recibimos el ID del Taller desde el JavaScript
$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    // Buscamos solo las empresas de ESTE taller
    $stmt = $conexion->prepare("SELECT id, nombre_comercial, razon_social, rfc, direccion, estatus FROM empresas WHERE taller_id = :taller AND estatus = 'Activo' ORDER BY nombre_comercial ASC");
    $stmt->execute([':taller' => $taller_id]);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "empresas" => $empresas]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
}
?>
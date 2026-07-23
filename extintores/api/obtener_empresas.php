<?php
// ==========================================
// RIESGOS CERO - API: OBTENER LISTA DE EMPRESAS
// ==========================================
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    // Solo traemos empresas activas para asignarlas al nuevo usuario
    $query = "SELECT id, nombre_comercial FROM empresas WHERE estatus = 'Activo' ORDER BY nombre_comercial ASC";
    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $empresas = $stmt->fetchAll();

    echo json_encode(["error" => false, "datos" => $empresas]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error al obtener catálogo de empresas."]);
}
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

// Identificador del SaaS
$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;

try {
    // 1. Obtener Técnicos de este taller
    $stmtTec = $conexion->prepare("SELECT id, nombre, email FROM usuarios WHERE rol = 'tecnico' AND taller_id = :taller AND estatus = 'Activo'");
    $stmtTec->execute([':taller' => $taller_id]);
    $tecnicos = $stmtTec->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener Clientes (Empresas) de este taller
    $stmtEmp = $conexion->prepare("SELECT id, nombre_comercial, direccion FROM empresas WHERE taller_id = :taller AND estatus = 'Activo'");
    $stmtEmp->execute([':taller' => $taller_id]);
    $empresas = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "tecnicos" => $tecnicos, "empresas" => $empresas]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
}
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;

if ($taller_id === 0) {
    echo json_encode(["error" => true, "mensaje" => "Falta ID del taller."]); exit;
}

try {
    // 1. Obtener SÓLO los Técnicos ACTIVOS
    $stmtTecnicos = $conexion->prepare("
        SELECT id, nombre, email 
        FROM usuarios 
        WHERE rol = 'tecnico' AND taller_id = :taller AND estatus = 'Activo'
        ORDER BY nombre ASC
    ");
    $stmtTecnicos->execute([':taller' => $taller_id]);
    $tecnicos = $stmtTecnicos->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener SÓLO las Empresas ACTIVAS
    $stmtEmpresas = $conexion->prepare("
        SELECT id, nombre_comercial, tiene_poliza 
        FROM empresas 
        WHERE taller_id = :taller AND estatus = 'Activo'
        ORDER BY nombre_comercial ASC
    ");
    $stmtEmpresas->execute([':taller' => $taller_id]);
    $empresas = $stmtEmpresas->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "error" => false,
        "tecnicos" => $tecnicos,
        "empresas" => $empresas
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD: " . $e->getMessage()]);
}
?>
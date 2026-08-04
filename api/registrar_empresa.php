<?php
// ==========================================
// RIESGOS CERO - API: CREAR NUEVA EMPRESA
// ==========================================
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['nombre_comercial']) || empty($data['razon_social'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos de la empresa."]);
    exit;
}

try {
    $query = "INSERT INTO empresas (nombre_comercial, razon_social, estatus) VALUES (:nc, :rs, 'Activo')";
    $stmt = $conexion->prepare($query);
    $stmt->execute([
        ':nc' => trim($data['nombre_comercial']), 
        ':rs' => trim($data['razon_social'])
    ]);

    echo json_encode(["error" => false, "mensaje" => "Empresa registrada exitosamente."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error al guardar la empresa."]);
}
?>
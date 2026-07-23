<?php
// ==========================================
// RIESGOS CERO - API: REGISTRAR EXTINTOR (FOLIO AUTOMÁTICO)
// ==========================================
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['empresa_id']) || empty($data['tipo_agente']) || empty($data['capacidad'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan campos obligatorios."]);
    exit;
}

try {
    // 1. Obtener nombre de la empresa para extraer las siglas
    $stmtEmp = $conexion->prepare("SELECT nombre_comercial FROM empresas WHERE id = :empresa_id");
    $stmtEmp->execute([':empresa_id' => $data['empresa_id']]);
    $empresa = $stmtEmp->fetch();

    if (!$empresa) {
        echo json_encode(["error" => true, "mensaje" => "Empresa no encontrada."]); exit;
    }

    // Limpiar el nombre (quitar espacios/símbolos) y tomar las primeras 3 letras en Mayúscula
    $nombre_limpio = preg_replace('/[^A-Za-z0-9]/', '', $empresa['nombre_comercial']);
    $siglas = strtoupper(substr($nombre_limpio, 0, 3));
    if (strlen($siglas) < 3) { $siglas = str_pad($siglas, 3, "X"); } // Por si el nombre es muy corto

    // 2. Contar los extintores actuales de esta empresa para calcular el consecutivo
    $stmtCount = $conexion->prepare("SELECT COUNT(id) as total FROM extintores WHERE empresa_id = :empresa_id");
    $stmtCount->execute([':empresa_id' => $data['empresa_id']]);
    $row = $stmtCount->fetch();
    
    $numero_consecutivo = $row['total'] + 1;
    // Formatear a 3 ceros (Ej. 001, 002, 015)
    $folio_generado = $siglas . "-EXT-" . str_pad($numero_consecutivo, 3, "0", STR_PAD_LEFT);

    // 3. Insertar a la base de datos con el folio auto-generado
    $query = "INSERT INTO extintores (empresa_id, codigo_qr, tipo_agente, capacidad, ubicacion_especifica, estatus) 
              VALUES (:empresa_id, :codigo_qr, :tipo_agente, :capacidad, :ubicacion_especifica, 'Operativo')";
    
    $stmt = $conexion->prepare($query);
    $stmt->execute([
        ':empresa_id' => $data['empresa_id'],
        ':codigo_qr' => $folio_generado, // Inyectamos el folio matemático
        ':tipo_agente' => $data['tipo_agente'],
        ':capacidad' => $data['capacidad'],
        ':ubicacion_especifica' => $data['ubicacion_especifica']
    ]);

    echo json_encode(["error" => false, "mensaje" => "Registrado con éxito. Folio: " . $folio_generado]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error interno al guardar el extintor."]);
}
?>
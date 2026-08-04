<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['empresa_id']) || empty($data['detalles'])) {
    ob_end_clean(); echo json_encode(["error" => true, "mensaje" => "Datos incompletos."]); exit;
}

$taller_id = intval($data['taller_id']);
$empresa_id = intval($data['empresa_id']);
$atencion_a = isset($data['atencion_a']) ? trim($data['atencion_a']) : ''; // <-- NUEVO
$subtotal = floatval($data['subtotal']);
$iva = floatval($data['iva']);
$total = floatval($data['total']);
$detalles = $data['detalles'];

try {
    $conexion->beginTransaction();

    $folio = "COT-" . date('Ymd') . "-" . rand(1000, 9999);

    // NUEVO: Agregamos atencion_a al INSERT
    $stmt = $conexion->prepare("INSERT INTO cotizaciones (taller_id, empresa_id, folio, atencion_a, subtotal, iva, total, estatus) VALUES (?, ?, ?, ?, ?, ?, ?, 'Enviada')");
    $stmt->execute([$taller_id, $empresa_id, $folio, $atencion_a, $subtotal, $iva, $total]);
    $cotizacion_id = $conexion->lastInsertId();

    $stmtDetalle = $conexion->prepare("INSERT INTO cotizaciones_detalles (cotizacion_id, extintor_id, concepto, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmtCatalogo = $conexion->prepare("INSERT INTO catalogo_precios (taller_id, concepto, precio) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE precio = ?");

    foreach ($detalles as $det) {
        $ext_id = intval($det['extintor_id']);
        $concepto = $det['concepto'];
        $precio = floatval($det['precio']);

        $stmtDetalle->execute([$cotizacion_id, $ext_id, $concepto, $precio]);
        $stmtCatalogo->execute([$taller_id, $concepto, $precio, $precio]);
    }

    $conexion->commit();
    ob_end_clean();
    echo json_encode(["error" => false, "cotizacion_id" => $cotizacion_id, "folio" => $folio]);

} catch (Exception $e) {
    $conexion->rollBack();
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error interno SQL."]);
}
?>
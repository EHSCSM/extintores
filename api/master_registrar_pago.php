<?php
require_once 'seguridad.php'; // <--- ESTE ES TU NUEVO CANDADO INFRANQUEABLE
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['taller_id']) && isset($data['monto']) && isset($data['nueva_fecha'])) {
    try {
        $conexion->beginTransaction();

        // 1. Guardar el registro del pago en el historial
        $stmtPago = $conexion->prepare("INSERT INTO pagos_saas (taller_id, monto, fecha_pago, metodo_pago) VALUES (?, ?, CURDATE(), ?)");
        $stmtPago->execute([$data['taller_id'], $data['monto'], $data['metodo']]);

        // 2. Extender la fecha de corte del taller y asegurarnos de que esté 'Activo'
        $stmtTaller = $conexion->prepare("UPDATE talleres SET fecha_corte = ?, estatus = 'Activo' WHERE id = ?");
        $stmtTaller->execute([$data['nueva_fecha'], $data['taller_id']]);

        $conexion->commit();
        echo json_encode(["error" => false, "mensaje" => "Pago guardado exitosamente."]);

    } catch(Exception $e) {
        $conexion->rollBack();
        echo json_encode(["error" => true, "mensaje" => "Error al registrar el pago en la BD."]);
    }
} else {
    echo json_encode(["error" => true, "mensaje" => "Datos incompletos."]);
}
?>
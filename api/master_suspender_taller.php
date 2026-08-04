<?php
require_once 'seguridad.php'; // <--- ESTE ES TU NUEVO CANDADO INFRANQUEABLE
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['id']) && isset($data['estatus_actual'])) {
    try {
        $nuevo_estatus = ($data['estatus_actual'] === 'Activo') ? 'Suspendido' : 'Activo';
        
        $stmt = $conexion->prepare("UPDATE talleres SET estatus = ? WHERE id = ?");
        $stmt->execute([$nuevo_estatus, $data['id']]);
        
        echo json_encode(["error" => false, "nuevo_estatus" => $nuevo_estatus]);
    } catch(Exception $e) {
        echo json_encode(["error" => true, "mensaje" => "Error en BD."]);
    }
} else {
    echo json_encode(["error" => true, "mensaje" => "Datos incompletos."]);
}
?>
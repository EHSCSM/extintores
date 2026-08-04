<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['taller_id'])) {
    try {
        $stmt = $conexion->prepare("UPDATE talleres SET plan_contratado = ?, fecha_corte = ? WHERE id = ?");
        $stmt->execute([
            $data['plan_suscripcion'], 
            empty($data['fecha_proximo_pago']) ? null : $data['fecha_proximo_pago'], 
            $data['taller_id']
        ]);
        
        echo json_encode(["error" => false]);
    } catch(Exception $e) { 
        echo json_encode(["error" => true, "mensaje" => "Error al actualizar."]); 
    }
} else {
    echo json_encode(["error" => true, "mensaje" => "ID no recibido."]);
}
?>
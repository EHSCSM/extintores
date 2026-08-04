<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['taller_id']) && isset($data['nuevo_estatus'])) {
    try {
        $stmt = $conexion->prepare("UPDATE talleres SET estatus = ? WHERE id = ?");
        $stmt->execute([
            $data['nuevo_estatus'], 
            $data['taller_id']
        ]);
        
        echo json_encode(["error" => false]);
    } catch(Exception $e) { 
        echo json_encode(["error" => true, "mensaje" => "Error al suspender."]); 
    }
} else {
    echo json_encode(["error" => true, "mensaje" => "Datos incompletos."]);
}
?>
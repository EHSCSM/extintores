<?php
require_once 'seguridad.php'; // <--- ESTE ES TU NUEVO CANDADO INFRANQUEABLE
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['nombre_taller']) && isset($data['admin_email'])) {
    try {
        $conexion->beginTransaction();
        
        // 1. Creamos el Taller en modo de Prueba
        $stmtTaller = $conexion->prepare("INSERT INTO talleres (nombre, estatus, plan_contratado) VALUES (?, 'Activo', 'Prueba Gratuita')");
        $stmtTaller->execute([trim($data['nombre_taller'])]);
        $taller_id = $conexion->lastInsertId();

        // 2. Creamos al Dueño (Administrador) de ese Taller
        $stmtUser = $conexion->prepare("INSERT INTO usuarios (taller_id, nombre, email, password, rol) VALUES (?, ?, ?, ?, 'admin')");
        $stmtUser->execute([
            $taller_id, 
            trim($data['admin_nombre']), 
            trim($data['admin_email']), 
            $data['admin_pass']
        ]);

        $conexion->commit();
        echo json_encode(["error" => false, "mensaje" => "Taller creado con éxito."]);

    } catch(Exception $e) {
        $conexion->rollBack();
        echo json_encode(["error" => true, "mensaje" => "El correo ya existe o hubo un error."]);
    }
} else {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos."]);
}
?>
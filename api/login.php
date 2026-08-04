<?php
session_start(); // ¡ESTA LÍNEA ES VITAL!
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$data = json_decode(file_get_contents("php://input"), true);

if(isset($data['email']) && isset($data['password'])) {
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE email = ? AND estatus = 'Activo'");
    $stmt->execute([$data['email']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si el usuario existe y la contraseña coincide
    if($usuario && $data['password'] === $usuario['password']) {
        
        // ==========================================
        // MAGIA DE SEGURIDAD: Guardamos en el servidor
        // ==========================================
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        $_SESSION['taller_id'] = $usuario['taller_id'];
        $_SESSION['empresa_id'] = $usuario['empresa_id'];
        
        // Enviamos el éxito a JS
        echo json_encode([
            "error" => false, 
            "nombre" => $usuario['nombre']
        ]);
        
    } else {
        echo json_encode(["error" => true, "mensaje" => "Credenciales incorrectas o usuario inactivo."]);
    }
} else {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos."]);
}
?>
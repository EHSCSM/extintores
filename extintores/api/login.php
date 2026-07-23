<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['email']) || empty($data['password'])) {
    echo json_encode(["error" => true, "mensaje" => "Ingresa tu correo y contraseña."]); exit;
}

try {
    // Buscamos al usuario en la base de datos (solo si está Activo)
    $stmt = $conexion->prepare("SELECT id, nombre, rol, taller_id FROM usuarios WHERE email = :email AND password = :password AND estatus = 'Activo' LIMIT 1");
    $stmt->execute([
        ':email' => $data['email'],
        ':password' => $data['password']
    ]);
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Credenciales correctas
        echo json_encode([
            "error" => false, 
            "mensaje" => "Acceso concedido.",
            "usuario" => $usuario
        ]);
    } else {
        // Falló el login
        echo json_encode(["error" => true, "mensaje" => "Credenciales incorrectas o usuario inactivo."]);
    }

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
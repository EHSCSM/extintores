<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['id']) || empty($data['nombre']) || empty($data['email'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos obligatorios."]); exit;
}

try {
    // Si el administrador escribió una nueva contraseña, la actualizamos. Si la dejó vacía, solo actualizamos nombre y correo.
    if (!empty($data['password'])) {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, password = :password WHERE id = :id AND rol = 'tecnico'");
        $stmt->execute([':nombre' => $data['nombre'], ':email' => $data['email'], ':password' => $data['password'], ':id' => $data['id']]);
    } else {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id AND rol = 'tecnico'");
        $stmt->execute([':nombre' => $data['nombre'], ':email' => $data['email'], ':id' => $data['id']]);
    }
    
    echo json_encode(["error" => false, "mensaje" => "Técnico actualizado con éxito."]);
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
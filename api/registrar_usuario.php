<?php
// ==========================================
// RIESGOS CERO - API: CREAR NUEVO USUARIO
// ==========================================
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['nombre']) || empty($data['email']) || empty($data['password']) || empty($data['rol'])) {
    echo json_encode(["error" => true, "mensaje" => "Todos los campos son obligatorios."]);
    exit;
}

try {
    // 1. Validar que el correo no exista ya
    $stmtCheck = $conexion->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmtCheck->bindParam(':email', $data['email'], PDO::PARAM_STR);
    $stmtCheck->execute();
    if ($stmtCheck->rowCount() > 0) {
        echo json_encode(["error" => true, "mensaje" => "El correo electrónico ya está registrado."]);
        exit;
    }

    // 2. Insertar el nuevo usuario
    // Si el rol es super_admin, puede no tener una empresa específica (null)
    $empresa_id = ($data['empresa_id'] !== "") ? intval($data['empresa_id']) : null;

    $query = "INSERT INTO usuarios (empresa_id, nombre, email, password, rol, estatus) 
              VALUES (:empresa_id, :nombre, :email, :password, :rol, 'Activo')";
    
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
    $stmt->bindParam(':nombre', $data['nombre'], PDO::PARAM_STR);
    $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
    $stmt->bindParam(':password', $data['password'], PDO::PARAM_STR); // Manteniendo la lógica de tu login actual
    $stmt->bindParam(':rol', $data['rol'], PDO::PARAM_STR);
    
    $stmt->execute();

    echo json_encode(["error" => false, "mensaje" => "Usuario registrado exitosamente."]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error interno al guardar el usuario."]);
}
?>
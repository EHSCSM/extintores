<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verificamos que lleguen los datos del formulario JS
if (empty($data['nombre']) || empty($data['correo']) || empty($data['contrasena'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos obligatorios."]); exit;
}

try {
    // Insertamos mapeando 'correo' del JS a la columna 'email' de tu BD
    $query = "INSERT INTO usuarios (nombre, email, password, rol, taller_id, estatus) 
              VALUES (:nombre, :email, :pass, 'tecnico', :taller, 'Activo')";
              
    $stmt = $conexion->prepare($query);
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':email'  => $data['correo'], 
        ':pass'   => $data['contrasena'],
        ':taller' => $data['taller_id']
    ]);
    
    echo json_encode(["error" => false, "mensaje" => "Técnico registrado con éxito."]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
<?php
// Iniciamos la sesión segura del servidor
session_start();
header('Content-Type: application/json; charset=utf-8');

// Revisamos si existe una sesión válida guardada en el servidor
if(isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_rol'])) {
    echo json_encode([
        "error" => false,
        "activa" => true,
        "rol" => $_SESSION['usuario_rol']
    ]);
} else {
    // Si no hay sesión, le decimos a JS que lo deje en la pantalla de Login
    echo json_encode(["error" => false, "activa" => false]);
}
?>
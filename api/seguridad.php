<?php
// Iniciamos o retomamos la sesión segura del servidor
session_start();

// Verificamos si NO existe la variable 'usuario_id' en la memoria del servidor
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    
    // Le mandamos un código de estado 401 (No Autorizado) a la red
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json; charset=utf-8');
    
    // Devolvemos el mensaje de bloqueo
    echo json_encode([
        "error" => true, 
        "mensaje" => "⚠️ ACCESO DENEGADO. Intento de conexión bloqueado por Riesgos Cero."
    ]);
    
    // LA LÍNEA MÁS IMPORTANTE: 'exit()' "desconecta" el servidor inmediatamente. 
    // Ningún código que esté por debajo de esta línea se ejecutará jamás.
    exit(); 
}
?>
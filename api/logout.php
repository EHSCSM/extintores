<?php
session_start();

// 1. Vaciamos todas las variables de sesión
$_SESSION = array();

// 2. Destruimos la cookie de sesión en el navegador del usuario
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruimos la sesión en el servidor
session_destroy();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(["error" => false, "mensaje" => "Sesion destruida correctamente"]);
?>
<?php
// ==========================================
// RIESGOS CERO - CONEXIÓN SEGURA A MYSQL (PDO)
// ==========================================

// Cabeceras CORS estrictas para permitir comunicación con la PWA
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Credenciales de Base de Datos (Hostinger)
$host = "localhost"; // En Hostinger casi siempre es localhost
$dbname = "u417425040_extintores"; // Reemplaza con el nombre real en hPanel
$username = "u417425040_extintores"; // Reemplaza con el usuario real en hPanel
$password = "Skaparate100"; // Reemplaza con tu contraseña

try {
    // Conexión forzada a UTF-8 para evitar problemas con acentos y caracteres latinos
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Modo de errores estricto: Si algo falla, PHP lanza una excepción atrapable
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la BD está caída o la contraseña es incorrecta, devolvemos un JSON limpio (no HTML roto)
    die(json_encode([
        "error" => true, 
        "mensaje" => "Error crítico de base de datos. Verifique credenciales en conexion.php."
    ]));
}
?>
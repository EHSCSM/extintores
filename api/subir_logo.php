<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$taller_id = isset($_POST['taller_id']) ? intval($_POST['taller_id']) : 0;

if ($taller_id === 0 || !isset($_FILES['logo'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos o el archivo."]);
    exit;
}

$archivo = $_FILES['logo'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$permitidos = ['jpg', 'jpeg', 'png'];

if (!in_array($extension, $permitidos)) {
    echo json_encode(["error" => true, "mensaje" => "Solo se permiten imágenes JPG o PNG."]);
    exit;
}

// Crear la carpeta "uploads/logos" si no existe en tu servidor
$directorio = "../uploads/logos/";
if (!file_exists($directorio)) {
    mkdir($directorio, 0777, true);
}

// Generar un nombre único para que no choquen los logos de diferentes talleres
$nombre_archivo = "logo_taller_" . $taller_id . "_" . time() . "." . $extension;
$ruta_destino = $directorio . $nombre_archivo;
$ruta_bd = "uploads/logos/" . $nombre_archivo;

if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
    // Actualizar el Taller con la nueva ruta de su logo
    $stmt = $conexion->prepare("UPDATE talleres SET logo_url = ? WHERE id = ?");
    $stmt->execute([$ruta_bd, $taller_id]);
    
    echo json_encode(["error" => false, "mensaje" => "Logotipo actualizado con éxito.", "logo_url" => $ruta_bd]);
} else {
    echo json_encode(["error" => true, "mensaje" => "Error del servidor al guardar el archivo."]);
}
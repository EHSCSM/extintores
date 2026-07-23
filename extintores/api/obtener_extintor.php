<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$qr = isset($_GET['qr']) ? trim($_GET['qr']) : '';

if (empty($qr)) {
    echo json_encode(["error" => true, "mensaje" => "Folio QR no proporcionado."]);
    exit;
}

try {
    // 1. Buscamos el equipo en la base de datos
    $stmt = $conexion->prepare("SELECT * FROM extintores WHERE codigo_qr = :qr LIMIT 1");
    $stmt->execute([':qr' => $qr]);
    $extintor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($extintor) {
        // 2. Formatear la fecha para que el celular la entienda (YYYY-MM)
        $ultima_recarga_formato = "";
        if (!empty($extintor['fecha_ultimo_mantenimiento'])) {
            $ultima_recarga_formato = date("Y-m", strtotime($extintor['fecha_ultimo_mantenimiento']));
        }
        
        // Empaquetamos la variable formateada
        $extintor['ultima_recarga'] = $ultima_recarga_formato;

        // 3. Enviamos los datos al PWA del técnico
        echo json_encode(["error" => false, "extintor" => $extintor]);
    } else {
        echo json_encode(["error" => true, "mensaje" => "No existe un extintor con este folio."]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD: " . $e->getMessage()]);
}
?>
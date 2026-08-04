<?php
ob_start(); // Escudo protector contra espacios en blanco
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

// Recibir los datos enviados por JavaScript
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar que tengamos los datos mínimos
if (empty($data['empresa_id']) || empty($data['tipo_agente']) || empty($data['capacidad'])) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Por favor, completa todos los campos del equipo."]);
    exit;
}

try {
    // 1. Obtener datos de la empresa (incluyendo su nombre comercial para extraer la abreviatura)
    $stmtEmp = $conexion->prepare("SELECT taller_id, nombre_comercial FROM empresas WHERE id = ?");
    $stmtEmp->execute([$data['empresa_id']]);
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        ob_end_clean();
        echo json_encode(["error" => true, "mensaje" => "La empresa seleccionada no existe."]);
        exit;
    }
    
    $taller_id = $empresa['taller_id'];

    // 2. Generar Abreviatura (Primeras 3 letras del nombre, en mayúsculas)
    // Usamos preg_replace para quitar espacios y símbolos y dejar solo letras
    $nombre_limpio = preg_replace('/[^A-Za-z]/', '', $empresa['nombre_comercial']); 
    $abreviatura = strtoupper(substr($nombre_limpio, 0, 3));
    
    // Si la empresa tiene un nombre súper corto (raro), ponemos un comodín
    if (strlen($abreviatura) < 3) {
        $abreviatura = "EXT"; 
    }

    // 3. Descubrir qué número de extintor le toca (Secuencial: 001, 002, etc.)
    $stmtCount = $conexion->prepare("SELECT COUNT(*) FROM extintores WHERE empresa_id = ?");
    $stmtCount->execute([$data['empresa_id']]);
    $total_actual = $stmtCount->fetchColumn();
    $siguiente_numero = $total_actual + 1;

    // Armamos el código final (Ej: REA-EXT-001)
    $qr_generado = $abreviatura . "-EXT-" . str_pad($siguiente_numero, 3, '0', STR_PAD_LEFT);

    // Bucle de seguridad: Por si borraste el 001 y el contador se repite, que busque el siguiente libre
    $stmtCheck = $conexion->prepare("SELECT id FROM extintores WHERE codigo_qr = ?");
    while (true) {
        $stmtCheck->execute([$qr_generado]);
        if ($stmtCheck->rowCount() == 0) break; // Está libre, salimos del bucle
        $siguiente_numero++;
        $qr_generado = $abreviatura . "-EXT-" . str_pad($siguiente_numero, 3, '0', STR_PAD_LEFT);
    }

    // 4. Guardar el Extintor
    $stmt = $conexion->prepare("
        INSERT INTO extintores (taller_id, empresa_id, codigo_qr, tipo_agente, capacidad, ubicacion_especifica, estatus) 
        VALUES (?, ?, ?, ?, ?, ?, 'Operativo')
    ");
    
    $stmt->execute([
        $taller_id,
        $data['empresa_id'],
        $qr_generado,
        $data['tipo_agente'],
        $data['capacidad'],
        $data['ubicacion_especifica'] ?? 'General'
    ]);

    ob_end_clean();
    echo json_encode(["error" => false, "mensaje" => "✅ Equipo creado. QR: " . $qr_generado]);

} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error SQL: " . $e->getMessage()]);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(["error" => true, "mensaje" => "Error interno del servidor."]);
}
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['qr']) || empty($data['empresa_id']) || empty($data['tecnico_id'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos obligatorios."]); exit;
}

try {
    $conexion->beginTransaction();

    // 1. Buscar el ID del extintor usando el Código QR
    $stmtExt = $conexion->prepare("SELECT id FROM extintores WHERE codigo_qr = :qr AND empresa_id = :empresa");
    $stmtExt->execute([':qr' => $data['qr'], ':empresa' => $data['empresa_id']]);
    $extintor = $stmtExt->fetch(PDO::FETCH_ASSOC);

    if (!$extintor) {
        echo json_encode(["error" => true, "mensaje" => "El extintor con QR " . $data['qr'] . " no pertenece a esta empresa o no existe."]);
        $conexion->rollBack();
        exit;
    }

    // 2. Verificar si ya existe una "Carpeta de Inspección" para esta empresa HOY
    $fecha_hoy = date('Y-m-d');
    $stmtInsp = $conexion->prepare("SELECT id FROM inspecciones WHERE empresa_id = :empresa AND fecha_inspeccion = :fecha LIMIT 1");
    $stmtInsp->execute([':empresa' => $data['empresa_id'], ':fecha' => $fecha_hoy]);
    $inspeccion = $stmtInsp->fetch(PDO::FETCH_ASSOC);

    $inspeccion_id = 0;
    if (!$inspeccion) {
        // Si no existe, creamos la carpeta del día
        $stmtNuevaInsp = $conexion->prepare("INSERT INTO inspecciones (taller_id, empresa_id, tecnico_id, fecha_inspeccion) VALUES (:taller, :empresa, :tecnico, :fecha)");
        $stmtNuevaInsp->execute([
            ':taller' => 1, // Puedes pasarlo dinámico después
            ':empresa' => $data['empresa_id'],
            ':tecnico' => $data['tecnico_id'],
            ':fecha' => $fecha_hoy
        ]);
        $inspeccion_id = $conexion->lastInsertId();
    } else {
        $inspeccion_id = $inspeccion['id'];
    }

    // 3. Guardar el detalle (El checklist del extintor)
    $stmtDetalle = $conexion->prepare("
        INSERT INTO inspeccion_detalles 
        (inspeccion_id, extintor_id, manometro, manguera, cilindro, seguro_marchamo, pintura, senaletica, estatus_final_extintor, observaciones) 
        VALUES (:insp, :ext, :man, :manq, :cil, :seg, :pin, :sen, :estatus, :obs)
    ");
    $stmtDetalle->execute([
        ':insp' => $inspeccion_id,
        ':ext' => $extintor['id'],
        ':man' => $data['manometro'],
        ':manq' => $data['manguera'],
        ':cil' => $data['cilindro'],
        ':seg' => $data['seguro_marchamo'],
        ':pin' => $data['pintura'],
        ':sen' => $data['senaletica'],
        ':estatus' => $data['estatus'],
        ':obs' => $data['observaciones']
    ]);

    // 4. Actualizar el estado global del extintor en el inventario (Si lo mandan a Taller o sigue Operativo)
    $estatus_inventario = ($data['estatus'] === 'Rechazado' || $data['estatus'] === 'Condicionado') ? 'En Taller' : 'Operativo';
    $stmtUpdateExt = $conexion->prepare("UPDATE extintores SET estatus = :estatus WHERE id = :id");
    $stmtUpdateExt->execute([':estatus' => $estatus_inventario, ':id' => $extintor['id']]);

    $conexion->commit();
    echo json_encode(["error" => false, "mensaje" => "✅ Inspección registrada con éxito."]);

} catch (PDOException $e) {
    $conexion->rollBack();
    echo json_encode(["error" => true, "mensaje" => "Error BD: " . $e->getMessage()]);
}
?>
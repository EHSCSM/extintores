<?php
require_once 'seguridad.php'; // <--- ESTE ES TU NUEVO CANDADO INFRANQUEABLE
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validación estricta para B2B
if (empty($data['extintor_id']) || empty($data['usuario_id'])) {
    echo json_encode(["error" => true, "mensaje" => "Faltan datos de sesión o del equipo."]);
    exit;
}

try {
    $conexion->beginTransaction();

    $taller_id = isset($data['taller_id']) ? intval($data['taller_id']) : 1;
    $fecha_hoy = date('Y-m-d');

    // 1. Obtener la empresa del extintor para el aislamiento SaaS
    $stmtEmp = $conexion->prepare("SELECT empresa_id FROM extintores WHERE id = :id LIMIT 1");
    $stmtEmp->execute([':id' => $data['extintor_id']]);
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);
    $empresa_id = $empresa ? $empresa['empresa_id'] : 0;

    // 2. Buscar si ya hay un "Reporte Padre" de esta empresa hoy
    $stmtBusqueda = $conexion->prepare("SELECT id FROM inspecciones WHERE empresa_id = :emp_id AND fecha_inspeccion = :fecha LIMIT 1");
    $stmtBusqueda->execute([':emp_id' => $empresa_id, ':fecha' => $fecha_hoy]);
    $inspeccionPadre = $stmtBusqueda->fetch(PDO::FETCH_ASSOC);

    if ($inspeccionPadre) {
        $inspeccion_id = $inspeccionPadre['id'];
    } else {
        // Crear nuevo reporte padre
        $stmtInsert = $conexion->prepare("INSERT INTO inspecciones (taller_id, empresa_id, tecnico_id, fecha_inspeccion, estatus) VALUES (:taller, :emp, :tec, :fecha, 'En Progreso')");
        $stmtInsert->execute([
            ':taller' => $taller_id,
            ':emp' => $empresa_id,
            ':tec' => $data['usuario_id'],
            ':fecha' => $fecha_hoy
        ]);
        $inspeccion_id = $conexion->lastInsertId();
    }

    // 3. Guardar el detalle del extintor (Los 6 campos exactos del diseño nuevo)
    $stmtDetalle = $conexion->prepare("
        INSERT INTO inspeccion_detalles 
        (inspeccion_id, extintor_id, estatus_final_extintor, ano_fabricacion, ultima_recarga, chk_garantia, 
         chk_manometro, chk_manguera, chk_cilindro, chk_seguro, chk_pintura, chk_senaletica,
         cot_recarga_fg, cot_senaletica, cot_soporte, cot_funda, cot_refaccion, altura_correcta, observaciones, firma_tecnico) 
        VALUES 
        (:insp, :ext, :estatus, :fab, :rec, :gar, :man, :manq, :cil, :seg, :pin, :sen, :cot1, :cot2, :cot3, :cot4, :cot5, :alt, :obs, :firma)
    ");

    $stmtDetalle->execute([
        ':insp' => $inspeccion_id,
        ':ext' => $data['extintor_id'],
        ':estatus' => $data['estatus_final'],
        ':fab' => $data['ano_fabricacion'],
        ':rec' => $data['ultima_recarga'],
        ':gar' => $data['chk_garantia'] ?? 'No',
        ':man' => $data['chk_manometro'] ?? 'Bien',
        ':manq' => $data['chk_manguera'] ?? 'Bien',
        ':cil' => $data['chk_cilindro'] ?? 'Bien',
        ':seg' => $data['chk_seguro'] ?? 'Bien',
        ':pin' => $data['chk_pintura'] ?? 'Bien',
        ':sen' => $data['chk_senaletica'] ?? 'Bien',
        ':cot1' => $data['cot_recarga_fg'] ?? 0,
        ':cot2' => $data['cot_senaletica'] ?? 0,
        ':cot3' => $data['cot_soporte'] ?? 0,
        ':cot4' => $data['cot_funda'] ?? 0,
        ':cot5' => $data['cot_refaccion'] ?? 0,
        ':alt' => $data['altura_correcta'] ?? 1,
        ':obs' => $data['observaciones'] ?? '',
        ':firma' => $data['firma'] ?? ''
    ]);

    // 4. Actualizar el estatus general del extintor en el inventario
    $stmtUpdateExt = $conexion->prepare("UPDATE extintores SET estatus = :est, ano_fabricacion = :fab, ultima_recarga = :rec WHERE id = :id");
    $stmtUpdateExt->execute([
        ':est' => ($data['estatus_final'] === 'Aprobado') ? 'Operativo' : 'Mantenimiento',
        ':fab' => $data['ano_fabricacion'],
        ':rec' => $data['ultima_recarga'],
        ':id' => $data['extintor_id']
    ]);

    $conexion->commit();
    echo json_encode(["error" => false, "mensaje" => "Inspección registrada con éxito."]);

} catch (PDOException $e) {
    $conexion->rollBack();
    echo json_encode(["error" => true, "mensaje" => "Error interno SQL: " . $e->getMessage()]);
}
?>
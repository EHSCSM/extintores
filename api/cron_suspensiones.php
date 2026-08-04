<?php
// Este archivo está diseñado para ser ejecutado por el servidor, no por un humano.
require_once 'conexion.php';

// Medida de seguridad simple para evitar que curiosos lo ejecuten desde el navegador
$token_seguridad = isset($_GET['token']) ? $_GET['token'] : '';
if ($token_seguridad !== 'RiesgosCeroAdmin2026') {
    die("Acceso denegado. Robot en reposo.");
}

try {
    $conexion->beginTransaction();

    // 1. Buscar los talleres que están Activos, que tienen fecha de pago y que la fecha ya pasó
    $stmt = $conexion->prepare("
        SELECT id, nombre FROM talleres 
        WHERE estatus = 'Activo' 
        AND fecha_proximo_pago IS NOT NULL 
        AND fecha_proximo_pago < CURDATE()
    ");
    $stmt->execute();
    $morosos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_suspendidos = 0;

    foreach ($morosos as $taller) {
        $taller_id = $taller['id'];

        // 2. Apagar el Taller
        $updateTaller = $conexion->prepare("UPDATE talleres SET estatus = 'Suspendido' WHERE id = ?");
        $updateTaller->execute([$taller_id]);

        // 3. Apagar a todos sus usuarios (Dueños, Técnicos y Clientes) 
        // Nunca te suspenderá a ti (super_admin)
        $updateUsuarios = $conexion->prepare("UPDATE usuarios SET estatus = 'Suspendido' WHERE taller_id = ? AND rol != 'super_admin'");
        $updateUsuarios->execute([$taller_id]);

        $total_suspendidos++;
    }

    $conexion->commit();
    echo "Reporte del Cron Job: Operación completada. Talleres suspendidos hoy: " . $total_suspendidos;

} catch (Exception $e) {
    $conexion->rollBack();
    echo "Error crítico en el robot: " . $e->getMessage();
}
?>
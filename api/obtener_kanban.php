<?php
// FASE 1: CANDADO MAESTRO (Si no hay sesión, se bloquea y muere aquí)
require_once 'seguridad.php';
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    // FASE 2: AISLAMIENTO DE INQUILINOS
    $rol = $_SESSION['usuario_rol'];
    
    // Si el usuario NO eres tú (SuperAdmin), el sistema IGNORA lo que mande el JavaScript
    // y lo obliga estrictamente a usar el ID de su propio taller guardado en el servidor.
    if ($rol !== 'superadmin' && $rol !== 'super_admin') {
        $taller_id = $_SESSION['taller_id'];
    } else {
        // Solo tú (el Dueño del SaaS) puedes elegir qué taller quieres ver enviando el ID
        $taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 1;
    }

    // Buscamos los equipos dañados protegiendo estrictamente la consulta SQL
    $stmt = $conexion->prepare("
        SELECT x.id, x.codigo_qr, x.tipo_agente, x.capacidad, e.nombre_comercial,
               COALESCE(NULLIF(x.etapa_taller, 'Cliente'), 'Recibido') as etapa_taller
        FROM extintores x
        JOIN empresas e ON x.empresa_id = e.id
        JOIN (
            SELECT extintor_id, estatus_final_extintor
            FROM inspeccion_detalles
            WHERE id IN (SELECT MAX(id) FROM inspeccion_detalles GROUP BY extintor_id)
        ) d ON d.extintor_id = x.id
        WHERE e.taller_id = ? 
          AND d.estatus_final_extintor != 'Aprobado'
          AND (x.etapa_taller IS NULL OR x.etapa_taller != 'En Planta')
        ORDER BY x.id ASC
    ");
    
    // Aquí inyectamos el ID blindado
    $stmt->execute([$taller_id]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["error" => false, "datos" => $datos]);

} catch (Exception $e) {
    echo json_encode(["error" => true, "mensaje" => "Error al consultar la base de datos."]);
}
?>
<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

try {
    // 1. OBTENER KPIs (Indicadores Principales)
    $stmtClientes = $conexion->query("SELECT COUNT(*) as total FROM empresas");
    $totalClientes = $stmtClientes->fetch(PDO::FETCH_ASSOC)['total'];

    // Consideramos "En Taller" a los extintores con estatus Rechazado o Condicionado
    $stmtTaller = $conexion->query("SELECT COUNT(*) as total FROM extintores WHERE estatus IN ('Condicionado', 'Rechazado')");
    $totalTaller = $stmtTaller->fetch(PDO::FETCH_ASSOC)['total'];

    // Cotizaciones Pendientes (Inspecciones que tengan alguna casilla de venta activada)
    $queryVentas = "SELECT COUNT(DISTINCT id) as total FROM inspecciones 
                    WHERE cot_recarga_fg = 1 OR cot_senaletica = 1 OR cot_soporte = 1 OR cot_funda = 1 OR cot_refaccion = 1";
    $stmtVentas = $conexion->query($queryVentas);
    $totalVentas = $stmtVentas->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. RESUMEN POR EMPRESA (Agrupación matemática)
    $queryResumen = "
        SELECT 
            e.id as empresa_id,
            e.nombre_comercial,
            COUNT(ex.id) as total_extintores,
            SUM(CASE WHEN ex.estatus IN ('Condicionado', 'Rechazado') THEN 1 ELSE 0 END) as en_taller,
            SUM(CASE WHEN ex.tipo_agente = 'PQS' THEN 1 ELSE 0 END) as total_pqs,
            SUM(CASE WHEN ex.tipo_agente = 'CO2' THEN 1 ELSE 0 END) as total_co2,
            SUM(CASE WHEN ex.tipo_agente = 'Agua' THEN 1 ELSE 0 END) as total_agua,
            SUM(CASE WHEN ex.tipo_agente = 'Espuma' THEN 1 ELSE 0 END) as total_espuma
        FROM empresas e
        LEFT JOIN extintores ex ON e.id = ex.empresa_id
        GROUP BY e.id, e.nombre_comercial
        ORDER BY e.nombre_comercial ASC
    ";
    $stmtResumen = $conexion->query($queryResumen);
    $resumenClientes = $stmtResumen->fetchAll(PDO::FETCH_ASSOC);

    // 3. OPORTUNIDADES DE VENTA (Cotizaciones detalladas)
    $queryCotizaciones = "
        SELECT 
            i.id as inspeccion_id,
            e.nombre_comercial,
            ex.codigo_qr,
            i.cot_recarga_fg, i.cot_senaletica, i.cot_soporte, i.cot_funda, i.cot_refaccion
        FROM inspecciones i
        JOIN extintores ex ON i.extintor_id = ex.id
        JOIN empresas e ON ex.empresa_id = e.id
        WHERE i.cot_recarga_fg = 1 OR i.cot_senaletica = 1 OR i.cot_soporte = 1 OR i.cot_funda = 1 OR i.cot_refaccion = 1
        ORDER BY i.fecha_registro DESC
    ";
    $stmtCotizaciones = $conexion->query($queryCotizaciones);
    $cotizaciones = $stmtCotizaciones->fetchAll(PDO::FETCH_ASSOC);

    // Enviar el paquete completo al Dashboard
    echo json_encode([
        "error" => false,
        "kpis" => [
            "clientes" => $totalClientes,
            "taller" => $totalTaller,
            "ventas" => $totalVentas
        ],
        "resumen_clientes" => $resumenClientes,
        "cotizaciones" => $cotizaciones
    ]);

} catch (PDOException $e) {
    echo json_encode(["error" => true, "mensaje" => "Error de BD: " . $e->getMessage()]);
}
?>
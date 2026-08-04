<?php
ob_start(); 
require('fpdf.php');
require_once 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) { ob_end_clean(); die("Error: Cotización no especificada."); }

try {
    // 1. Obtener la Cotización Guardada
    $stmtCot = $conexion->prepare("
        SELECT c.*, e.nombre_comercial, e.direccion, t.logo_url, t.nombre as nombre_taller
        FROM cotizaciones c
        JOIN empresas e ON c.empresa_id = e.id
        JOIN talleres t ON c.taller_id = t.id
        WHERE c.id = ?
    ");
    $stmtCot->execute([$id]);
    $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

    if (!$cotizacion) { ob_end_clean(); die("Error: Cotización no encontrada."); }

    // 2. Obtener el desglose
    $stmtDet = $conexion->prepare("
        SELECT cd.concepto, cd.precio_unitario, x.codigo_qr
        FROM cotizaciones_detalles cd
        LEFT JOIN extintores x ON cd.extintor_id = x.id
        WHERE cd.cotizacion_id = ?
    ");
    $stmtDet->execute([$id]);
    $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

    // 3. Generar el PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Header
    if (!empty($cotizacion['logo_url']) && file_exists("../" . $cotizacion['logo_url'])) {
        $pdf->Image("../" . $cotizacion['logo_url'], 10, 10, 35);
    }

    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(30, 58, 138); 
    $pdf->Cell(0, 10, utf8_decode('COTIZACIÓN COMERCIAL'), 0, 1, 'R');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(220, 38, 38);
    $pdf->Cell(0, 6, 'FOLIO: ' . $cotizacion['folio'], 0, 1, 'R');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y', strtotime($cotizacion['fecha_creacion'])), 0, 1, 'R');
    $pdf->Ln(15);

    // Datos del Cliente
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(0, 8, utf8_decode(' PREPARADO PARA:'), 0, 1, 'L', true);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, utf8_decode($cotizacion['nombre_comercial']), 0, 1);
    
    // NUEVO: Si escribieron un nombre de encargado, lo imprimimos
    if (!empty($cotizacion['atencion_a'])) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, utf8_decode('Atención a: ' . $cotizacion['atencion_a']), 0, 1);
    }
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, utf8_decode($cotizacion['direccion'] ?? 'Dirección no registrada'), 0, 1);
    $pdf->Ln(10);

    // Tabla
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(30, 58, 138); 
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(35, 10, 'EQUIPO', 1, 0, 'C', true);
    $pdf->Cell(115, 10, 'DESCRIPCION DEL SERVICIO', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'IMPORTE', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(50, 50, 50);
    foreach($detalles as $det) {
        $pdf->Cell(35, 10, utf8_decode($det['codigo_qr'] ? $det['codigo_qr'] : 'N/A'), 1, 0, 'C');
        $pdf->Cell(115, 10, utf8_decode($det['concepto']), 1, 0, 'L');
        $pdf->Cell(40, 10, '$ ' . number_format($det['precio_unitario'], 2), 1, 1, 'R');
    }
    
    // Totales
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(150, 8, 'SUBTOTAL:', 0, 0, 'R');
    $pdf->Cell(40, 8, '$ ' . number_format($cotizacion['subtotal'], 2), 1, 1, 'R');
    
    $pdf->Cell(150, 8, 'I.V.A. (16%):', 0, 0, 'R');
    $pdf->Cell(40, 8, '$ ' . number_format($cotizacion['iva'], 2), 1, 1, 'R');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(16, 185, 129);
    $pdf->Cell(150, 10, 'TOTAL A PAGAR (MXN):', 0, 0, 'R');
    $pdf->Cell(40, 10, '$ ' . number_format($cotizacion['total'], 2), 1, 1, 'R');

    // Footer
    $pdf->SetY(-40);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, utf8_decode('Condiciones Comerciales:'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 5, utf8_decode('- Vigencia de esta cotización: 15 días naturales.'), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('- Este documento es un presupuesto, no un comprobante fiscal ni factura.'), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_decode('- Los equipos se liberarán a reparación una vez aprobado este presupuesto.'), 0, 1, 'L');

    ob_end_clean();
    $pdf->Output('I', $cotizacion['folio'] . '.pdf');

} catch(Exception $e) { ob_end_clean(); die("Error interno."); }
?>
<?php
ob_start(); 
require('fpdf.php');
require_once 'conexion.php';

$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;

if ($empresa_id === 0) { ob_end_clean(); die("Error: Cliente no especificado."); }

try {
    $stmtEmp = $conexion->prepare("
        SELECT e.nombre_comercial, e.razon_social, e.direccion, t.logo_url 
        FROM empresas e 
        JOIN talleres t ON e.taller_id = t.id 
        WHERE e.id = ?
    ");
    $stmtEmp->execute([$empresa_id]);
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) { ob_end_clean(); die("Error: Cliente no encontrado."); }

    // BLINDAJE: Buscamos la inspección usando el ID de la Empresa que tiene el Extintor.
    $stmtInsp = $conexion->prepare("
        SELECT d.estatus_final_extintor, x.codigo_qr, x.tipo_agente, x.capacidad, d.observaciones, i.fecha_inspeccion
        FROM inspeccion_detalles d
        JOIN inspecciones i ON d.inspeccion_id = i.id
        JOIN extintores x ON d.extintor_id = x.id
        WHERE x.empresa_id = ?
        ORDER BY i.fecha_inspeccion DESC, x.codigo_qr ASC
    ");
    $stmtInsp->execute([$empresa_id]);
    $detalles = $stmtInsp->fetchAll(PDO::FETCH_ASSOC);

    $pdf = new FPDF();
    $pdf->AddPage();
    
    if (!empty($empresa['logo_url']) && file_exists("../" . $empresa['logo_url'])) {
        $pdf->Image("../" . $empresa['logo_url'], 10, 10, 35);
        $pdf->SetX(50); 
    }

    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(30, 58, 138); 
    $pdf->Cell(0, 10, utf8_decode('Reporte de extintores'), 0, 1, 'C');
    $pdf->Ln(15);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->Cell(40, 7, 'Cliente:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, utf8_decode($empresa['nombre_comercial'] ?? 'N/A'), 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, utf8_decode('Dirección:'), 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, utf8_decode($empresa['direccion'] ?? 'N/A'), 0, 1);
    $pdf->Ln(10);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->Cell(35, 10, 'Folio QR', 1, 0, 'C', true);
    $pdf->Cell(55, 10, 'Agente / Capacidad', 1, 0, 'C', true);
    $pdf->Cell(35, 10, 'Dictamen', 1, 0, 'C', true);
    $pdf->Cell(65, 10, 'Observaciones', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 9);
    foreach ($detalles as $row) {
        $qr = (string)($row['codigo_qr'] ?? '');
        $agente = (string)($row['tipo_agente'] ?? '') . ' ' . (string)($row['capacidad'] ?? '');
        $estatus = (string)($row['estatus_final_extintor'] ?? 'N/A');
        $obs = substr((string)($row['observaciones'] ?? ''), 0, 35);

        $pdf->Cell(35, 10, utf8_decode($qr), 1, 0, 'C');
        $pdf->Cell(55, 10, utf8_decode($agente), 1, 0, 'C');
        
        if ($estatus === 'Aprobado') $pdf->SetTextColor(16, 185, 129);
        else if ($estatus === 'Rechazado') $pdf->SetTextColor(220, 38, 38);
        else $pdf->SetTextColor(217, 119, 6);
        
        $pdf->Cell(35, 10, utf8_decode($estatus), 1, 0, 'C');
        $pdf->SetTextColor(50, 50, 50); 
        $pdf->Cell(65, 10, utf8_decode($obs), 1, 1, 'L');
    }

    $pdf->Ln(20);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, '______________________________________', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Firma y Sello del Taller', 0, 1, 'C');

    ob_end_clean();
    $pdf->Output('I', 'Dictamen_' . $empresa_id . '.pdf');

} catch(Exception $e) { 
    ob_end_clean(); die("Error en el sistema: " . $e->getMessage()); 
}
?>
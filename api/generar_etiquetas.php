<?php
ob_start();
require('fpdf.php');
require_once 'conexion.php';

$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;
$desde = isset($_GET['desde']) && is_numeric($_GET['desde']) ? intval($_GET['desde']) : null;
$hasta = isset($_GET['hasta']) && is_numeric($_GET['hasta']) ? intval($_GET['hasta']) : null;

if ($empresa_id === 0) { ob_end_clean(); die("Error: Cliente no especificado."); }

try {
    $stmtEmp = $conexion->prepare("SELECT nombre_comercial FROM empresas WHERE id = ?");
    $stmtEmp->execute([$empresa_id]);
    $empresa = $stmtEmp->fetch(PDO::FETCH_ASSOC);

    // Seleccionar extintores SOLO de esta empresa
    $stmtExt = $conexion->prepare("SELECT codigo_qr, tipo_agente, capacidad FROM extintores WHERE empresa_id = ? ORDER BY id ASC");
    $stmtExt->execute([$empresa_id]);
    $extintores = $stmtExt->fetchAll(PDO::FETCH_ASSOC);

    // Filtrar por rango numérico
    $extintores_filtrados = [];
    $contador = 1;
    foreach($extintores as $ext) {
        if ($desde !== null && $contador < $desde) { $contador++; continue; }
        if ($hasta !== null && $contador > $hasta) { break; }
        $extintores_filtrados[] = $ext;
        $contador++;
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, utf8_decode('Etiquetas QR - ' . $empresa['nombre_comercial']), 0, 1, 'C');
    
    if ($desde !== null || $hasta !== null) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, utf8_decode("Mostrando del extintor #$desde al #$hasta"), 0, 1, 'C');
    }
    $pdf->Ln(10);

    $col = 0; 
    $y = $pdf->GetY();
    
    foreach ($extintores_filtrados as $ext) {
        $x = 10 + ($col * 65); // Posición horizontal (3 columnas)

        // 1. Dibuja el marco de la etiqueta
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect($x, $y, 58, 65, 'D'); // Cuadro de 58mm x 65mm
        $pdf->SetDrawColor(0, 0, 0);
        
        // 2. MAGIA: Generar el Código QR usando una API súper rápida
        $qr_url = "https://quickchart.io/qr?text=" . urlencode($ext['codigo_qr']) . "&size=150&margin=0";
        // Pegar la imagen en el PDF
        $pdf->Image($qr_url, $x + 11.5, $y + 5, 35, 35, 'PNG');
        
        // 3. Título del QR (Ej: BIM-001)
        $pdf->SetXY($x, $y + 45);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(58, 6, utf8_decode($ext['codigo_qr']), 0, 1, 'C');
        
        // 4. Subtítulo (Ej: PQS - 4.5kg)
        $pdf->SetX($x);
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(58, 5, utf8_decode(strtoupper($ext['tipo_agente'] . ' - ' . $ext['capacidad'])), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0); // Regresar a color negro

        // Lógica para saltar a la siguiente columna / fila
        $col++;
        if ($col == 3) { 
            $col = 0; 
            $y += 70; // Espacio hacia abajo
            if ($y > 220) { 
                $pdf->AddPage(); 
                $y = 20; 
            }
        }
    }

    ob_end_clean();
    $pdf->Output('I', 'Etiquetas_QR_' . $empresa_id . '.pdf');

} catch (Exception $e) { ob_end_clean(); die("Error interno al generar PDF."); }
?>
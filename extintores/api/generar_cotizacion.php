<?php
require('fpdf.php');
require('conexion.php');

// Recibimos los parámetros enviados desde el Dashboard
$inspeccion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$precio_unitario = isset($_GET['precio']) ? floatval($_GET['precio']) : 0;

if ($inspeccion_id === 0) {
    die("Error: ID de inspeccion no valido.");
}

try {
    // Extraemos los datos del cliente y los conceptos que el técnico marcó en campo
    $query = "
        SELECT 
            i.*, 
            e.nombre_comercial, e.direccion, 
            ex.codigo_qr, ex.tipo_agente, ex.capacidad
        FROM inspecciones i
        JOIN extintores ex ON i.extintor_id = ex.id
        JOIN empresas e ON ex.empresa_id = e.id
        WHERE i.id = :id
    ";
    $stmt = $conexion->prepare($query);
    $stmt->execute([':id' => $inspeccion_id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) { die("Error: Cotizacion no encontrada."); }

    // Traducir los booleanos de la base de datos a texto para el cliente
    $conceptos = [];
    if($datos['cot_recarga_fg'] == 1) $conceptos[] = "Servicio de Recarga y Presurizacion";
    if($datos['cot_senaletica'] == 1) $conceptos[] = "Suministro de Senaletica NOM";
    if($datos['cot_soporte'] == 1) $conceptos[] = "Soporte de Instalacion (Herraje)";
    if($datos['cot_funda'] == 1) $conceptos[] = "Funda Protectora";
    if($datos['cot_refaccion'] == 1) $conceptos[] = "Refaccion (Manguera/Valvula/Manometro)";

    $total_conceptos = count($conceptos);
    $precio_total = $precio_unitario * $total_conceptos; // Lógica básica, puedes ajustarla luego

    // ==========================================
    // CREACIÓN DEL PDF CORPORATIVO
    // ==========================================
    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    
    // Encabezado
    $pdf->SetFont('Arial','B',20);
    $pdf->SetTextColor(30, 58, 138); // Azul Corporativo (#1e3a8a)
    $pdf->Cell(0, 10, utf8_decode('COTIZACIÓN DE SERVICIO'), 0, 1, 'R');
    
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(0, 5, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
    $pdf->Cell(0, 5, 'Folio QR Asociado: ' . $datos['codigo_qr'], 0, 1, 'R');
    $pdf->Ln(10);

    // Datos del Cliente
    $pdf->SetFont('Arial','B',12);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->Cell(0, 8, utf8_decode('Atención a:'), 0, 1, 'L');
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0, 6, utf8_decode($datos['nombre_comercial']), 0, 1, 'L');
    $pdf->SetFont('Arial','I',10);
    $pdf->Cell(0, 6, utf8_decode($datos['direccion']), 0, 1, 'L');
    $pdf->Ln(10);

    // Contexto del equipo
    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(0, 6, utf8_decode("Derivado de nuestra reciente inspeccion de mantenimiento, se detectaron anomalias en el extintor ({$datos['tipo_agente']} {$datos['capacidad']}) que requieren accion inmediata para cumplir con la NOM-154-SCFI. A continuacion, el desglose:"));
    $pdf->Ln(5);

    // Tabla de Conceptos
    $pdf->SetFillColor(241, 245, 249); // Gris claro
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(140, 10, 'Concepto Requerido', 1, 0, 'L', true);
    $pdf->Cell(50, 10, 'Importe', 1, 1, 'C', true);

    $pdf->SetFont('Arial','',10);
    foreach($conceptos as $concepto) {
        $pdf->Cell(140, 10, utf8_decode($concepto), 1, 0, 'L');
        $pdf->Cell(50, 10, '$' . number_format($precio_unitario, 2), 1, 1, 'C');
    }

    // Total
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(140, 10, 'TOTAL APROXIMADO', 1, 0, 'R');
    $pdf->SetTextColor(16, 185, 129); // Verde Émeralda (#10b981)
    $pdf->Cell(50, 10, '$' . number_format($precio_total, 2), 1, 1, 'C');

    // Pie de página comercial
    $pdf->Ln(20);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->SetFont('Arial','I',9);
    $pdf->MultiCell(0, 5, utf8_decode("Este documento es una cotizacion automatizada. Los precios no incluyen IVA y estan sujetos a confirmacion tras la revision fisica del equipo en nuestro taller."), 0, 'C');

    $pdf->Output('I', 'Cotizacion_'.$datos['codigo_qr'].'.pdf');

} catch (PDOException $e) {
    die("Error de BD: " . $e->getMessage());
}
?>
<?php
// ==========================================
// RIESGOS CERO - GENERADOR DE DICTAMEN (SMART-PRINT HTML)
// ==========================================
require_once 'conexion.php';

$empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : 0;

if ($empresa_id === 0) {
    die("Error: No se identificó la empresa para generar el dictamen.");
}

// ==========================================
// CONFIGURACIÓN DE LOGOS
// Aquí puedes colocar la ruta real de tu logo (Ej: '../assets/img/logo_taller.png')
// ==========================================
$logo_taller = "https://ui-avatars.com/api/?name=Riesgos+Cero&background=10b981&color=fff&size=150&rounded=true";

try {
    // 1. Obtener datos del cliente
    $stmtEmpresa = $conexion->prepare("SELECT nombre_comercial, tiene_poliza FROM empresas WHERE id = :id");
    $stmtEmpresa->execute([':id' => $empresa_id]);
    $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        die("Empresa no encontrada.");
    }

    // Logo del cliente (Por ahora genera uno automático con sus iniciales, 
    // en el futuro podemos leer una columna 'logo_url' de tu BD)
    $logo_cliente = "https://ui-avatars.com/api/?name=" . urlencode($empresa['nombre_comercial']) . "&background=1e3a8a&color=fff&size=150";

    // 2. Consultar el inventario actual (Adaptado a tu BD nueva)
    $query = "
        SELECT 
            codigo_qr,
            tipo_agente,
            capacidad,
            ubicacion_especifica AS nombre_area,
            estatus AS estatus_final_extintor
        FROM extintores
        WHERE empresa_id = :empresa_id
        ORDER BY ubicacion_especifica ASC, codigo_qr ASC
    ";

    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fecha actual para el reporte
    $fecha_hoy = date('d/m/Y');

} catch (PDOException $e) {
    die("Error de BD: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dictamen - <?php echo htmlspecialchars($empresa['nombre_comercial']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #e2e8f0; }
        
        /* ==========================================
           MAGIA DE IMPRESIÓN (SMART-PRINT)
           ========================================== */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background-color: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .salto-pagina { page-break-inside: avoid; }
            .sombra-papel { box-shadow: none !important; margin: 0 !important; border: none !important; }
        }
    </style>
</head>
<body class="py-10 flex justify-center flex-col items-center">

    <!-- BOTÓN FLOTANTE: Se oculta automáticamente al imprimir -->
    <button onclick="window.print()" class="no-print fixed bottom-10 right-10 bg-emerald-600 text-white px-8 py-4 rounded-full font-black shadow-2xl hover:bg-emerald-700 hover:scale-105 transition-transform flex items-center gap-2 z-50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
        IMPRIMIR / GUARDAR PDF
    </button>

    <div class="no-print bg-amber-100 text-amber-800 p-4 rounded-xl font-bold mb-6 max-w-2xl text-center text-sm shadow-sm border border-amber-200">
        💡 Recomendación: Al guardar como PDF, asegúrate de activar la opción <b>"Gráficos de fondo"</b> en la ventana de impresión para conservar los colores y membretes.
    </div>

    <!-- HOJA A4 -->
    <div class="bg-white w-[210mm] min-h-[297mm] p-[15mm] shadow-2xl relative sombra-papel mb-10 border border-slate-300">
        
        <!-- ==========================================
             ENCABEZADO Y LOGOS
             ========================================== -->
        <header class="flex justify-between items-center border-b-[6px] border-[#1e3a8a] pb-6 mb-8">
            <!-- LOGO DEL TALLER -->
            <div class="w-1/3">
                <img src="<?php echo $logo_taller; ?>" alt="Logo Taller" class="h-16 object-contain">
            </div>
            
            <div class="w-1/3 text-center">
                <h1 class="text-xl font-black text-[#1e3a8a] uppercase tracking-widest">Dictamen Técnico</h1>
                <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase">Cumplimiento NOM-154-SCFI-2005</p>
                <p class="text-[10px] font-bold text-slate-500">Fecha de Emisión: <?php echo $fecha_hoy; ?></p>
            </div>
            
            <!-- LOGO DEL CLIENTE -->
            <div class="w-1/3 flex justify-end">
                <img src="<?php echo $logo_cliente; ?>" alt="Logo Cliente" class="h-16 object-contain rounded-lg">
            </div>
        </header>

        <!-- DATOS DEL CLIENTE -->
        <section class="bg-slate-50 p-5 rounded-2xl border border-slate-200 mb-8 flex justify-between items-center">
            <div>
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Razón Social / Instalación</span>
                <span class="font-black text-slate-800 text-lg uppercase"><?php echo htmlspecialchars($empresa['nombre_comercial']); ?></span>
            </div>
            <div class="text-right">
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Estatus del Servicio</span>
                <span class="font-black text-xs px-3 py-1 rounded-full uppercase tracking-wider <?php echo $empresa['tiene_poliza'] ? 'bg-blue-100 text-blue-700' : 'bg-slate-200 text-slate-600'; ?>">
                    <?php echo $empresa['tiene_poliza'] ? 'Póliza Activa' : 'Servicio Independiente'; ?>
                </span>
            </div>
        </section>

        <!-- TABLA DE RESULTADOS DE INSPECCIÓN -->
        <section>
            <h2 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-3">Relación de Equipos y Resultados</h2>
            <table class="w-full text-left text-[11px] border-collapse">
                <thead>
                    <tr class="bg-[#1e3a8a] text-white uppercase tracking-wider">
                        <th class="p-3 font-black rounded-tl-xl border border-[#1e3a8a]">ID / Folio</th>
                        <th class="p-3 font-black border border-[#1e3a8a]">Equipo y Capacidad</th>
                        <th class="p-3 font-black border border-[#1e3a8a]">Ubicación (Área)</th>
                        <th class="p-3 font-black text-center rounded-tr-xl border border-[#1e3a8a]">Dictamen Final</th>
                    </tr>
                </thead>
                <tbody class="border-b-4 border-[#1e3a8a]">
                    <?php if (count($reportes) > 0): ?>
                        <?php foreach ($reportes as $idx => $row): ?>
                            <?php 
                                // Alternar colores de fila
                                $bg = ($idx % 2 === 0) ? 'bg-white' : 'bg-slate-50'; 
                                
                                // Color del dictamen
                                $estatus = $row['estatus_final_extintor'];
                                if ($estatus === 'Aprobado') $color_badge = 'text-emerald-700 bg-emerald-100 border border-emerald-200';
                                elseif ($estatus === 'Rechazado') $color_badge = 'text-red-700 bg-red-100 border border-red-200';
                                else $color_badge = 'text-amber-700 bg-amber-100 border border-amber-200';
                            ?>
                            <tr class="salto-pagina hover:bg-slate-100 transition-colors <?php echo $bg; ?>">
                                <td class="p-3 font-black text-slate-800 border-b border-slate-200 border-l border-l-slate-200"><?php echo htmlspecialchars($row['codigo_qr']); ?></td>
                                <td class="p-3 font-bold text-slate-600 border-b border-slate-200">
                                    <?php echo htmlspecialchars($row['tipo_agente'] . ' ' . $row['capacidad']); ?>
                                </td>
                                <td class="p-3 text-slate-500 font-medium border-b border-slate-200">
                                    <?php echo htmlspecialchars($row['nombre_area'] ?? 'Área General'); ?>
                                </td>
                                <td class="p-3 text-center border-b border-slate-200 border-r border-r-slate-200">
                                    <span class="<?php echo $color_badge; ?> px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider block mx-auto w-fit">
                                        <?php echo htmlspecialchars($estatus); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400 font-bold border-b border-l border-r border-slate-200 bg-slate-50">
                                No se encontraron inspecciones recientes para este cliente.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- ZONA DE FIRMAS (Al final del reporte) -->
        <section class="mt-20 grid grid-cols-2 gap-10 text-center salto-pagina">
            <div>
                <div class="border-b-2 border-slate-400 w-3/4 mx-auto mb-2 h-16"></div>
                <p class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Firma del Técnico</p>
                <p class="text-[10px] font-bold text-slate-500 mt-1">Riesgos Cero - Taller Autorizado</p>
            </div>
            <div>
                <div class="border-b-2 border-slate-400 w-3/4 mx-auto mb-2 h-16"></div>
                <p class="text-[11px] font-black text-slate-800 uppercase tracking-widest">Firma de Conformidad</p>
                <p class="text-[10px] font-bold text-slate-500 mt-1"><?php echo htmlspecialchars($empresa['nombre_comercial']); ?></p>
            </div>
        </section>

        <!-- PIE DE PÁGINA (Footer pegado al fondo) -->
        <footer class="absolute bottom-[15mm] left-[15mm] right-[15mm] border-t-2 border-slate-200 pt-4 text-center">
            <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
                Este documento certifica la revisión técnica de los equipos listados conforme a la NOM-154-SCFI-2005.<br>
                Las anomalías marcadas como "Rechazado" requieren mantenimiento inmediato para garantizar la seguridad del inmueble.
            </p>
        </footer>

    </div>
</body>
</html>
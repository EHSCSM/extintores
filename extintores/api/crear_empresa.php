// ==============================
        // EPIC 4: MINERÍA DE DATOS Y ALERTAS (Inteligencia de Pólizas)
        // ==============================
        async function cargarAlertas() {
            try {
                const res = await fetch(`../api/obtener_alertas.php?taller_id=${tallerId}`);
                const data = await res.json();
                const tbody = document.getElementById('tabla-alertas');
                
                if (data.error || data.datos.length === 0) {
                    tbody.innerHTML = '<tr><td class="p-6 text-center text-emerald-600 font-black"><i class="ph-bold ph-shield-check text-2xl mb-1 block"></i> ¡Todo al día! Ningún equipo caduca pronto.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = data.datos.map(alerta => {
                    let badgeFecha = alerta.dias_restantes < 0 
                        ? `<span class="bg-red-600 text-white px-2 py-1 rounded-md text-[9px] font-black uppercase shadow-sm">Vencido hace ${Math.abs(alerta.dias_restantes)} días</span>`
                        : `<span class="bg-amber-500 text-white px-2 py-1 rounded-md text-[9px] font-black uppercase shadow-sm">Vence en ${alerta.dias_restantes} días</span>`;
                    
                    // LÓGICA DE NEGOCIO: ¿Tiene póliza o no?
                    let botonAccion = '';
                    let badgePoliza = '';
                    
                    if (alerta.tiene_poliza == 1) {
                        badgePoliza = `<span class="text-[9px] font-black text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full"><i class="ph-fill ph-file-text"></i> Póliza Activa</span>`;
                        // Botón para agendar directo (te lleva a la pestaña de rutas y selecciona la empresa)
                        botonAccion = `
                            <button onclick="prepararRutaParaMantenimiento(${alerta.empresa_id})" class="bg-blue-600 text-white px-3 py-2 rounded-xl font-bold hover:bg-blue-700 shadow-sm text-[10px] uppercase flex items-center gap-1 mx-auto transition-transform active:scale-95">
                                <i class="ph-bold ph-map-trifold text-sm"></i> Agendar Ruta
                            </button>`;
                    } else {
                        badgePoliza = `<span class="text-[9px] font-black text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full"><i class="ph-bold ph-phone-call"></i> Venta Externa</span>`;
                        botonAccion = `
                            <button onclick="alert('Funcionalidad para enviar WhatsApp de venta a ${alerta.cliente} en desarrollo.')" class="bg-slate-900 text-white px-3 py-2 rounded-xl font-bold hover:bg-slate-800 shadow-sm text-[10px] uppercase flex items-center gap-1 mx-auto transition-transform active:scale-95">
                                <i class="ph-bold ph-phone-call text-sm"></i> Contactar
                            </button>`;
                    }
                        
                    return `
                    <tr class="hover:bg-red-50/50 transition-colors border-b border-red-50">
                        <td class="p-3">
                            <span class="font-black text-slate-800 text-sm">${alerta.cliente}</span> ${badgePoliza}<br>
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1 inline-block">${alerta.codigo_qr} | ${alerta.tipo_agente} ${alerta.capacidad}</span>
                        </td>
                        <td class="p-3 text-right">
                            ${badgeFecha}<br>
                            <span class="text-[9px] text-slate-400 font-bold mt-1 block">Vencimiento: ${alerta.proxima_recarga}</span>
                        </td>
                        <td class="p-3 text-center">
                            ${botonAccion}
                        </td>
                    </tr>`;
                }).join('');
            } catch (err) { console.error(err); }
        }

        // Esta función "brinca" al usuario a la pestaña de rutas y le pre-selecciona el cliente
        function prepararRutaParaMantenimiento(empresaId) {
            // 1. Simulamos el clic en el menú lateral para abrir la pestaña de Rutas
            const botonRutas = Array.from(document.querySelectorAll('.menu-btn')).find(btn => btn.innerText.includes('Despacho'));
            if(botonRutas) cambiarSeccion('sec-rutas', botonRutas);
            
            // 2. Pre-seleccionamos al cliente en el formulario
            const selectEmpresa = document.getElementById('ruta-empresa');
            if(selectEmpresa) {
                selectEmpresa.value = empresaId;
            }
        }
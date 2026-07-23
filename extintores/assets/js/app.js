/**
 * ============================================================================
 * RIESGOS CERO - CORE JAVASCRIPT PWA (VERSIÓN 1.0 - ARQUITECTURA LIMPIA)
 * ============================================================================
 */
 

// Ejecutar la verificación en cuanto la App Móvil cargue la pantalla principal
document.addEventListener('DOMContentLoaded', () => {
    // Solo verificar si estamos en la pantalla del técnico y ya inició sesión
    if(localStorage.getItem('usuario_rol') === 'tecnico') {
        verificarRutaHoy();
    }
});

// 1. RUTA DINÁMICA ABSOLUTA: Detecta tu dominio automáticamente (Anti-CORS)
const API_URL = window.location.origin + "/extintores/api";

let html5QrCode = null;
let canvas, ctx;
let dibujando = false;
let firmaRealizada = false;

document.addEventListener("DOMContentLoaded", () => {
    setTimeout(inicializarCanvas, 500);
});

// ==========================================
// MÓDULO 1: LOGIN Y SESIÓN
// ==========================================
async function ejecutarLogin() {
    const emailInput = document.getElementById('login-email').value.trim();
    const passwordInput = document.getElementById('login-pass').value.trim();
    const btn = document.getElementById('btn-login');
    const mensajeTexto = document.getElementById('mensaje-login'); 

    if (!emailInput || !passwordInput) {
        mensajeTexto.innerText = "Llena todos los campos."; mensajeTexto.style.color = "red"; return;
    }

    const txtOriginal = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Validando...';
    mensajeTexto.innerText = "Conectando..."; mensajeTexto.style.color = "#1e3a8a";

    try {
        const respuesta = await fetch(`${API_URL}/login.php`, {
            method: "POST", headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email: emailInput, password: passwordInput })
        });
        
        const textoRaw = await respuesta.text(); 
        
        try {
            const resultado = JSON.parse(textoRaw);
            if (resultado.error) {
                mensajeTexto.innerText = resultado.mensaje; mensajeTexto.style.color = "red";
                btn.disabled = false; btn.innerHTML = txtOriginal;
            } else {
                mensajeTexto.innerText = "¡Acceso correcto!"; mensajeTexto.style.color = "#10b981";
                
                // Guardar Sesión en el celular/computadora
                localStorage.clear();
                localStorage.setItem("usuario_id", resultado.usuario.id);
                localStorage.setItem("empresa_id", resultado.usuario.empresa_id);
                localStorage.setItem("usuario_nombre", resultado.usuario.nombre);
                localStorage.setItem("usuario_rol", resultado.usuario.rol);

                // ==========================================================
                // ENRUTADOR CORREGIDO (Elimina inspecciones.html por completo)
                // ==========================================================
                setTimeout(() => {
                    if (resultado.usuario.rol === "tecnico") {
                        document.getElementById('pantalla-login').classList.add('hidden');
                        document.getElementById('vista-inspeccion').classList.remove('hidden');
                        document.getElementById('nav-main').classList.remove('hidden'); 
                    } else if (resultado.usuario.rol === "admin_empresa") {
                        // RUTA CORRECTA: Te envía directo al dashboard unificado de administración
                        window.location.href = "./admin/dashboard.html"; 
                    } else if (resultado.usuario.rol === "super_admin") {
                        // RUTA CORRECTA: Te envía al panel global de super usuario
                        window.location.href = "./superadmin/dashboard.html";
                    }
                }, 500);
            }
        } catch(e) {
            mensajeTexto.innerText = "Error PHP: " + textoRaw.substring(0,40);
            mensajeTexto.style.color = "red"; btn.disabled = false; btn.innerHTML = txtOriginal;
        }
    } catch (error) {
        mensajeTexto.innerText = "Fallo de conexión: " + error.message; 
        mensajeTexto.style.color = "red"; btn.disabled = false; btn.innerHTML = txtOriginal;
    }
}

function cerrarSesion() {
    if(confirm("¿Seguro que deseas salir?")) {
        localStorage.clear();
        location.reload(); 
    }
}

// ==========================================
// MÓDULO 2: NAVEGACIÓN SPA (Single Page App)
// ==========================================
function cambiarVista(idVista) {
    document.querySelectorAll('.vista-app').forEach(v => v.classList.add('hidden'));
    document.getElementById(idVista).classList.remove('hidden');
    
    if (idVista === 'vista-inventario') {
        cargarInventario();
    } else {
        pararEscaneo();
    }
}

// ==========================================
// MÓDULO 3: ESCÁNER QR
// ==========================================
function iniciarEscaneo() {
    const modalEscaner = document.getElementById('modal-escaner');
    modalEscaner.classList.remove('hidden');
    if (html5QrCode) {
        html5QrCode.stop().then(() => { html5QrCode = null; arrancarCamara(); }).catch(() => { html5QrCode = null; arrancarCamara(); });
    } else {
        arrancarCamara();
    }
}

function arrancarCamara() {
    html5QrCode = new Html5Qrcode("reader-nativo");
    html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } }, 
        (codigo) => { 
            if (navigator.vibrate) navigator.vibrate(100);
            pararEscaneo();
            document.getElementById('inputCodigo').value = codigo.trim();
            buscarExtintor();
        }, 
        (error) => {}
    ).catch(err => { alert("🔒 Cámara bloqueada. Use HTTPS."); pararEscaneo(); });
}

function pararEscaneo() {
    document.getElementById('modal-escaner').classList.add('hidden');
    if (html5QrCode) {
        html5QrCode.stop().then(() => { html5QrCode.clear(); html5QrCode = null; }).catch(() => { html5QrCode = null; });
    }
}


// ==========================================
// BUSCAR EXTINTOR (MODO RAYOS X) CON VALIDACIÓN DE RUTA (EPIC 1)
// ==========================================
let empresaAsignadaHoyId = null; // Variable Global

async function buscarExtintor(qrEscaneado = null) {
    const codigo = qrEscaneado || document.getElementById('inputCodigo').value.trim();
    const msj = document.getElementById('mensajeBusqueda');

    if (!codigo) {
        msj.innerText = "⚠️ Ingresa o escanea un folio válido.";
        msj.style.color = "#dc2626";
        return;
    }

    msj.innerText = "Buscando equipo en la nube...";
    msj.style.color = "#1e3a8a";

    try {
        const url = `${API_URL}/obtener_extintor.php?qr=${codigo}`;
        const respuesta = await fetch(url);
        
        if (!respuesta.ok) {
            throw new Error(`Fallo HTTP: ${respuesta.status}`);
        }

        const textoCrudo = await respuesta.text();
        
        let data;
        try {
            data = JSON.parse(textoCrudo);
        } catch (e) {
            throw new Error("Error del servidor.");
        }

        if (data.error) {
            msj.innerText = "❌ " + data.mensaje;
            msj.style.color = "#dc2626";
            document.getElementById('seccion-formulario').classList.add('hidden');
        } else {
            
            // ==========================================
            // EPIC 1: BLOQUEO DE RUTA (CEGUERA INTENCIONAL)
            // ==========================================
            if (empresaAsignadaHoyId !== null && data.extintor.empresa_id != empresaAsignadaHoyId) {
                alert(`❌ ALERTA DE RUTA INCORRECTA:\nEste extintor no pertenece a tu cliente asignado para hoy.\nPor favor, verifica el folio o solicita permiso a tu Administrador.`);
                msj.innerText = "Equipo bloqueado por Ruta.";
                msj.style.color = "#dc2626";
                document.getElementById('seccion-formulario').classList.add('hidden');
                return; // Bloquea la continuación
            }
            
            msj.innerText = "✅ Equipo localizado.";
            msj.style.color = "#10b981";
            
            document.getElementById('seccion-formulario').classList.remove('hidden');
            document.getElementById('extintorId').value = data.extintor.id;
            document.getElementById('lbl-id-auditoria').innerText = "FOLIO: " + data.extintor.codigo_qr;
            document.getElementById('infoExtintor').innerText = `${data.extintor.tipo_agente} - ${data.extintor.capacidad}`;
            
            // ACTUALIZACIÓN DE LOGÍSTICA
            if (data.extintor.empresa_id) {
                fetch(`${API_URL}/obtener_total_extintores.php?empresa_id=${data.extintor.empresa_id}`)
                    .then(res => res.json())
                    .then(totalData => {
                        if(!totalData.error) {
                            document.getElementById('txt-totales').innerText = totalData.total;
                            document.getElementById('txt-auditados').innerText = totalData.auditados;
                        }
                    })
                    .catch(err => console.error("Error al obtener totales", err));
            }

            // Memoria
            document.getElementById('insp_fabricacion').value = data.extintor.ano_fabricacion || "";
            document.getElementById('insp_recarga').value = data.extintor.ultima_recarga || "";

            if (typeof calcularVigencias === 'function') calcularVigencias();
        }
    } catch (error) {
        console.error(error);
        msj.innerText = "🛑 " + error.message;
        msj.style.color = "#dc2626";
    }
}c
// ==========================================
// MÓDULO 5: FIRMA DIGITAL
// ==========================================
function inicializarCanvas() {
    canvas = document.getElementById('canvasFirma'); if(!canvas) return;
    ctx = canvas.getContext('2d'); const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width || 300; canvas.height = 128;
    ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.strokeStyle = '#0f172a';
    
    canvas.addEventListener('mousedown', iniciarDibujo); canvas.addEventListener('mousemove', dibujar); canvas.addEventListener('mouseup', detenerDibujo);
    canvas.addEventListener('touchstart', (e) => { e.preventDefault(); iniciarDibujo(e.touches[0]); }, {passive: false});
    canvas.addEventListener('touchmove', (e) => { e.preventDefault(); dibujar(e.touches[0]); }, {passive: false});
    canvas.addEventListener('touchend', (e) => { e.preventDefault(); detenerDibujo(); }, {passive: false});
}
function iniciarDibujo(e) { dibujando = true; firmaRealizada = true; ctx.beginPath(); const rect = canvas.getBoundingClientRect(); ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top); }
function dibujar(e) { if (!dibujando) return; const rect = canvas.getBoundingClientRect(); ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top); ctx.stroke(); }
function detenerDibujo() { dibujando = false; ctx.beginPath(); }
function limpiarFirma() { if(!ctx) return; ctx.clearRect(0, 0, canvas.width, canvas.height); firmaRealizada = false; }

// ==========================================
// MÓDULO 6: GUARDAR INSPECCIÓN (MAPEADO COMPLETO)
// ==========================================
// ==========================================
// MOTOR DE GUARDADO B2B - BLINDADO CONTRA ERRORES
// ==========================================
async function guardarChecklist() {
    const btn = document.getElementById('btnGuardar');
    const msj = document.getElementById('mensajeGuardado');
    const txtOriginal = btn.innerHTML;

    try {
        // 1. Bloqueamos el botón y avisamos al usuario
        btn.disabled = true;
        btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin text-xl"></i> PROCESANDO...';
        msj.innerText = "Empaquetando datos...";
        msj.style.color = "#1e3a8a"; // Azul

        // 2. Función interna de seguridad para leer HTML
        const getVal = (id) => {
            const el = document.getElementById(id);
            if (!el) throw new Error(`Falta el elemento HTML con el ID exacto: "${id}"`);
            return el.value;
        };
        const getCheck = (id) => {
            const el = document.getElementById(id);
            if (!el) throw new Error(`Falta la casilla de verificación (checkbox) con ID: "${id}"`);
            return el.checked ? 1 : 0;
        };

        // 3. Validar fechas obligatorias
        const fab = getVal('insp_fabricacion');
        const rec = getVal('insp_recarga');
        if (!fab || !rec) {
            throw new Error("⚠️ Completa el Año de Fabricación y la Última Recarga.");
        }

        // 4. Capturar la Firma
        const canvas = document.getElementById('canvasFirma');
        if (!canvas) throw new Error("No se encontró el lienzo de la firma digital.");
        const firmaBase64 = canvas.toDataURL("image/png");

        // 5. Construir el paquete de datos
        const payload = {
            extintor_id: getVal('extintorId'),
            usuario_id: localStorage.getItem('usuario_id'),
            estatus_final: getVal('estatusFinal'),
            
            ano_fabricacion: fab,
            ultima_recarga: rec,
            
            chk_cilindro: getVal('chk_cilindro'),
            chk_valvula: getVal('chk_valvula'),
            chk_presion: getVal('chk_presion'),
            chk_manometro: getVal('chk_manometro'),
            chk_marchamo: getVal('chk_marchamo'),
            chk_manguera: getVal('chk_manguera'),
            chk_difusor: getVal('chk_difusor'),
            chk_senalamiento: getVal('chk_senalamiento'),
            chk_etiquetas: getVal('chk_etiquetas'),
            chk_gancho: getVal('chk_gancho'),
            chk_cubierta: getVal('chk_cubierta'),
            chk_obstruido: getVal('chk_obstruido'),
            chk_garantia: getVal('chk_garantia'),
            cot_recarga_fg: getCheck('cot_recarga_fg'),
            cot_senaletica: getCheck('cot_senaletica'),
            cot_soporte: getCheck('cot_soporte'),
            cot_funda: getCheck('cot_funda'),
            cot_refaccion: getCheck('cot_refaccion'),
            
            altura_correcta: getCheck('altura_correcta'),
            libre_acceso: getCheck('libre_acceso'),
            observaciones: getVal('insp_observaciones'),
            firma: firmaBase64
        };

        msj.innerText = "Enviando a la nube...";

        // 6. Enviar a PHP
        const respuesta = await fetch(`${API_URL}/guardar_inspeccion.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        // 7. Leer la respuesta cruda para detectar si PHP se rompió
        const textoRaw = await respuesta.text();
        let data;
        try {
            data = JSON.parse(textoRaw);
        } catch (e) {
            throw new Error("Error interno del servidor PHP: " + textoRaw.substring(0, 60));
        }

        // 8. Evaluar el resultado final
        if (data.error) {
            throw new Error(data.mensaje); // Error reportado por PHP (Ej. Faltan columnas SQL)
        } else {
            msj.innerText = "✅ " + data.mensaje; 
            // DISPARO LOGÍSTICO EN VIVO
            const folioDetectado = document.getElementById('lbl-id-auditoria').innerText.replace("FOLIO: ", "");
            const estatusDetectado = document.getElementById('estatusFinal').value;
            const ubicacionDetectada = document.getElementById('infoExtintor').innerText;
            
            if (typeof actualizarMetricasLogistica === 'function') {
                actualizarMetricasLogistica(folioDetectado, estatusDetectado, ubicacionDetectada);
            }
            msj.style.color = "#10b981"; // Verde Éxito
            
            // Cerrar pestaña y limpiar
            setTimeout(() => {
                document.getElementById('seccion-formulario').classList.add('hidden');
                document.getElementById('formChecklist').reset();
                if(typeof limpiarFirma === 'function') limpiarFirma();
                window.scrollTo(0, 0); 
            }, 2500);
        }

    } catch (error) {
        // SI ALGO FALLA, CAYÓ EN ESTA RED DE SEGURIDAD
        console.error(error);
        msj.innerText = error.message; 
        msj.style.color = "#dc2626"; // Rojo Alerta
    } finally {
        // PASE LO QUE PASE, DESBLOQUEAMOS EL BOTÓN
        btn.disabled = false; 
        btn.innerHTML = txtOriginal;
    }
}

// ==========================================
// MÓDULO 7: INVENTARIO FÍSICO
// ==========================================
async function cargarInventario() {
    const contenedor = document.getElementById('lista-inventario');
    const empresaId = localStorage.getItem('empresa_id'); 
    if (!empresaId) return;

    contenedor.innerHTML = '<div class="skeleton w-full h-20 rounded-xl"></div><div class="skeleton w-full h-20 rounded-xl"></div>';

    try {
        const respuesta = await fetch(`${API_URL}/obtener_inventario.php?empresa_id=${empresaId}`);
        const data = await respuesta.json();
        contenedor.innerHTML = ''; 

        if (data.error || data.datos.length === 0) {
            contenedor.innerHTML = `<p class="text-center text-sm font-bold text-slate-400 py-6">No hay activos asignados.</p>`; return;
        }

        data.datos.forEach(ext => {
            const colorIcono = ext.estatus === 'Operativo' ? 'text-emerald-500' : 'text-red-500';
            const bgBadge = ext.estatus === 'Operativo' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700';

            contenedor.innerHTML += `
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 flex justify-between items-center shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center border border-slate-100"><i class="ph-fill ph-fire-extinguisher text-2xl ${colorIcono}"></i></div>
                        <div>
                            <p class="font-black text-slate-800 text-sm tracking-tight">${ext.codigo_qr}</p>
                            <p class="text-[10px] font-bold text-slate-500 uppercase">${ext.tipo_agente} - ${ext.capacidad}</p>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end gap-2">
                        <span class="${bgBadge} px-2 py-1 rounded text-[9px] font-black uppercase tracking-wider">${ext.estatus}</span>
                        <button onclick="auditarDesdeInventario('${ext.codigo_qr}')" class="text-[10px] font-bold text-[#1e3a8a] bg-blue-50 px-2 py-1 rounded-md border border-blue-100 flex items-center gap-1">
                            <i class="ph-bold ph-pencil-simple"></i> Auditar
                        </button>
                    </div>
                </div>
            `;
        });
    } catch (error) { contenedor.innerHTML = `<p class="text-center text-xs text-red-500">Error de conexión.</p>`; }
}

function auditarDesdeInventario(codigoQR) {
    cambiarVista('vista-inspeccion');
    document.getElementById('inputCodigo').value = codigoQR;
    buscarExtintor();
}
// ==========================================
// CALCULADORA DE VIGENCIA EN TIEMPO REAL
// ==========================================
function calcularVigencias() {
    const inputFab = document.getElementById('insp_fabricacion').value;
    const inputRec = document.getElementById('insp_recarga').value;
    const alertaDiv = document.getElementById('alerta-vigencia');
    const alertaTexto = document.getElementById('texto-alerta-vigencia');
    
    let alertas = [];
    const hoy = new Date();
    const añoActual = hoy.getFullYear();

    // 1. REGLA DE PRUEBA HIDROSTÁTICA (PH) Y OBSOLESCENCIA
    if (inputFab) {
        const fab = parseInt(inputFab);
        const añosUso = añoActual - fab;
        
        if (añosUso >= 20) {
            alertas.push("⛔ EQUIPO OBSOLETO: Ha superado los 20 años de vida útil permitidos por la norma.");
        } else if (añosUso > 0 && añosUso % 5 === 0) {
            alertas.push("⚠️ REQUIERE P.H.: Este año le corresponde Prueba Hidrostática (Múltiplo de 5 años).");
        }
    }

    // 2. REGLA DE RECARGA ANUAL
    if (inputRec) {
        // Asumimos el día 1 del mes para el cálculo (Formato YYYY-MM)
        const fechaRecarga = new Date(inputRec + "-01T00:00:00");
        // Le sumamos 1 año exacto a la última recarga
        const vencimientoRecarga = new Date(fechaRecarga.setFullYear(fechaRecarga.getFullYear() + 1));
        
        if (hoy > vencimientoRecarga) {
            alertas.push("⚠️ RECARGA VENCIDA: Ha pasado más de 1 año desde el último mantenimiento.");
        }
    }

    // 3. MOSTRAR U OCULTAR ALERTA
    if (alertas.length > 0) {
        alertaTexto.innerHTML = alertas.join('<br><br>');
        alertaDiv.classList.remove('hidden');
        // Opcional: Marcar el dictamen como "Condicionado" o "Rechazado" automáticamente
        document.getElementById('estatusFinal').value = "Condicionado";
    } else {
        alertaDiv.classList.add('hidden');
        document.getElementById('estatusFinal').value = "Aprobado";
    }
}

// Escuchar cambios en vivo cuando el técnico teclea
document.getElementById('insp_fabricacion').addEventListener('input', calcularVigencias);
document.getElementById('insp_recarga').addEventListener('change', calcularVigencias);

// Arreglo temporal en memoria para rastrear qué extintores van para el taller
let extintoresParaTaller = [];

// Funciones para controlar la ventana flotante de retiros
function abrirModalRetiros() {
    const contenedor = document.getElementById('lista-para-retiro');
    if (extintoresParaTaller.length === 0) {
        contenedor.innerHTML = '<div class="p-6 text-center text-slate-400 font-medium">Ningún equipo requiere retiro hasta ahora. ¡Buen trabajo!</div>';
    } else {
        contenedor.innerHTML = '';
        extintoresParaTaller.forEach(eq => {
            contenedor.innerHTML += `
                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex justify-between items-center">
                    <div>
                        <p class="font-black text-amber-900">${eq.qr}</p>
                        <p class="text-[10px] text-amber-700">${eq.ubicacion}</p>
                    </div>
                    <span class="bg-amber-500 text-slate-900 text-[9px] font-black px-2 py-0.5 rounded uppercase">${eq.motivo}</span>
                </div>
            `;
        });
    }
    document.getElementById('modal-retiros').classList.remove('hidden');
}

function cerrarModalRetiros() {
    document.getElementById('modal-retiros').classList.add('hidden');
}

// LLAMAR ESTO DENTRO DE TU FUNCIÓN ACTUAL DE CONEXIÓN EXITOSA AL GUARDAR
function actualizarMetricasLogistica(folioQR, estatus, ubicacion) {
    // 1. Aumentar el contador visual de auditados
    const totalActual = parseInt(document.getElementById('txt-auditados').innerText);
    document.getElementById('txt-auditados').innerText = totalActual + 1;
    
    // 2. Si el dictamen no fue Aprobado, va directo a la lista de la camioneta
    if (estatus !== "Aprobado") {
        extintoresParaTaller.push({
            qr: folioQR,
            ubicacion: ubicacion || "Ubicación General",
            motivo: estatus === "Rechazado" ? "BAJA / PH" : "MANTENIMIENTO"
        });
        
        // Actualizar el globo indicador
        document.getElementById('txt-retirar').innerText = extintoresParaTaller.length;
    }
}

// ==========================================
// EPIC 1: VISIÓN DE TÚNEL Y VALIDACIÓN
// ==========================================
async function verificarRutaHoy() {
    const tecnicoId = localStorage.getItem('usuario_id'); // ID del técnico logueado
    if (!tecnicoId) return;

    try {
        // Reemplaza el fetch() dentro de verificarRutaHoy con este:
const res = await fetch(`${API_URL}/obtener_ruta_hoy.php?tecnico_id=${tecnicoId}`);
        const data = await res.json();

        if (!data.error && data.hay_ruta) {
            // Si tiene ruta: Guardamos la empresa y mostramos la tarjeta azul
            empresaAsignadaHoyId = data.datos.empresa_id;
            document.getElementById('tarjeta-mision').classList.remove('hidden');
            document.getElementById('mision-cliente').innerText = data.datos.nombre_comercial;

            const opcionesFecha = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('mision-fecha').innerText = new Date().toLocaleDateString('es-MX', opcionesFecha);
        } else {
            // Si no tiene ruta: Mostramos la tarjeta gris y bloqueamos botón de escanear (opcional)
            document.getElementById('tarjeta-sin-ruta').classList.remove('hidden');
            // document.getElementById('btn-escanear').disabled = true; // Descomenta esto si quieres bloquear el botón físico
        }
    } catch (error) {
        console.error("Error al buscar la ruta de hoy:", error);
    }
}

// Disparar la verificación cuando la App inicie
document.addEventListener("DOMContentLoaded", verificarRutaHoy);
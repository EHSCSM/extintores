// ==========================================
// CONTROLADOR ADMIN: REGISTRO DE TÉCNICOS
// ==========================================
async function registrarTecnico() {
    const msj = document.getElementById('msjTecnico');
    const payload = {
        nombre: document.getElementById('tec_nombre').value.trim(),
        correo: document.getElementById('tec_correo').value.trim(),
        contrasena: document.getElementById('tec_password').value
    };

    try {
        const res = await fetch('api/crear_tecnico.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if(data.error) {
            msj.innerText = data.mensaje; msj.style.color = "#dc2626";
        } else {
            msj.innerText = "✅ Técnico registrado con éxito."; msj.style.color = "#10b981";
            document.getElementById('formAltaTecnico').reset();
            cargarListasAdmin(); // Recargar tablas en vivo
        }
    } catch (e) {
        msj.innerText = "Error al conectar con la API de usuarios."; msj.style.color = "#dc2626";
    }
}

// ==========================================
// CONTROLADOR ADMIN: REGISTRO DE EMPRESAS
// ==========================================
async function registrarEmpresa() {
    const msj = document.getElementById('msjEmpresa');
    const payload = {
        nombre: document.getElementById('emp_nombre').value.trim(),
        razon_social: document.getElementById('emp_razon').value.trim(),
        rfc: document.getElementById('emp_rfc').value.trim(),
        direccion: document.getElementById('emp_direccion').value.trim()
    };

    try {
        const res = await fetch('api/crear_empresa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if(data.error) {
            msj.innerText = data.mensaje; msj.style.color = "#dc2626";
        } else {
            msj.innerText = "✅ Cliente dado de alta."; msj.style.color = "#10b981";
            document.getElementById('formAltaEmpresa').reset();
            cargarListasAdmin(); // Recargar tablas en vivo
        }
    } catch (e) {
        msj.innerText = "Error al conectar con la API de empresas."; msj.style.color = "#dc2626";
    }
}

// Cargar catálogos en las tablas en tiempo real
async function cargarListasAdmin() {
    try {
        const res = await fetch('api/obtener_catalogos.php');
        const data = await res.json();
        
        if(!data.error) {
            // Pintar Técnicos
            const tbodyTec = document.getElementById('tabla-tecnicos-admin');
            tbodyTec.innerHTML = data.tecnicos.map(t => `<tr class="border-b border-slate-100 hover:bg-white"><td class="p-2 font-bold text-slate-800">${t.nombre}</td><td class="p-2 text-slate-500">${t.correo}</td></tr>`).join('');
            
            // Pintar Empresas
            const tbodyEmp = document.getElementById('tabla-empresas-admin');
            tbodyEmp.innerHTML = data.empresas.map(e => `<tr class="border-b border-slate-100 hover:bg-white"><td class="p-2 font-bold text-slate-800">${e.nombre_comercial}</td><td class="p-2 text-slate-500">${e.direccion || 'Sin dirección'}</td></tr>`).join('');
        }
    } catch(err) { console.error("Error al poblar catálogos administrativos", err); }
}

// Disparar la carga inicial al abrir el panel
document.addEventListener("DOMContentLoaded", cargarListasAdmin);
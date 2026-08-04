/**
 * ============================================================================
 * RIESGOS CERO - CORE JAVASCRIPT (LOGIN BLINDADO Y ENRUTADOR MAESTRO)
 * ============================================================================
 */

// DICCIONARIO DE RUTAS (Mucho más limpio y escalable)
const RUTAS_POR_ROL = {
    'superadmin': './superadmin/dashboard.html',
    'super_admin': './superadmin/dashboard.html',
    'admin_taller': './admin/dashboard.html',
    'admin_empresa': './admin/dashboard.html',
    'cliente': './cliente/dashboard.html',
    'tecnico': './app_tecnico.html'
};

document.addEventListener('DOMContentLoaded', () => {
    verificarSesionActiva();

    // Protegemos el formulario para que funcione con la tecla 'Enter' y evite doble-clic
    const formLogin = document.getElementById('form-login');
    if(formLogin) {
        formLogin.addEventListener('submit', (e) => {
            e.preventDefault(); // Evita que la página recargue
            ejecutarLogin();
        });
    }
});

async function verificarSesionActiva() {
    try {
        // Le preguntamos al servidor de forma segura si el usuario ya ingresó antes
        const res = await fetch('api/verificar_sesion.php');
        const data = await res.json();
        
        if (data.activa && RUTAS_POR_ROL[data.rol]) {
            window.location.replace(RUTAS_POR_ROL[data.rol]);
        }
    } catch (e) {
        console.error("Error al verificar la sesión de seguridad.");
    }
}

async function ejecutarLogin() {
    const emailInput = document.getElementById('login-email').value.trim();
    const passwordInput = document.getElementById('login-pass').value.trim();
    const btn = document.getElementById('btn-login');
    const mensajeTexto = document.getElementById('mensaje-login');

    if (!emailInput || !passwordInput) {
        mensajeTexto.innerText = "Llena todos los campos."; 
        mensajeTexto.style.color = "red"; 
        return;
    }

    const txtOriginal = btn.innerHTML;
    btn.disabled = true; 
    btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Validando...';
    mensajeTexto.innerText = "Estableciendo conexión segura..."; 
    mensajeTexto.style.color = "#1e3a8a";

    try {
        const respuesta = await fetch(`api/login.php`, {
            method: "POST", 
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email: emailInput, password: passwordInput })
        });
        
        const resultado = await respuesta.json();

        if (resultado.error) {
            mensajeTexto.innerText = resultado.mensaje; 
            mensajeTexto.style.color = "red";
            btn.disabled = false; 
            btn.innerHTML = txtOriginal;
        } else {
            mensajeTexto.innerText = "¡Acceso Autorizado!"; 
            mensajeTexto.style.color = "#10b981";
            
            // OJO: Ya no guardamos el ROL ni el ID en localStorage. 
            // Solo guardamos el nombre para mostrarlo visualmente en la interfaz ("Hola, Juan").
            localStorage.setItem("usuario_nombre", resultado.nombre);
            
            // El servidor ya sabe quiénes somos, le pedimos que nos enrute
            setTimeout(() => { verificarSesionActiva(); }, 800);
        }
    } catch (error) {
        mensajeTexto.innerText = "Error de red. Revisa tu conexión.";
        mensajeTexto.style.color = "red"; 
        btn.disabled = false; 
        btn.innerHTML = txtOriginal;
    }
}
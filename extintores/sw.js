// ============================================================================
// RIESGOS CERO - SERVICE WORKER (CORE OFFLINE V1.0)
// ============================================================================

const CACHE_NAME = 'riesgos-cero-cache-v1';

// Archivos estáticos indispensables para que la interfaz cargue sin internet
const ASSETS_TO_CACHE = [
  './index.html',
  './assets/css/estilos.css?v=1.0',
  './assets/js/app.js?v=1.2',
  './manifest.json',
  './assets/img/icon-512.png',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap',
  'https://cdn.tailwindcss.com',
  'https://unpkg.com/@phosphor-icons/web',
  'https://unpkg.com/html5-qrcode'
];

// 1. Evento de Instalación: Guarda la interfaz en el disco duro del celular
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// 2. Evento de Activación: Limpia cachés viejos si se actualiza el sistema
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// 3. Estrategia de Red: Intercepta peticiones. Si es la API, va a internet. Si es diseño, va a caché.
self.addEventListener('fetch', (event) => {
  // Ignorar las peticiones a la API PHP (las peticiones de datos siempre deben ir a internet en tiempo real)
  if (event.request.url.includes('/api/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        // Devolver el archivo guardado en el celular (Carga instantánea)
        return cachedResponse;
      }
      // Si no estaba en caché, ir a buscarlo a internet
      return fetch(event.request);
    })
  );
});
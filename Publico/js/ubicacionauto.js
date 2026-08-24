// UBICACIÓN AUTOMÁTICA POR GPS
//
// Flujo: login exitoso -> sessionStorage marca 'ubicacionAutoPendiente' ->
// al cargar el home del cliente (vista=cliente) se solicita la ubicación del
// navegador (Geolocation API) y se envía al backend, que hace el Reverse
// Geocoding y guarda la ubicación + histórico.
//
// Regla fundamental: si el usuario rechaza el permiso, el navegador no
// soporta geolocalización o hay cualquier error, NADA se bloquea: el cliente
// continúa usando la app normalmente y conserva la ubicación manual.

const URL_UBICACION_AUTO = 'apiubicacion.php';

document.addEventListener('DOMContentLoaded', () => {
    // Solo corre una vez por inicio de sesión (lo marca login.js)
    if (!sessionStorage.getItem('ubicacionAutoPendiente')) {
        return;
    }

    sessionStorage.removeItem('ubicacionAutoPendiente');

    intentarUbicacionAutomatica();
});

function intentarUbicacionAutomatica() {
    // Caso 2: navegador sin soporte de Geolocation API -> continuar normal
    if (!navigator.geolocation) {
        return;
    }

    navigator.geolocation.getCurrentPosition(
        enviarUbicacionAutomatica,
        errorUbicacionAutomatica,
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
}

function enviarUbicacionAutomatica(posicion) {
    const formData = new FormData();
    formData.append('lat', posicion.coords.latitude);
    formData.append('lng', posicion.coords.longitude);

    fetch(`${URL_UBICACION_AUTO}?accion=guardarAutomatica`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(resp => {
            // Sin avisos intrusivos: si no se pudo guardar solo se informa
            // en consola y el usuario continúa con la ubicación manual
            if (!resp.guardado) {
                console.info('[Ubicación automática]', resp.mensaje);
            }
        })
        .catch(err => console.info('Ubicación automática no guardada:', err));
}

// Caso 1 (usuario rechaza) y caso 3 (error obteniendo coordenadas):
// no se muestra ningún error crítico, solo queda disponible la ubicación manual
function errorUbicacionAutomatica(error) {
    console.info('Ubicación automática no disponible:', error.message);
}



const URL_UBICACION_AUTO = 'apiubicacion.php';

document.addEventListener('DOMContentLoaded', () => {
    if (!sessionStorage.getItem('ubicacionAutoPendiente')) {
        return;
    }

    sessionStorage.removeItem('ubicacionAutoPendiente');

    intentarUbicacionAutomatica();
});

function intentarUbicacionAutomatica() {
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

    peticion(`${URL_UBICACION_AUTO}?accion=guardarAutomatica`, {
        method: 'POST',
        body: formData
    })
        .then(resp => {
            if (!resp.guardado) {
                console.info('[Ubicación automática]', resp.mensaje);
            }
        })
        .catch(err => console.info('Ubicación automática no guardada:', err));
}

function errorUbicacionAutomatica(error) {
    console.info('Ubicación automática no disponible:', error.message);
}

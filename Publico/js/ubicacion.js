
const URL_UBICACION = 'apiubicacion.php';

let mapa;
let marcador;

document.addEventListener('DOMContentLoaded', () => {
    initMapa();

    cargarProvincias().then(() => {
        cargarUbicacion();
    });

    document.getElementById('provincia').addEventListener('change', function () {
        const provinciaId = this.value;
        resetSelect('canton', 'Seleccione cantón');
        resetSelect('distrito', 'Seleccione distrito');

        if (provinciaId) {
            cargarCantones(provinciaId);
            document.getElementById('canton').disabled = false;
        } else {
            document.getElementById('canton').disabled = true;
            document.getElementById('distrito').disabled = true;
        }
    });

    document.getElementById('canton').addEventListener('change', function () {
        const cantonId = this.value;
        resetSelect('distrito', 'Seleccione distrito');

        if (cantonId) {
            cargarDistritos(cantonId);
            document.getElementById('distrito').disabled = false;
        } else {
            document.getElementById('distrito').disabled = true;
        }
    });

    document.getElementById('formUbicacion').addEventListener('submit', guardarUbicacion);
});

// MAPA (Leaflet + OpenStreetMap, sin API key) 

function initMapa() {
    const centroDefault = [9.9281, -84.0907]; // San José, Costa Rica

    mapa = L.map('mapaUbicacion').setView(centroDefault, 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(mapa);

    mapa.on('click', (evento) => {
        colocarMarcador(evento.latlng);
    });

    // Centra el mapa en la ubicación real, si la autoriza
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition((posicion) => {
            const ubicacionUsuario = [posicion.coords.latitude, posicion.coords.longitude];
            mapa.setView(ubicacionUsuario, 14);
        });
    }
}

function colocarMarcador(latlng) {
    if (marcador) {
        marcador.setLatLng(latlng);
    } else {
        marcador = L.marker(latlng, { draggable: true }).addTo(mapa);
        marcador.on('dragend', () => {
            actualizarCoordenadas(marcador.getLatLng());
        });
    }
    actualizarCoordenadas(latlng);
}

function actualizarCoordenadas(latlng) {
    document.getElementById('latitud').value = latlng.lat;
    document.getElementById('longitud').value = latlng.lng;
}

//  SELECTS EN CASCADA 

function resetSelect(id, textoDefault) {
    const select = document.getElementById(id);
    select.innerHTML = `<option value="">${textoDefault}</option>`;
}

function cargarProvincias() {

    return fetch(`${URL_UBICACION}?accion=getProvincias`)
        .then(res => res.json())
        .then(resp => {

            if (resp.exito) {

                const select = document.getElementById('provincia');

                resp.data.forEach(p => {
                    select.innerHTML +=
                        `<option value="${p.tbprovinciaid}">
                            ${p.tbprovincianombre}
                        </option>`;
                });

            }

        });

}

function cargarUbicacion() {

    const perfilId = document.getElementById('perfilId').value;

    fetch(`${URL_UBICACION}?accion=getUbicacion&perfilId=${perfilId}`)
        .then(res => res.json())
        .then(async resp => {

            if (!resp.exito || !resp.data) {
                return;
            }

            const u = resp.data;

            // Si la ubicación fue desactivada por un administrador, bloquea el formulario
            if (u.tbubicacionestado == 0) {
                deshabilitarFormularioUbicacion();
                return;
            }

            // Provincia
            if (u.tbubicacionprovincia === null) {
                return;
            }

            document.getElementById('provincia').value = u.tbubicacionprovincia;

            if (u.tbubicacioncanton !== null) {
                await cargarCantones(u.tbubicacionprovincia);
                document.getElementById('canton').disabled = false;
                document.getElementById('canton').value = u.tbubicacioncanton;
            }

            if (u.tbubicaciondistrito !== null) {
                await cargarDistritos(u.tbubicacioncanton);
                document.getElementById('distrito').disabled = false;
                document.getElementById('distrito').value = u.tbubicaciondistrito;
            }

            if (u.tbubicacionlatitud !== null && u.tbubicacionlongitud !== null) {
                document.getElementById('latitud').value = u.tbubicacionlatitud;
                document.getElementById('longitud').value = u.tbubicacionlongitud;

                colocarMarcador({
                    lat: parseFloat(u.tbubicacionlatitud),
                    lng: parseFloat(u.tbubicacionlongitud)
                });

                mapa.setView(
                    [u.tbubicacionlatitud, u.tbubicacionlongitud],
                    15
                );
            }

        })
        .catch(console.error);

}

function deshabilitarFormularioUbicacion() {
    document.getElementById('provincia').disabled = true;
    document.getElementById('canton').disabled = true;
    document.getElementById('distrito').disabled = true;
    document.getElementById('btnGuardarUbicacion').disabled = true;
    document.getElementById('mapaUbicacion').style.pointerEvents = 'none';
    document.getElementById('mapaUbicacion').style.opacity = '0.5';

    const contenedor = document.getElementById('contenedorFormularioUbicacion');
    const aviso = document.createElement('div');
    aviso.className = 'alert alert-warning mt-3';
    aviso.textContent = 'Tu ubicación fue desactivada por un administrador y no puede editarse.';
    contenedor.appendChild(aviso);
}

function cargarCantones(provinciaId) {

    return fetch(`${URL_UBICACION}?accion=getCantones&provinciaId=${provinciaId}`)
        .then(res => res.json())
        .then(resp => {

            if (resp.exito) {

                const select = document.getElementById('canton');

                select.innerHTML = '<option value="">Seleccione cantón</option>';

                resp.data.forEach(c => {
                    select.innerHTML +=
                        `<option value="${c.tbcantonid}">${c.tbcantonnombre}</option>`;
                });

            }

        });

}

function cargarDistritos(cantonId) {

    return fetch(`${URL_UBICACION}?accion=getDistritos&cantonId=${cantonId}`)
        .then(res => res.json())
        .then(resp => {

            if (resp.exito) {

                const select = document.getElementById('distrito');

                select.innerHTML = '<option value="">Seleccione distrito</option>';

                resp.data.forEach(d => {
                    select.innerHTML +=
                        `<option value="${d.tbdistritoid}">
                            ${d.tbdistritonombre}
                        </option>`;
                });

            }

        })
        .catch(err => console.error('Error al cargar distritos:', err));

}

function guardarUbicacion(e) {
    e.preventDefault();

    const perfilId = document.getElementById('perfilId').value;
    const provinciaId = document.getElementById('provincia').value;
    const cantonId = document.getElementById('canton').value;
    const distritoId = document.getElementById('distrito').value;
    const lat = document.getElementById('latitud').value;
    const lng = document.getElementById('longitud').value;


    if (!provinciaId || !cantonId || !distritoId) {
        alert('Debe seleccionar provincia, cantón y distrito');
        return;
    }

    if (!lat || !lng) {
        alert('Debe hacer clic en el mapa para marcar su ubicación exacta');
        return;
    }

    const formData = new FormData();
    formData.append('perfilId', perfilId);
    formData.append('provinciaId', provinciaId);
    formData.append('cantonId', cantonId);
    formData.append('distritoId', distritoId);
    formData.append('lat', lat);
    formData.append('lng', lng);



    fetch(`${URL_UBICACION}?accion=guardarUbicacion`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(resp => {
            alert(resp.mensaje);
        })
        .catch(err => console.error('Error al guardar ubicación:', err));
}
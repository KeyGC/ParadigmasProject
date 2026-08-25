let player;
let cancionActualId = null;
let segundosAcumuladosSesion = 0;
let intervaloEnvio = null;
let yaContadaEstaReproduccion = false;

function onYouTubeIframeAPIReady() {
    player = new YT.Player('reproductorYoutube', {
        height: '220',
        width: '100%',
        playerVars: {
            controls: 1 
        },
        events: {
            onStateChange: onPlayerStateChange
        }
    });
}

function extraerVideoId(url) {
    const match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
    return match ? match[1] : null;
}

function cargarCanciones() {
    fetch('apicancion.php?accion=getCanciones')
        .then(r => r.json())
        .then(res => {
            if (!res.exito) return;

            const carrusel = document.getElementById('carruselCanciones');
            carrusel.innerHTML = '';

            res.data.forEach(c => {
                const videoId = extraerVideoId(c.tbcancionurl);
                if (!videoId) return;

                const div = document.createElement('div');
                div.className = 'tarjeta-cancion';
                div.innerHTML = `
                    <img src="https://img.youtube.com/vi/${videoId}/hqdefault.jpg" alt="${c.tbcancionnombre}">
                    <div class="titulo-cancion">${c.tbcancionnombre}</div>
                    <div class="artista-cancion">${c.tbcancionartista}</div>
                `;
                div.onclick = () => reproducirCancion(c, videoId);
                carrusel.appendChild(div);
            });
        });
}

function reproducirCancion(cancion, videoId) {
    detenerTracking();

    cancionActualId = cancion.tbcancionid;
    segundosAcumuladosSesion = 0;
    yaContadaEstaReproduccion = false;

    document.getElementById('tituloCancionModal').textContent =
        `${cancion.tbcancionnombre} - ${cancion.tbcancionartista}`;

    const modal = new bootstrap.Modal(document.getElementById('modalReproductor'));
    modal.show();

    if (player && player.loadVideoById) {
        player.loadVideoById(videoId);
    }
}

function onPlayerStateChange(event) {
    if (event.data === YT.PlayerState.PLAYING) {

        if (!yaContadaEstaReproduccion) {
            registrarReproduccion();
            yaContadaEstaReproduccion = true;
        }

        iniciarTracking();

    } else if (event.data === YT.PlayerState.ENDED) {
        detenerTracking();
        yaContadaEstaReproduccion = false;

    } else {
        detenerTracking();
    }
}

function registrarReproduccion() {
    if (!cancionActualId) return;

    const formData = new FormData();
    formData.append('cancionId', cancionActualId);

    fetch('apicancion.php?accion=registrarReproduccion', {
        method: 'POST',
        body: formData
    });
}

function iniciarTracking() {
    if (intervaloEnvio) return;
    intervaloEnvio = setInterval(() => {
        segundosAcumuladosSesion++;
        if (segundosAcumuladosSesion % 10 === 0) {
            enviarTiempo(10);
        }
    }, 1000);
}

function detenerTracking() {
    if (intervaloEnvio) {
        clearInterval(intervaloEnvio);
        intervaloEnvio = null;

        const restante = segundosAcumuladosSesion % 10;
        if (restante > 0) {
            enviarTiempo(restante);
        }
        segundosAcumuladosSesion = 0;
    }
}

function enviarTiempo(segundos) {
    if (!cancionActualId) return;

    const formData = new FormData();
    formData.append('cancionId', cancionActualId);
    formData.append('segundos', segundos);

    fetch('apicancion.php?accion=registrarTiempo', {
        method: 'POST',
        body: formData
    });
}

document.addEventListener('DOMContentLoaded', () => {
    cargarCanciones();

    document.getElementById('modalReproductor').addEventListener('hidden.bs.modal', () => {
        detenerTracking();
        if (player && player.stopVideo) {
            player.stopVideo();
        }
    });
});

window.addEventListener('beforeunload', detenerTracking);
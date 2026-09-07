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

function crearTarjetaCancion(c) {
    const videoId = extraerVideoId(c.tbcancionurl);
    if (!videoId) return null;

    const div = document.createElement('div');
    div.className = 'tarjeta-cancion';
    div.innerHTML = `
        <img src="https://img.youtube.com/vi/${videoId}/hqdefault.jpg" alt="${c.tbcancionnombre}">
        <div class="titulo-cancion">${c.tbcancionnombre}</div>
        <div class="artista-cancion">${c.tbcancionartista}</div>
    `;
    div.onclick = () => reproducirCancion(c, videoId);
    return div;
}

const ICONOS_GENERO = {
    1: '🎵', 2: '🎸', 3: '💃', 4: '🥁', 5: '🎧',
    6: '🎷', 7: '🎙️', 8: '🎤', 9: '💎', 10: '🎼',
    11: '🌴', 12: '🤠', 13: '🤘', 14: '🌹', 15: '🎺',
    16: '🥳', 17: '🎻', 18: '🕺', 19: '⚡', 20: '🌈'
};

const PALETA_ACENTOS = [
    '#E84393', '#0984E3', '#6C5CE7', '#00B894', '#E17055',
    '#FDCB6E', '#27AE60', '#8E44AD', '#2D9CDB', '#E74C3C'
];

function cargarGeneros() {
    fetch('apicancion.php?accion=getGenerosConConteo')
        .then(r => r.json())
        .then(res => {
            if (!res.exito) return;

            const contenedor = document.getElementById('contenedorGeneros');
            contenedor.innerHTML = '';

            res.data.forEach(g => {
                const div = document.createElement('div');
                div.className = 'tarjeta-genero';
                div.style.setProperty('--acento', PALETA_ACENTOS[(g.tbgeneroid - 1) % PALETA_ACENTOS.length]);
                div.innerHTML = `
                    <div class="icono-genero">${ICONOS_GENERO[g.tbgeneroid] || '🎵'}</div>
                    <div class="nombre-genero">${g.tbgeneronombre}</div>
                    <div class="conteo-canciones">${g.total} canción${g.total == 1 ? '' : 'es'}</div>
                `;
                div.onclick = () => cargarPlaylistGenero(g.tbgeneroid, g.tbgeneronombre);
                contenedor.appendChild(div);
            });
        });
}

function cargarPlaylistGenero(tbgeneroid, nombreGenero) {
    fetch(`apicancion.php?accion=getPlaylistPorGenero&tbgeneroid=${tbgeneroid}`)
        .then(r => r.json())
        .then(res => {
            if (!res.exito) return;

            document.getElementById('contenedorGeneros').style.display = 'none';
            document.getElementById('contenedorPlaylist').style.display = 'block';
            document.getElementById('tituloPlaylist').textContent = nombreGenero;

            const carrusel = document.getElementById('carruselCanciones');
            carrusel.innerHTML = '';

            res.data.forEach(c => {
                const tarjeta = crearTarjetaCancion(c);
                if (tarjeta) carrusel.appendChild(tarjeta);
            });
        });
}

function volverGeneros() {
    document.getElementById('contenedorPlaylist').style.display = 'none';
    document.getElementById('contenedorGeneros').style.display = '';
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
    cargarGeneros();

    document.getElementById('modalReproductor').addEventListener('hidden.bs.modal', () => {
        detenerTracking();
        if (player && player.stopVideo) {
            player.stopVideo();
        }
    });
});

window.addEventListener('beforeunload', detenerTracking);

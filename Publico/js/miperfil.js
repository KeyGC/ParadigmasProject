const URL_API_MIPERFIL = 'apimiperfil.php';

document.addEventListener('DOMContentLoaded', cargarResumenPerfil);

function cargarResumenPerfil() {
    fetch(`${URL_API_MIPERFIL}?accion=getResumen`)
        .then(res => res.json())
        .then(respuesta => {
            if (!respuesta.exito) {
                document.getElementById('nombrePerfilResumen').textContent = 'No se pudo cargar el perfil';
                console.error(respuesta.mensaje);
                return;
            }
            pintarResumen(respuesta.data);
        })
        .catch(err => {
            console.error('Error al cargar el perfil:', err);
            document.getElementById('nombrePerfilResumen').textContent = 'Error al cargar el perfil';
        });
}

function pintarResumen(data) {
    document.getElementById('nombrePerfilResumen').textContent = data.nombre;
    document.getElementById('correoPerfilResumen').textContent = data.correo;
    document.getElementById('ubicacionPerfilResumen').textContent = data.ubicacion
        ? `${data.ubicacion}`
        : 'Ubicación no configurada todavía';

    const contenedorGustos = document.getElementById('gustosMusicalesLista');
    contenedorGustos.innerHTML = '';

    if (!data.gustosMusicalesDisponible || data.gustosMusicales.length === 0) {
        const mensaje = data.gustosMusicalesMensaje || 'Aún no tenemos suficiente información sobre tus gustos musicales.';
        contenedorGustos.innerHTML = `<p class="text-secondary">${mensaje}</p>`;
        return;
    }

    const lista = document.createElement('ul');
    lista.className = 'list-unstyled d-flex flex-column gap-2';

    data.gustosMusicales.forEach(texto => {
        const item = document.createElement('li');
        item.className = 'gustoMusicalItem';
        item.textContent = `🎵 ${texto}`;
        lista.appendChild(item);
    });

    contenedorGustos.appendChild(lista);
}
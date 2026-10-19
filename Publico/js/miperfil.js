(function () {
    'use strict';

    var API_URL_PERFIL = 'apimiperfil.php';

    var IDS_GUSTOS = [
        'gustosMusicalesLista',
        'gustosGastronomicosLista',
        'gustosDeportivosLista'
    ];

    function limpiarIndicadores(mensajeError) {
        var texto = mensajeError || (window.Peticiones && window.Peticiones.MENSAJES ? window.Peticiones.MENSAJES.servidor : 'No se pudo cargar la información.');
        IDS_GUSTOS.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.innerHTML = '<p class="text-secondary small mb-0">' + texto + '</p>';
            }
        });
    }

    function pintarListaGustos(contenedorId, disponible, items, mensajeVacio, icono, urlJuego, textoJuego) {
        var contenedor = document.getElementById(contenedorId);
        if (!contenedor) return;
        contenedor.innerHTML = '';

        if (!disponible || !items || items.length === 0) {
            var mensaje = mensajeVacio || 'Aún no tenemos suficiente información sobre tus gustos.';
            var p = document.createElement('p');
            p.className = 'text-secondary small mb-0';
            p.textContent = mensaje;
            if (urlJuego && textoJuego) {
                p.appendChild(document.createTextNode(' '));
                var a = document.createElement('a');
                a.href = urlJuego;
                a.className = 'text-decoration-none';
                a.style.color = 'var(--color-primario)';
                a.textContent = textoJuego;
                p.appendChild(a);
            }
            contenedor.appendChild(p);
            return;
        }

        var lista = document.createElement('ul');
        lista.className = 'list-unstyled d-flex flex-column gap-2 mb-0';

        items.forEach(function (texto) {
            var item = document.createElement('li');
            item.className = 'gustoMusicalItem';
            item.textContent = icono + ' ' + texto;
            lista.appendChild(item);
        });

        contenedor.appendChild(lista);
    }

    function pintarResumen(data) {
        var elNombre = document.getElementById('nombrePerfilResumen');
        var elCorreo = document.getElementById('correoPerfilResumen');
        var elUbicacion = document.getElementById('ubicacionPerfilResumen');

        if (elNombre) elNombre.textContent = data.nombre || '';
        if (elCorreo) elCorreo.textContent = data.correo || '';
        if (elUbicacion) {
            elUbicacion.textContent = data.ubicacion || 'Ubicación no configurada todavía';
        }

        pintarListaGustos(
            'gustosMusicalesLista',
            data.gustosMusicalesDisponible,
            data.gustosMusicales,
            data.gustosMusicalesMensaje,
            '🎵'
        );
        pintarListaGustos(
            'gustosGastronomicosLista',
            data.gustosGastronomicosDisponible,
            data.gustosGastronomicos,
            data.gustosGastronomicosMensaje,
            '🍔',
            'index.php?vista=descubrircomida',
            'Descubre tus gustos en Comida.'
        );
        pintarListaGustos(
            'gustosDeportivosLista',
            data.gustosDeportivosDisponible,
            data.gustosDeportivos,
            data.gustosDeportivosMensaje,
            '⚽',
            'index.php?vista=descubrirdeporte',
            'Descubre tus gustos en Deporte.'
        );
    }

    function cargarResumenPerfil() {
        peticion(API_URL_PERFIL + '?accion=getResumen')
            .then(function (respuesta) {
                if (!respuesta || !respuesta.exito) {
                    var elNombre = document.getElementById('nombrePerfilResumen');
                    if (elNombre) elNombre.textContent = 'No se pudo cargar el perfil';
                    var msg = (respuesta && respuesta.mensaje)
                        ? respuesta.mensaje
                        : (window.Peticiones && window.Peticiones.MENSAJES ? window.Peticiones.MENSAJES.servidor : 'Ocurrió un error en el servidor.');
                    limpiarIndicadores(msg);
                    return;
                }
                pintarResumen(respuesta.data);
            })
            .catch(function (err) {
                var elNombre = document.getElementById('nombrePerfilResumen');
                if (elNombre) elNombre.textContent = 'Error al cargar el perfil';
                var msg = (err && err.message)
                    ? err.message
                    : (window.Peticiones && window.Peticiones.MENSAJES ? window.Peticiones.MENSAJES.red : 'No se pudo conectar con el servidor.');
                limpiarIndicadores(msg);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', cargarResumenPerfil);
    } else {
        cargarResumenPerfil();
    }
})();
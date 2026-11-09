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
        pintarPerfilFotografico(data.perfilFotografico);
    }

    function pintarPerfilFotografico(pf) {
        var contenedor = document.getElementById('perfilFotograficoContenedor');
        if (!contenedor) return;
        contenedor.innerHTML = '';

        if (!pf || pf.totalFotos === 0) {
            var p = document.createElement('p');
            p.className = 'text-secondary small mb-0';
            p.textContent = 'Aún no has participado en retos fotográficos.';
            var a = document.createElement('a');
            a.href = 'index.php?vista=fotografia';
            a.className = 'text-decoration-none ms-1';
            a.style.color = 'var(--color-primario)';
            a.textContent = 'Explora el Club de Fotografía y sube tus fotos.';
            p.appendChild(a);
            contenedor.appendChild(p);
            return;
        }

        var html = '<div class="row g-2 mb-3 text-center">' +
            '<div class="col-4"><div class="p-2 border rounded bg-white"><div class="fw-bold text-primary">' + pf.totalRetos + '</div><small class="text-secondary" style="font-size:0.75rem;">Retos</small></div></div>' +
            '<div class="col-4"><div class="p-2 border rounded bg-white"><div class="fw-bold text-success">' + pf.totalPodios + '</div><small class="text-secondary" style="font-size:0.75rem;">Podios</small></div></div>' +
            '<div class="col-4"><div class="p-2 border rounded bg-white"><div class="fw-bold text-warning">★ ' + pf.promedioGeneral + '</div><small class="text-secondary" style="font-size:0.75rem;">Promedio</small></div></div>' +
        '</div>';

        if (pf.perfilEstructural) {
            html += '<div class="card p-3 mb-3 border-0 bg-light" style="border-radius: var(--radio-tarjeta);">' +
                '<h3 class="h6 mb-2 fw-semibold text-primary">Perfil Estructural Fotográfico</h3>' +
                '<div class="d-flex flex-wrap gap-2">' +
                    '<span class="badge bg-primary">Estilo: ' + escapeTexto(pf.perfilEstructural.estiloPrincipal) + '</span>' +
                    '<span class="badge bg-secondary">Color: ' + escapeTexto(pf.perfilEstructural.colorPrincipal) + '</span>' +
                    '<span class="badge bg-info text-dark">Composición: ' + escapeTexto(pf.perfilEstructural.composicionPrincipal) + '</span>' +
                '</div>' +
            '</div>';
        }

        if (pf.retosParticipados && pf.retosParticipados.length > 0) {
            html += '<h3 class="h6 mb-2 fw-semibold">Mis participaciones y resultados:</h3><div class="d-flex flex-column gap-2">';
            pf.retosParticipados.forEach(function (r) {
                var medalla = 'Participante';
                var badgeClass = 'bg-secondary';
                if (r.miPosicion === 1) {
                    medalla = '🥇 1° Lugar (Ganador)';
                    badgeClass = 'bg-warning text-dark';
                } else if (r.miPosicion === 2) {
                    medalla = '🥈 2° Lugar';
                    badgeClass = 'bg-secondary text-white';
                } else if (r.miPosicion === 3) {
                    medalla = '🥉 3° Lugar';
                    badgeClass = 'bg-danger text-white';
                } else if (r.miPosicion) {
                    medalla = 'Posición #' + r.miPosicion;
                }

                html += '<div class="p-2 border rounded bg-white d-flex justify-content-between align-items-center">' +
                    '<div>' +
                        '<div class="fw-semibold">' + escapeTexto(r.tematica) + '</div>' +
                        '<small class="text-secondary">' + r.fotos.length + ' foto(s) inscrita(s)</small>' +
                    '</div>' +
                    '<div class="text-end">' +
                        '<span class="badge ' + badgeClass + ' mb-1">' + medalla + '</span><br>' +
                        '<a href="index.php?vista=fotografia&reto=' + encodeURIComponent(r.tbretoid) + '" class="small text-decoration-none" style="font-size:0.75rem;">Ver reto &rarr;</a>' +
                    '</div>' +
                '</div>';
            });
            html += '</div>';
        }

        contenedor.innerHTML = html;
    }

    function escapeTexto(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
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
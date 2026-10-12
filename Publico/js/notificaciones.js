(function () {
    'use strict';

    const config = Object.assign(
        { apiUrl: 'apiafinidad.php', intervalo: 10000, perfilId: null },
        window.CONFIG_NOTIFICACIONES || {}
    );

    const badge = document.getElementById('campanaBadge');
    const lista = document.getElementById('notificacionesLista');
    const vacio = document.getElementById('notificacionesVacio');
    const error = document.getElementById('notificacionesError');

    let temporizador = null;

    function mostrarError(mensaje) {
        if (error) {
            error.textContent = mensaje;
            error.hidden = false;
        }
    }

    function limpiarError() {
        if (error) {
            error.hidden = true;
            error.textContent = '';
        }
    }

    function pintarBadge(total) {
        if (!badge) {
            return;
        }

        const numero = Number(total) || 0;

        if (numero > 0) {
            badge.textContent = numero > 99 ? '99+' : String(numero);
            badge.classList.remove('d-none');
        } else {
            badge.textContent = '0';
            badge.classList.add('d-none');
        }
    }

    async function pedir(url, opciones) {
        const respuesta = await fetch(url, Object.assign({ credentials: 'same-origin' }, opciones || {}));
        const texto = await respuesta.text();
        return JSON.parse(texto);
    }

    async function refrescarContador() {
        try {
            const datos = await pedir(config.apiUrl + '?accion=contador');
            if (datos && datos.exito) {
                pintarBadge(datos.data ? datos.data.total : 0);
            }
        } catch (e) {
            // Silencioso: el contador no debe romper la pagina.
        }
    }

    function etiquetaTema(afinidad) {
        const tema = (afinidad.tbafinidadtema || '').trim();
        if (tema !== '') {
            return tema;
        }
        return afinidad.tbafinidadcategoria || 'interés';
    }

    function textoOtros(afinidad) {
        const cantidad = Number(afinidad.tbafinidadcantidadotros || 0);
        const otros = afinidad.tbafinidadotros || [];

        if (afinidad.tbafinidadmostrarnombres && otros.length > 0) {
            return otros.map(function (o) { return o.tbperfilnombre; }).join(', ');
        }

        return cantidad === 1 ? '1 persona con este interés' : (cantidad + ' personas con este interés');
    }

    function crearTarjeta(afinidad) {
        const columna = document.createElement('div');
        columna.className = 'col-12 col-md-6';

        const privacidad = afinidad.tbafinidadorigen === 'chat' && !afinidad.tbafinidadmostrarnombres
            ? '<p class="text-secondary small mb-2">Perfiles reservados hasta que aceptes.</p>'
            : '';

        columna.innerHTML =
            '<div class="card h-100 shadow-sm">' +
                '<div class="card-body d-flex flex-column">' +
                    '<h2 class="h6 mb-1">' + escapeHtml(etiquetaTema(afinidad)) + '</h2>' +
                    '<p class="text-secondary small mb-2">' +
                        escapeHtml(afinidad.tbafinidadcategoria || '') +
                        ' · origen ' + escapeHtml(afinidad.tbafinidadorigen || '') +
                    '</p>' +
                    '<p class="small mb-2">' + escapeHtml(textoOtros(afinidad)) + '</p>' +
                    privacidad +
                    '<div class="mt-auto d-flex gap-2">' +
                        '<button type="button" class="btn btn-primary btn-sm" data-accion="aceptar">Aceptar</button>' +
                        '<button type="button" class="btn btn-outline-secondary btn-sm" data-accion="rechazar">Ahora no</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        columna.querySelector('[data-accion="aceptar"]').addEventListener('click', function () {
            responder(afinidad.tbafinidadid, 'aceptar');
        });
        columna.querySelector('[data-accion="rechazar"]').addEventListener('click', function () {
            responder(afinidad.tbafinidadid, 'rechazar');
        });

        return columna;
    }

    function escapeHtml(valor) {
        const div = document.createElement('div');
        div.textContent = valor == null ? '' : String(valor);
        return div.innerHTML;
    }

    async function responder(afinidadId, accion) {
        const cuerpo = new FormData();
        cuerpo.append('accion', accion);
        cuerpo.append('afinidadId', afinidadId);

        try {
            const datos = await pedir(config.apiUrl, { method: 'POST', body: cuerpo });
            if (datos && datos.exito) {
                await cargarPendientes();
                await cargarRecomendadas();
                await refrescarContador();
            } else {
                mostrarError((datos && datos.mensaje) || 'No se pudo completar la acción.');
            }
        } catch (e) {
            mostrarError('No se pudo conectar con el servidor.');
        }
    }

    async function cargarPendientes() {
        if (!lista) {
            return;
        }

        limpiarError();

        try {
            const datos = await pedir(config.apiUrl + '?accion=getPendientes');

            lista.innerHTML = '';
            const pendientes = (datos && datos.data) || [];

            if (!datos || !datos.exito) {
                mostrarError((datos && datos.mensaje) || 'No se pudieron cargar las recomendaciones.');
                return;
            }

            pendientes.forEach(function (afinidad) {
                lista.appendChild(crearTarjeta(afinidad));
            });

            if (vacio) {
                vacio.hidden = pendientes.length > 0;
            }
        } catch (e) {
            mostrarError('No se pudieron cargar las recomendaciones.');
        }
    }

    function crearTarjetaRecomendada(afinidad) {
        const columna = document.createElement('div');
        columna.className = 'col-12 col-md-6';

        const aceptada = afinidad.tbafinidadmiembroestado === 'aceptada';
        const grupoId = afinidad.tbgrupoid;
        const cerrada = afinidad.tbafinidadestado === 'cerrada';

        let acciones = '';

        if (grupoId) {
            acciones = '<a class="btn btn-primary btn-sm" href="index.php?vista=grupos&grupo=' + encodeURIComponent(grupoId) + '">Abrir grupo</a>';
        } else if (!aceptada && !cerrada) {
            acciones = '<button type="button" class="btn btn-primary btn-sm" data-accion="aceptar">Aceptar</button>';
        } else if (cerrada) {
            acciones = '<span class="badge bg-secondary">Desactivada</span>';
        } else {
            acciones = '<span class="badge bg-info text-dark">Esperando más personas</span>';
        }

        columna.innerHTML =
            '<div class="card h-100 shadow-sm">' +
                '<div class="card-body d-flex flex-column">' +
                    '<h3 class="h6 mb-1">' + escapeHtml(etiquetaTema(afinidad)) + '</h3>' +
                    '<p class="text-secondary small mb-2">' +
                        escapeHtml(afinidad.tbafinidadcategoria || '') +
                        ' · origen ' + escapeHtml(afinidad.tbafinidadorigen || '') +
                    '</p>' +
                    '<p class="small mb-2">' + escapeHtml(textoOtros(afinidad)) + '</p>' +
                    '<div class="mt-auto">' + acciones + '</div>' +
                '</div>' +
            '</div>';

        const boton = columna.querySelector('[data-accion="aceptar"]');
        if (boton) {
            boton.addEventListener('click', function () {
                responder(afinidad.tbafinidadid, 'aceptar');
            });
        }

        return columna;
    }

    async function cargarRecomendadas() {
        const contenedor = document.getElementById('recomendadasLista');

        if (!contenedor) {
            return;
        }

        const errorRec = document.getElementById('recomendadasError');
        const vacioRec = document.getElementById('recomendadasVacio');

        try {
            const datos = await pedir(config.apiUrl + '?accion=getRecomendadas');

            contenedor.innerHTML = '';

            if (!datos || !datos.exito) {
                if (errorRec) {
                    errorRec.textContent = (datos && datos.mensaje) || 'No se pudieron cargar las recomendaciones.';
                    errorRec.hidden = false;
                }
                return;
            }

            const afinidades = datos.data || [];
            afinidades.forEach(function (afinidad) {
                contenedor.appendChild(crearTarjetaRecomendada(afinidad));
            });

            if (vacioRec) {
                vacioRec.hidden = afinidades.length > 0;
            }
        } catch (e) {
            if (errorRec) {
                errorRec.textContent = 'No se pudieron cargar las recomendaciones.';
                errorRec.hidden = false;
            }
        }
    }

    function programar() {
        if (temporizador) {
            clearInterval(temporizador);
        }

        temporizador = setInterval(function () {
            if (document.hidden) {
                return;
            }
            refrescarContador();
        }, config.intervalo);
    }

    document.addEventListener('DOMContentLoaded', function () {
        refrescarContador();
        cargarPendientes();
        cargarRecomendadas();
        programar();
    });
})();

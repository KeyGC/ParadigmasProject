(function () {
    'use strict';

    var config = window.CONFIG_COMUNIDAD || {};
    var apiUrl = config.apiUrl || 'apicomunidad.php';
    var perfilId = parseInt(config.perfilId, 10) || 0;
    var perfilNombre = config.perfilNombre || ('Perfil ' + perfilId);
    var comunidadInicial = parseInt(config.comunidadInicial, 10) || 0;
    var intervaloPolling = 3000;

    var CATEGORIAS = [
        { clave: 'genero', titulo: 'Musica', icono: '🎵' },
        { clave: 'comida', titulo: 'Comida', icono: '🍔' },
        { clave: 'deporte', titulo: 'Deporte', icono: '⚽' }
    ];

    var PALETA_ACENTOS = [
        '#E84393', '#0984E3', '#6C5CE7', '#00B894', '#E17055',
        '#FDCB6E', '#27AE60', '#8E44AD', '#2D9CDB', '#E74C3C'
    ];

    var el = {
        catalogo: document.getElementById('comunidadCatalogo'),
        cargando: document.getElementById('comunidadCargando'),
        vacio: document.getElementById('comunidadVacio'),
        sala: document.getElementById('comunidadSala'),
        volver: document.getElementById('comunidadVolver'),
        nombre: document.getElementById('comunidadNombre'),
        mensajes: document.getElementById('comunidadMensajes'),
        mensajesVacio: document.getElementById('comunidadMensajesVacio'),
        form: document.getElementById('comunidadForm'),
        texto: document.getElementById('comunidadTexto'),
        enviar: document.getElementById('comunidadEnviar'),
        estado: document.getElementById('comunidadEstado'),
        error: document.getElementById('comunidadError'),
        secciones: {},
        grids: {}
    };

    CATEGORIAS.forEach(function (categoria) {
        el.secciones[categoria.clave] = document.getElementById('comunidadSeccion' + capitalizar(categoria.clave));
        el.grids[categoria.clave] = document.getElementById('comunidadGrid' + capitalizar(categoria.clave));
    });

    var estado = {
        comunidades: [],
        activa: null,
        ultimoId: 0,
        timer: null,
        consultando: false,
        enviando: false,
        avisoFormato: false
    };

    function capitalizar(texto) {
        return texto.charAt(0).toUpperCase() + texto.slice(1);
    }

    function consultar(params) {
        return fetch(apiUrl + '?' + new URLSearchParams(params), {
            headers: { 'Accept': 'application/json' }
        }).then(function (respuesta) {
            return respuesta.json();
        });
    }

    function enviar(params) {
        var datos = new URLSearchParams();
        Object.keys(params).forEach(function (clave) {
            datos.append(clave, params[clave]);
        });
        return fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: datos.toString()
        }).then(function (respuesta) {
            return respuesta.json();
        });
    }

    function mensaje(texto) {
        if (el.estado) el.estado.textContent = texto || '';
    }

    function hora(fecha) {
        if (!fecha) return '';
        var partes = String(fecha).replace('T', ' ').split(' ');
        var horaTexto = partes[1] ? partes[1].slice(0, 5) : '';
        return partes[0] + ' ' + horaTexto;
    }

    function scrollAlFinal() {
        if (el.mensajes) el.mensajes.scrollTop = el.mensajes.scrollHeight;
    }

    function estadoVacio() {
        // estado.comunidades es un objeto { genero: [], comida: [], deporte: [] },
        // no un array: se recorre con Object.keys y no con metodos de Array
        return Object.keys(estado.comunidades).every(function (clave) {
            return !Array.isArray(estado.comunidades[clave]) || estado.comunidades[clave].length === 0;
        });
    }

    function esCatalogoValido(data) {
        if (!data || typeof data !== 'object' || Array.isArray(data)) {
            return false;
        }
        return CATEGORIAS.every(function (categoria) {
            return Array.isArray(data[categoria.clave]);
        });
    }

    function errorFormato(etiqueta, data) {
        var detalle;
        try {
            detalle = JSON.stringify(data);
        } catch (e) {
            detalle = String(data);
        }
        console.error('comunidades.js: formato inesperado en ' + etiqueta + ' -> ' + detalle);
    }

    function mensajeCatalogo(texto) {
        if (!el.error) return;
        el.error.textContent = texto || '';
        el.error.hidden = !texto;
    }

    function pintarCatalogo() {
        if (el.cargando) el.cargando.hidden = true;

        var vacio = estadoVacio();
        if (el.vacio) el.vacio.hidden = !vacio;

        CATEGORIAS.forEach(function (categoria) {
            var lista = estado.comunidades[categoria.clave] || [];
            var grid = el.grids[categoria.clave];
            var seccion = el.secciones[categoria.clave];

            if (!grid || !seccion) return;

            grid.innerHTML = '';
            seccion.hidden = lista.length === 0;

            lista.forEach(function (comunidad, indice) {
                var tarjeta = document.createElement('div');
                tarjeta.className = 'tarjeta-genero comunidad-tarjeta';
                tarjeta.style.setProperty('--acento', PALETA_ACENTOS[indice % PALETA_ACENTOS.length]);
                tarjeta.dataset.comunidad = comunidad.tbcomunidadid;

                var icono = document.createElement('div');
                icono.className = 'icono-genero';
                icono.textContent = categoria.icono;
                tarjeta.appendChild(icono);

                var nombre = document.createElement('div');
                nombre.className = 'nombre-genero';
                nombre.textContent = comunidad.tbcomunidadnombre;
                tarjeta.appendChild(nombre);

                var conteo = document.createElement('div');
                conteo.className = 'conteo-canciones';
                conteo.textContent = categoria.titulo;
                tarjeta.appendChild(conteo);

                tarjeta.addEventListener('click', function () {
                    abrirComunidad(comunidad);
                });

                grid.appendChild(tarjeta);
            });
        });
    }

    function agregarMensaje(dato) {
        if (!el.mensajes) return;

        var fila = document.createElement('div');
        var propia = dato.tbperfilidemisor === perfilId;
        fila.className = 'chat-fila ' + (propia ? 'mía' : 'otra');

        // En una sala grupal siempre se muestra quien escribe, tambien el propio
        var emisor = document.createElement('span');
        emisor.className = 'chat-emisor' + (propia ? ' propio' : '');
        emisor.textContent = dato.tbperfilnombre || ('Perfil ' + dato.tbperfilidemisor);
        fila.appendChild(emisor);

        var texto = document.createElement('p');
        texto.className = 'chat-burbuja mb-1';
        texto.textContent = dato.tbcomunidadmensajetexto;
        fila.appendChild(texto);

        var meta = document.createElement('span');
        meta.className = 'chat-hora';
        meta.textContent = hora(dato.tbcomunidadmensajefechahora);
        fila.appendChild(meta);

        el.mensajes.appendChild(fila);
    }

    function activarEnvio(activo) {
        if (el.texto) el.texto.disabled = !activo;
        if (el.enviar) el.enviar.disabled = !activo;
    }

    function detenerPolling() {
        if (estado.timer !== null) {
            clearInterval(estado.timer);
            estado.timer = null;
        }
    }

    function iniciarPolling() {
        detenerPolling();
        estado.timer = setInterval(polling, intervaloPolling);
    }

    function polling() {
        if (!estado.activa || estado.consultando) return;
        if (document.hidden) return;
        estado.consultando = true;

        consultar({
            accion: 'getMensajesComunidad',
            comunidadId: estado.activa.tbcomunidadid,
            desdeId: estado.ultimoId
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudieron actualizar los mensajes.');
                return;
            }

            if (!Array.isArray(respuesta.data)) {
                if (!estado.avisoFormato) {
                    errorFormato('getMensajesComunidad', respuesta.data);
                    estado.avisoFormato = true;
                }
                mensaje('El servidor no devolvió una lista de mensajes válida.');
                return;
            }

            var nuevos = respuesta.data;
            if (nuevos.length === 0) return;

            nuevos.forEach(function (dato) {
                agregarMensaje(dato);
                if (dato.tbcomunidadmensajeid > estado.ultimoId) {
                    estado.ultimoId = dato.tbcomunidadmensajeid;
                }
            });
            scrollAlFinal();
        }).catch(function () {
            mensaje('Sin conexión con el servidor, reintentando...');
        }).then(function () {
            estado.consultando = false;
        });
    }

    function mostrarCatalogo() {
        detenerPolling();
        estado.activa = null;
        estado.ultimoId = 0;
        estado.consultando = false;
        estado.enviando = false;

        if (el.sala) el.sala.classList.add('d-none');
        if (el.catalogo) el.catalogo.classList.remove('d-none');
        if (el.nombre) el.nombre.textContent = 'Comunidad';
        activarEnvio(false);
        mensaje('');
    }

    function abrirComunidad(comunidad) {
        estado.activa = comunidad;
        estado.ultimoId = 0;
        estado.consultando = false;
        estado.avisoFormato = false;

        if (el.catalogo) el.catalogo.classList.add('d-none');
        if (el.sala) el.sala.classList.remove('d-none');
        if (el.nombre) el.nombre.textContent = comunidad.tbcomunidadnombre;
        if (el.mensajes) el.mensajes.innerHTML = '';
        if (el.mensajesVacio) el.mensajesVacio.classList.add('d-none');
        activarEnvio(true);
        if (el.texto) el.texto.focus();

        consultar({
            accion: 'getMensajesComunidad',
            comunidadId: comunidad.tbcomunidadid,
            desdeId: 0
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudieron cargar los mensajes.');
                return;
            }

            if (!Array.isArray(respuesta.data)) {
                errorFormato('getMensajesComunidad', respuesta.data);
                mensaje('El servidor no devolvió una lista de mensajes válida.');
                return;
            }

            var lista = respuesta.data;
            if (lista.length === 0 && el.mensajesVacio) {
                el.mensajesVacio.classList.remove('d-none');
            }

            lista.forEach(function (dato) {
                agregarMensaje(dato);
                if (dato.tbcomunidadmensajeid > estado.ultimoId) {
                    estado.ultimoId = dato.tbcomunidadmensajeid;
                }
            });
            scrollAlFinal();
        }).catch(function () {
            mensaje('Sin conexión con el servidor.');
        });

        iniciarPolling();
    }

    function enviarMensaje(evento) {
        evento.preventDefault();
        if (!estado.activa || estado.enviando) return;

        var texto = el.texto ? el.texto.value.trim() : '';
        if (texto === '') return;

        estado.enviando = true;
        if (el.enviar) el.enviar.disabled = true;

        enviar({
            accion: 'enviarMensajeComunidad',
            comunidadId: estado.activa.tbcomunidadid,
            texto: texto
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudo enviar el mensaje.');
                return;
            }
            agregarMensaje(respuesta.data);
            if (respuesta.data.tbcomunidadmensajeid > estado.ultimoId) {
                estado.ultimoId = respuesta.data.tbcomunidadmensajeid;
            }
            if (el.texto) el.texto.value = '';
            mensaje('');
            scrollAlFinal();
        }).catch(function () {
            mensaje('No se pudo enviar el mensaje, reintenta.');
        }).then(function () {
            estado.enviando = false;
            activarEnvio(true);
            if (el.texto) el.texto.focus();
        });
    }

    function cargarComunidades() {
        return consultar({ accion: 'getComunidades' })
            .then(function (respuesta) {
                if (!respuesta.exito) {
                    mensaje(respuesta.mensaje || 'No se pudieron cargar las comunidades.');
                    if (el.cargando) el.cargando.hidden = true;
                    return;
                }

                if (!esCatalogoValido(respuesta.data)) {
                    errorFormato('getComunidades', respuesta.data);
                    mensajeCatalogo('El servidor no devolvió el catálogo de comunidades esperado.');
                    if (el.cargando) el.cargando.hidden = true;
                    return;
                }

                mensajeCatalogo('');
                estado.comunidades = respuesta.data;
                pintarCatalogo();
            })
            .catch(function (error) {
                if (error) {
                    errorFormato('getComunidades', error);
                }
                mensajeCatalogo('Sin conexión con el servidor, reintenta.');
                if (el.cargando) el.cargando.hidden = true;
            });
    }

    function buscarComunidad(comunidadId) {
        var encontrada = null;
        Object.keys(estado.comunidades).forEach(function (clave) {
            (estado.comunidades[clave] || []).forEach(function (comunidad) {
                if (comunidad.tbcomunidadid === comunidadId) encontrada = comunidad;
            });
        });
        return encontrada;
    }

    function iniciar() {
        activarEnvio(false);
        if (el.form) el.form.addEventListener('submit', enviarMensaje);
        if (el.volver) el.volver.addEventListener('click', mostrarCatalogo);

        cargarComunidades().then(function () {
            if (comunidadInicial > 0) {
                var comunidad = buscarComunidad(comunidadInicial);
                if (comunidad) {
                    abrirComunidad(comunidad);
                    return;
                }
                mensaje('La comunidad solicitada no existe o esta inactiva.');
            }
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && estado.activa) {
            polling();
        }
    });

    document.addEventListener('DOMContentLoaded', iniciar);
})();
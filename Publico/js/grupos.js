(function () {
    'use strict';

    var config = window.CONFIG_GRUPO || {};
    var apiUrl = config.apiUrl || 'apigrupo.php';
    var perfilId = parseInt(config.perfilId, 10) || 0;
    var grupoInicial = parseInt(config.grupoInicial, 10) || 0;
    var intervaloPolling = 3000;

    var el = {
        catalogo: document.getElementById('grupoCatalogo'),
        cargando: document.getElementById('grupoCargando'),
        vacio: document.getElementById('grupoVacio'),
        lista: document.getElementById('grupoLista'),
        sala: document.getElementById('grupoSala'),
        volver: document.getElementById('grupoVolver'),
        nombre: document.getElementById('grupoNombre'),
        miembros: document.getElementById('grupoMiembros'),
        mensajes: document.getElementById('grupoMensajes'),
        mensajesVacio: document.getElementById('grupoMensajesVacio'),
        form: document.getElementById('grupoForm'),
        texto: document.getElementById('grupoTexto'),
        enviar: document.getElementById('grupoEnviar'),
        estado: document.getElementById('grupoEstado'),
        error: document.getElementById('grupoError')
    };

    var estado = {
        grupos: [],
        activa: null,
        ultimoId: 0,
        timer: null,
        consultando: false,
        enviando: false,
        avisoFormato: false,
        vistos: {},
        cargandoGrupos: false
    };

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

    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function errorFormato(etiqueta, data) {
        var detalle;
        try {
            detalle = JSON.stringify(data);
        } catch (e) {
            detalle = String(data);
        }
        console.error('grupos.js: formato inesperado en ' + etiqueta + ' -> ' + detalle);
    }

    function pintarGrupos() {
        if (el.cargando) el.cargando.hidden = true;
        if (el.vacio) el.vacio.hidden = estado.grupos.length > 0;
        if (!el.lista) return;

        el.lista.innerHTML = '';

        estado.grupos.forEach(function (grupo) {
            var tarjeta = document.createElement('button');
            tarjeta.type = 'button';
            tarjeta.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
            tarjeta.dataset.grupo = grupo.tbgrupoid;

            var izquierda = document.createElement('div');
            izquierda.className = 'text-start';

            var titulo = document.createElement('div');
            titulo.className = 'fw-semibold';
            titulo.textContent = grupo.tbgruponombre;
            izquierda.appendChild(titulo);

            var detalle = document.createElement('div');
            detalle.className = 'text-secondary small';
            detalle.textContent = grupo.tbgrupocantidadmiembros + ' miembros · ' + (grupo.tbgrupocategoria || '');
            izquierda.appendChild(detalle);

            if (grupo.tbgrupoultimotexto) {
                var ultimo = document.createElement('div');
                ultimo.className = 'text-secondary small text-truncate';
                ultimo.textContent = grupo.tbgrupoultimotexto;
                izquierda.appendChild(ultimo);
            }

            tarjeta.appendChild(izquierda);

            if (grupo.tbgrupomensajenoleidos > 0) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-danger rounded-pill';
                badge.textContent = grupo.tbgrupomensajenoleidos;
                tarjeta.appendChild(badge);
            }

            tarjeta.addEventListener('click', function () {
                abrirGrupo(grupo);
            });

            el.lista.appendChild(tarjeta);
        });
    }

    function claveMensaje(dato) {
        if (dato.tbgrupomensajeid !== null && dato.tbgrupomensajeid !== undefined) {
            return 'id:' + dato.tbgrupomensajeid;
        }
        return 'bloqueado:' + (dato.tbgrupomensajetexto || '') + ':' + (dato.tbgrupomensajefechahora || '');
    }

    function agregarMensaje(dato) {
        if (!el.mensajes) return;

        var clave = claveMensaje(dato);
        if (estado.vistos[clave]) return;
        estado.vistos[clave] = true;

        var fila = document.createElement('div');
        var propia = dato.tbperfilidemisor === perfilId;
        fila.className = 'chat-fila ' + (propia ? 'mía' : 'otra');

        var emisor = document.createElement('span');
        emisor.className = 'chat-emisor' + (propia ? ' propio' : '');
        emisor.textContent = dato.tbperfilnombre || ('Perfil ' + dato.tbperfilidemisor);
        fila.appendChild(emisor);

        var texto = document.createElement('p');
        texto.className = 'chat-burbuja mb-1';
        texto.textContent = dato.tbgrupomensajetexto;
        fila.appendChild(texto);

        var meta = document.createElement('span');
        meta.className = 'chat-hora';
        meta.textContent = hora(dato.tbgrupomensajefechahora);
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
        if (!estado.activa || estado.consultando || document.hidden) return;
        estado.consultando = true;

        var grupoId = estado.activa.tbgrupoid;

        consultar({
            accion: 'getMensajesGrupo',
            grupoId: grupoId,
            desdeId: estado.ultimoId
        }).then(function (respuesta) {
            if (!estado.activa || estado.activa.tbgrupoid !== grupoId) return;

            if (!respuesta.exito || !Array.isArray(respuesta.data)) {
                if (!respuesta.exito) mensaje(respuesta.mensaje || 'No se pudieron actualizar los mensajes.');
                return;
            }

            var nuevos = respuesta.data;
            if (nuevos.length === 0) return;

            nuevos.forEach(function (dato) {
                if (dato.tbgrupomensajeid && dato.tbgrupomensajeid <= estado.ultimoId) return;
                agregarMensaje(dato);
                if (dato.tbgrupomensajeid > estado.ultimoId) {
                    estado.ultimoId = dato.tbgrupomensajeid;
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
        activarEnvio(false);
        mensaje('');
    }

    function abrirGrupo(grupo) {
        estado.activa = grupo;
        estado.ultimoId = 0;
        estado.consultando = false;
        estado.avisoFormato = false;
        estado.vistos = {};

        if (el.catalogo) el.catalogo.classList.add('d-none');
        if (el.sala) el.sala.classList.remove('d-none');
        if (el.nombre) el.nombre.textContent = grupo.tbgruponombre;
        if (el.miembros) el.miembros.textContent = grupo.tbgrupocantidadmiembros + ' miembros';
        if (el.mensajes) el.mensajes.innerHTML = '';
        if (el.mensajesVacio) el.mensajesVacio.classList.add('d-none');
        activarEnvio(true);
        if (el.texto) el.texto.focus();

        var grupoId = grupo.tbgrupoid;

        // Marca el grupo como abierto para limpiar el contador de no leidos.
        enviar({ accion: 'marcarAbierto', grupoId: grupoId }).catch(function () {});

        consultar({
            accion: 'getMensajesGrupo',
            grupoId: grupoId,
            desdeId: 0
        }).then(function (respuesta) {
            if (!estado.activa || estado.activa.tbgrupoid !== grupoId) return;

            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudieron cargar los mensajes.');
                return;
            }

            if (!Array.isArray(respuesta.data)) {
                errorFormato('getMensajesGrupo', respuesta.data);
                mensaje('El servidor no devolvió una lista de mensajes válida.');
                return;
            }

            var lista = respuesta.data;
            if (lista.length === 0 && el.mensajesVacio) {
                el.mensajesVacio.classList.remove('d-none');
            }

            lista.forEach(function (dato) {
                agregarMensaje(dato);
                if (dato.tbgrupomensajeid > estado.ultimoId) {
                    estado.ultimoId = dato.tbgrupomensajeid;
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
        activarEnvio(false);

        enviar({
            accion: 'enviarMensajeGrupo',
            grupoId: estado.activa.tbgrupoid,
            texto: texto
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudo enviar el mensaje.');
                return;
            }
            agregarMensaje(respuesta.data);
            if (respuesta.data.tbgrupomensajeid > estado.ultimoId) {
                estado.ultimoId = respuesta.data.tbgrupomensajeid;
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

    function cargarGrupos() {
        if (estado.cargandoGrupos) return Promise.resolve();
        estado.cargandoGrupos = true;

        return consultar({ accion: 'getGrupos' })
            .then(function (respuesta) {
                if (!respuesta.exito) {
                    mensaje(respuesta.mensaje || 'No se pudieron cargar los grupos.');
                    if (el.cargando) el.cargando.hidden = true;
                    return;
                }

                if (!Array.isArray(respuesta.data)) {
                    errorFormato('getGrupos', respuesta.data);
                    if (el.error) {
                        el.error.textContent = 'El servidor no devolvió una lista de grupos válida.';
                        el.error.hidden = false;
                    }
                    if (el.cargando) el.cargando.hidden = true;
                    return;
                }

                if (el.error) el.error.hidden = true;
                estado.grupos = respuesta.data;
                pintarGrupos();
            })
            .catch(function (error) {
                if (error) errorFormato('getGrupos', error);
                if (el.error) {
                    el.error.textContent = 'Sin conexión con el servidor, reintenta.';
                    el.error.hidden = false;
                }
                if (el.cargando) el.cargando.hidden = true;
            })
            .then(function () {
                estado.cargandoGrupos = false;
            });
    }

    function buscarGrupo(grupoId) {
        var encontrado = null;
        estado.grupos.forEach(function (grupo) {
            if (grupo.tbgrupoid === grupoId) encontrado = grupo;
        });
        return encontrado;
    }

    function iniciar() {
        activarEnvio(false);
        if (el.form) el.form.addEventListener('submit', enviarMensaje);
        if (el.volver) el.volver.addEventListener('click', mostrarCatalogo);

        cargarGrupos().then(function () {
            if (grupoInicial > 0) {
                var grupo = buscarGrupo(grupoInicial);
                if (grupo) {
                    abrirGrupo(grupo);
                    return;
                }
                mensaje('El grupo solicitado no existe o no tienes acceso.');
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

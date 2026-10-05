(function () {
    'use strict';

    var config = window.CONFIG_CHAT || {};
    var apiUrl = config.apiUrl || 'apichat.php';
    var perfilId = parseInt(config.perfilId, 10) || 0;
    var conversacionInicial = parseInt(config.conversacionInicial, 10) || 0;
    var intervaloPolling = 3000;

    var el = {
        lista: document.getElementById('chatConversaciones'),
        listaVacia: document.getElementById('chatListaVacia'),
        nombre: document.getElementById('chatNombre'),
        mensajes: document.getElementById('chatMensajes'),
        mensajesVacio: document.getElementById('chatMensajesVacio'),
        form: document.getElementById('chatForm'),
        texto: document.getElementById('chatTexto'),
        enviar: document.getElementById('chatEnviar'),
        estado: document.getElementById('chatEstado')
    };

    var estado = {
        conversaciones: [],
        activa: null,
        ultimoId: 0,
        timer: null,
        consultando: false,
        enviando: false
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

    function pintarConversaciones() {
        if (!el.lista) return;
        el.lista.innerHTML = '';

        if (estado.conversaciones.length === 0) {
            if (el.listaVacia) el.listaVacia.classList.remove('d-none');
            return;
        }
        if (el.listaVacia) el.listaVacia.classList.add('d-none');

        estado.conversaciones.forEach(function (conversacion) {
            var li = document.createElement('li');
            li.className = 'chat-conversacion';
            li.dataset.conversacion = conversacion.tbconversacionid;
            if (estado.activa && estado.activa.tbconversacionid === conversacion.tbconversacionid) {
                li.classList.add('activa');
            }

            var boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'chat-conversacion-boton';

            var nombre = document.createElement('span');
            nombre.className = 'chat-conversacion-nombre';
            nombre.textContent = conversacion.tbperfilnombreotro || ('Perfil ' + conversacion.tbperfilidotro);
            boton.appendChild(nombre);

            if (conversacion.tbmensajenoleidos > 0) {
                var badge = document.createElement('span');
                badge.className = 'chat-badge';
                badge.textContent = conversacion.tbmensajenoleidos;
                boton.appendChild(badge);
            }

            var previo = document.createElement('span');
            previo.className = 'chat-conversacion-previo';
            var emisorYo = conversacion.tbmensajeemisorultimo === perfilId;
            previo.textContent = (conversacion.tbmensajetextoultimo
                ? (emisorYo ? 'Tú: ' : '') + conversacion.tbmensajetextoultimo
                : 'Sin mensajes todavía');
            boton.appendChild(previo);

            boton.addEventListener('click', function () {
                abrirConversacion(conversacion);
            });

            li.appendChild(boton);
            el.lista.appendChild(li);
        });
    }

    function agregarMensaje(dato) {
        if (!el.mensajes) return;

        var fila = document.createElement('div');
        fila.className = 'chat-fila ' + (dato.tbperfilidemisor === perfilId ? 'mía' : 'otra');
        fila.dataset.mensaje = dato.tbmensajeid;

        var texto = document.createElement('p');
        texto.className = 'chat-burbuja mb-1';
        texto.textContent = dato.tbmensajetexto;
        fila.appendChild(texto);

        var meta = document.createElement('span');
        meta.className = 'chat-hora';
        meta.textContent = hora(dato.tbmensajefechahora);
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
            accion: 'getMensajesNuevos',
            conversacionId: estado.activa.tbconversacionid,
            desdeId: estado.ultimoId
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudieron actualizar los mensajes.');
                return;
            }

            var nuevos = respuesta.data || [];
            if (nuevos.length === 0) return;

            nuevos.forEach(function (dato) {
                agregarMensaje(dato);
                if (dato.tbmensajeid > estado.ultimoId) {
                    estado.ultimoId = dato.tbmensajeid;
                }
            });
            scrollAlFinal();
            marcarLeidosActiva();
            cargarConversaciones();
        }).catch(function () {
            mensaje('Sin conexión con el servidor, reintentando...');
        }).then(function () {
            estado.consultando = false;
        });
    }

    function marcarLeidosActiva() {
        if (!estado.activa) return;
        enviar({
            accion: 'marcarLeidos',
            conversacionId: estado.activa.tbconversacionid
        }).catch(function () { });
    }

    function cargarConversaciones() {
        return consultar({ accion: 'getConversaciones' })
            .then(function (respuesta) {
                if (!respuesta.exito) {
                    mensaje(respuesta.mensaje || 'No se pudieron cargar las conversaciones.');
                    return;
                }
                estado.conversaciones = respuesta.data || [];
                pintarConversaciones();
            })
            .catch(function () {
                mensaje('Sin conexión con el servidor.');
            });
    }

    function abrirConversacion(conversacion) {
        estado.activa = conversacion;
        estado.ultimoId = 0;
        estado.consultando = false;

        if (el.nombre) {
            el.nombre.textContent = conversacion.tbperfilnombreotro
                || ('Perfil ' + conversacion.tbperfilidotro);
        }
        if (el.mensajes) el.mensajes.innerHTML = '';
        if (el.mensajesVacio) el.mensajesVacio.classList.add('d-none');
        activarEnvio(true);
        if (el.texto) el.texto.focus();
        pintarConversaciones();

        consultar({
            accion: 'getMensajesNuevos',
            conversacionId: conversacion.tbconversacionid,
            desdeId: 0
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudieron cargar los mensajes.');
                return;
            }
            (respuesta.data || []).forEach(function (dato) {
                agregarMensaje(dato);
                if (dato.tbmensajeid > estado.ultimoId) {
                    estado.ultimoId = dato.tbmensajeid;
                }
            });
            scrollAlFinal();
            marcarLeidosActiva();
            cargarConversaciones();
        }).catch(function () {
            mensaje('Sin conexión con el servidor.');
        });

        iniciarPolling();
    }

    function abrirConOtroPerfil(perfilIdOtro) {
        return enviar({ accion: 'obtenerOCrearConversacion', perfilId: perfilIdOtro })
            .then(function (respuesta) {
                if (!respuesta.exito) {
                    mensaje(respuesta.mensaje || 'No se pudo abrir la conversación.');
                    return;
                }
                var conversacion = respuesta.data;
                var existe = estado.conversaciones.some(function (item) {
                    return item.tbconversacionid === conversacion.tbconversacionid;
                });
                if (!existe) {
                    estado.conversaciones.unshift(conversacion);
                }
                abrirConversacion(conversacion);
            })
            .catch(function () {
                mensaje('Sin conexión con el servidor.');
            });
    }

    function enviarMensaje(evento) {
        evento.preventDefault();
        if (!estado.activa || estado.enviando) return;

        var texto = el.texto ? el.texto.value.trim() : '';
        if (texto === '') return;

        estado.enviando = true;
        if (el.enviar) el.enviar.disabled = true;

        enviar({
            accion: 'enviarMensaje',
            conversacionId: estado.activa.tbconversacionid,
            texto: texto
        }).then(function (respuesta) {
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudo enviar el mensaje.');
                return;
            }
            var dato = respuesta.data;
            agregarMensaje(dato);
            if (dato.tbmensajeid > estado.ultimoId) {
                estado.ultimoId = dato.tbmensajeid;
            }
            if (el.texto) el.texto.value = '';
            mensaje('');
            scrollAlFinal();
            cargarConversaciones();
        }).catch(function () {
            mensaje('No se pudo enviar el mensaje, reintenta.');
        }).then(function () {
            estado.enviando = false;
            activarEnvio(true);
            if (el.texto) el.texto.focus();
        });
    }

    function iniciar() {
        activarEnvio(false);
        if (el.form) el.form.addEventListener('submit', enviarMensaje);

        cargarConversaciones().then(function () {
            if (conversacionInicial > 0 && conversacionInicial !== perfilId) {
                abrirConOtroPerfil(conversacionInicial);
                return;
            }
            if (estado.conversaciones.length > 0) {
                abrirConversacion(estado.conversaciones[0]);
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
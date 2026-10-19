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
        enviando: false,
        vistos: {},
        cargandoConversaciones: false
    };

    function consultar(params) {
        return peticion(apiUrl + '?' + new URLSearchParams(params), {
            headers: { 'Accept': 'application/json' }
        });
    }

    function enviar(params) {
        var datos = new URLSearchParams();
        Object.keys(params).forEach(function (clave) {
            datos.append(clave, params[clave]);
        });
        return peticion(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: datos.toString()
        });
    }

    function mensaje(texto) {
        if (el.estado) el.estado.textContent = texto || '';
    }

    function hora(fecha) {
        if (!fecha) return '';
        var partes = String(fecha).replace('T', ' ').split(' ');
        var fechaPartes = (partes[0] || '').split('-');
        var fechaTexto = fechaPartes.length === 3
            ? fechaPartes[2] + '/' + fechaPartes[1] + '/' + fechaPartes[0]
            : (partes[0] || '');
        var horaTexto = partes[1] ? partes[1].slice(0, 5) : '';
        return (fechaTexto + ' ' + horaTexto).trim();
    }

    function scrollAlFinal() {
        if (el.mensajes) el.mensajes.scrollTop = el.mensajes.scrollHeight;
    }

    function pintarConversaciones() {
        if (!el.lista) return;
        el.lista.innerHTML = '';

        if (estado.conversaciones.length === 0) {
            if (el.listaVacia) {
                el.listaVacia.className = 'chat-vacio mb-2';
                el.listaVacia.style.color = '';
                el.listaVacia.textContent = 'Todavía no tienes conversaciones. Envía un mensaje desde tus afinidades abajo.';
                el.listaVacia.classList.remove('d-none');
            }
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

    function claveMensaje(dato) {
        if (dato.tbmensajeid !== null && dato.tbmensajeid !== undefined) {
            return 'id:' + dato.tbmensajeid;
        }
        return 'bloqueado:' + (dato.tbmensajetexto || '') + ':' + (dato.tbmensajefechahora || '');
    }

    function crearTarjetaComida(comida) {
        if (!comida) return null;
        var tarjeta = document.createElement('div');
        tarjeta.className = 'chat-tarjeta-comida mt-1 p-2 border rounded';
        tarjeta.style.background = 'rgba(255, 255, 255, 0.95)';
        tarjeta.style.maxWidth = '250px';
        tarjeta.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)';

        if (comida.imagenUrl) {
            var img = document.createElement('img');
            img.src = comida.imagenUrl;
            img.alt = comida.nombre || '';
            img.className = 'img-fluid rounded mb-1';
            img.style.maxHeight = '120px';
            img.style.width = '100%';
            img.style.objectFit = 'cover';
            img.onerror = function () { this.style.display = 'none'; };
            tarjeta.appendChild(img);
        }

        var titulo = document.createElement('div');
        titulo.className = 'fw-bold small text-truncate text-dark';
        titulo.textContent = comida.nombre || '';
        tarjeta.appendChild(titulo);

        if (comida.tipoNombre) {
            var badge = document.createElement('span');
            badge.className = 'badge bg-secondary mt-1';
            badge.style.fontSize = '0.7rem';
            badge.textContent = comida.tipoNombre;
            tarjeta.appendChild(badge);
        }

        return tarjeta;
    }

    function agregarMensaje(dato) {
        if (!el.mensajes) return;

        var clave = claveMensaje(dato);
        if (estado.vistos[clave]) return;
        estado.vistos[clave] = true;

        var fila = document.createElement('div');
        fila.className = 'chat-fila ' + (dato.tbperfilidemisor === perfilId ? 'mía' : 'otra');
        fila.dataset.mensaje = dato.tbmensajeid;

        var texto = document.createElement('p');
        texto.className = 'chat-burbuja mb-1';
        texto.textContent = dato.tbmensajetexto;
        fila.appendChild(texto);

        if (dato.comida) {
            var tc = crearTarjetaComida(dato.comida);
            if (tc) fila.appendChild(tc);
        }

        var meta = document.createElement('span');
        meta.className = 'chat-hora';
        meta.textContent = ' ' + hora(dato.tbmensajefechahora);
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

        var conversacionId = estado.activa.tbconversacionid;

        consultar({
            accion: 'getMensajesNuevos',
            conversacionId: conversacionId,
            desdeId: estado.ultimoId
        }).then(function (respuesta) {
            if (!estado.activa || estado.activa.tbconversacionid !== conversacionId) {
                return;
            }
            if (manejarSesion(respuesta)) {
                detenerPolling();
                return;
            }
            if (!respuesta.exito) {
                mensaje(respuesta.mensaje || 'No se pudieron actualizar los mensajes.');
                return;
            }

            var nuevos = respuesta.data || [];
            if (nuevos.length === 0) return;

            nuevos.forEach(function (dato) {
                if (dato.tbmensajeid && dato.tbmensajeid <= estado.ultimoId) {
                    return;
                }
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
        if (estado.cargandoConversaciones) return Promise.resolve();
        estado.cargandoConversaciones = true;

        if (estado.conversaciones.length === 0 && el.listaVacia) {
            el.listaVacia.className = 'small mb-2';
            el.listaVacia.style.color = 'var(--color-texto-suave)';
            el.listaVacia.textContent = 'Cargando conversaciones...';
            el.listaVacia.classList.remove('d-none');
        }

        return consultar({ accion: 'getConversaciones' })
            .then(function (respuesta) {
                if (manejarSesion(respuesta)) return;
                if (!respuesta.exito) {
                    mensaje(respuesta.mensaje || 'No se pudieron cargar las conversaciones.');
                    if (el.listaVacia && estado.conversaciones.length === 0) {
                        el.listaVacia.className = 'small mb-2 text-secondary';
                        el.listaVacia.textContent = 'No se pudieron cargar las conversaciones.';
                        el.listaVacia.classList.remove('d-none');
                    }
                    return;
                }
                estado.conversaciones = respuesta.data || [];
                pintarConversaciones();
            })
            .catch(function () {
                mensaje('Sin conexión con el servidor.');
                if (el.listaVacia && estado.conversaciones.length === 0) {
                    el.listaVacia.className = 'small mb-2 text-secondary';
                    el.listaVacia.textContent = 'Sin conexión con el servidor.';
                    el.listaVacia.classList.remove('d-none');
                }
            })
            .then(function () {
                estado.cargandoConversaciones = false;
            });
    }

    function abrirConversacion(conversacion) {
        estado.activa = conversacion;
        estado.ultimoId = 0;
        estado.consultando = false;
        estado.vistos = {};

        if (el.nombre) {
            el.nombre.textContent = conversacion.tbperfilnombreotro
                || ('Perfil ' + conversacion.tbperfilidotro);
        }
        if (el.mensajes) el.mensajes.innerHTML = '';
        if (el.mensajesVacio) el.mensajesVacio.classList.add('d-none');
        activarEnvio(true);
        if (el.texto) el.texto.focus();
        pintarConversaciones();

        var conversacionId = conversacion.tbconversacionid;

        consultar({
            accion: 'getMensajesNuevos',
            conversacionId: conversacionId,
            desdeId: 0
        }).then(function (respuesta) {
            if (!estado.activa || estado.activa.tbconversacionid !== conversacionId) {
                return;
            }
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
        activarEnvio(false);

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
        configurarBotonesAfinidad();

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

    var APIS_AFINIDAD = {
        comida: 'apicomida.php',
        deporte: 'apideporte.php',
        musica: 'apicancion.php'
    };

    // Caché de resultados por categoría para no repetir peticiones
    var _cacheCatChat = {};

    function escapeHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto || '';
        return div.innerHTML;
    }

    function renderAfinidades(categoria, respuesta) {
        var contenedor = document.getElementById('chatListaCoincidencias');
        if (!contenedor) return;
        contenedor.innerHTML = '';

        if (!respuesta || !respuesta.exito) {
            var msgErr = (respuesta && respuesta.mensaje) ? respuesta.mensaje : 'No se pudieron obtener las coincidencias.';
            // Detectar mensajes de "sin datos" para dar mensaje amigable con enlace
            var sinDatos = /swipe|resultado|calcul|m.nimo|mini-juego/i.test(msgErr);
            var p = document.createElement('p');
            p.className = 'small mb-0';
            p.style.color = 'var(--color-texto-suave)';
            if (sinDatos) {
                var catNombre = categoria === 'comida' ? 'Comida' : (categoria === 'deporte' ? 'Deporte' : 'Música');
                if (categoria !== 'musica') {
                    p.innerHTML = 'Aún no tienes afinidades de <strong>' + escapeHtml(catNombre) + '</strong>. ' +
                        'Juega el mini-juego de <a href="index.php?vista=descubrir' + escapeHtml(categoria) + '" style="color:var(--color-primario)">' + escapeHtml(catNombre) + '</a> para generarlas.';
                } else {
                    p.innerHTML = 'Aún no tienes afinidades de <strong>Música</strong>. ' +
                        'Escucha canciones en <a href="index.php?vista=cliente" style="color:var(--color-primario)">Inicio</a> para generar tu perfil musical.';
                }
            } else {
                p.textContent = msgErr;
            }
            contenedor.appendChild(p);
            return;
        }

        var datos = respuesta.data || [];
        if (datos.length === 0) {
            var vacio = document.createElement('p');
            vacio.className = 'small mb-0';
            vacio.style.color = 'var(--color-texto-suave)';
            vacio.textContent = 'Aún no hay coincidencias en esta categoría.';
            contenedor.appendChild(vacio);
            return;
        }

        var lista = document.createElement('ul');
        lista.className = 'coincidencias-lista mt-2';
        datos.forEach(function (coincidencia) {
            var li = document.createElement('li');
            li.className = 'd-flex justify-content-between align-items-center gap-2';

            var nombreMostrar = coincidencia.tbperfilnombre || ('Perfil ' + coincidencia.tbperfilid);
            if (coincidencia.mostrarNombres === false) {
                nombreMostrar = 'Perfil reservado';
            }

            var info = document.createElement('div');
            info.className = 'small fw-semibold text-truncate';
            info.style.color = 'var(--color-texto)';
            info.textContent = nombreMostrar;

            var boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'btn btn-sm';
            boton.style.cssText = 'background:var(--color-primario);color:#fff;border:none;border-radius:var(--radio-control);font-size:0.8rem;padding:0.25rem 0.65rem;white-space:nowrap;flex-shrink:0;';
            boton.textContent = 'Enviar mensaje';
            boton.addEventListener('click', function () {
                abrirConOtroPerfil(coincidencia.tbperfilid);
            });

            li.appendChild(info);
            li.appendChild(boton);
            lista.appendChild(li);
        });
        contenedor.appendChild(lista);
    }

    function cargarAfinidadesCat(cat) {
        var urlApi = APIS_AFINIDAD[cat] || APIS_AFINIDAD.comida;
        peticion(urlApi + '?accion=buscarCoincidencias')
            .then(function (respuesta) {
                _cacheCatChat[cat] = respuesta;
                // Renderizar solo si este tab está activo ahora
                var btnActivo = document.querySelector('#chatAfinidadesSeccion [data-cat].active');
                if (btnActivo && btnActivo.dataset.cat === cat) {
                    renderAfinidades(cat, respuesta);
                }
            })
            .catch(function () {
                _cacheCatChat[cat] = { exito: false, mensaje: 'Error de conexión.' };
                var btnActivo = document.querySelector('#chatAfinidadesSeccion [data-cat].active');
                if (btnActivo && btnActivo.dataset.cat === cat) {
                    renderAfinidades(cat, _cacheCatChat[cat]);
                }
            });
    }

    function configurarBotonesAfinidad() {
        var botones = document.querySelectorAll('#chatAfinidadesSeccion [data-cat]');
        var contenedor = document.getElementById('chatListaCoincidencias');

        botones.forEach(function (btn) {
            btn.addEventListener('click', function () {
                botones.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                var cat = btn.dataset.cat;
                if (_cacheCatChat[cat] !== undefined) {
                    // Ya cargado: mostrar caché instantáneo
                    renderAfinidades(cat, _cacheCatChat[cat]);
                } else {
                    if (contenedor) {
                        contenedor.innerHTML = '<p class="small mb-0" style="color:var(--color-texto-suave)">Cargando afinidades...</p>';
                    }
                    cargarAfinidadesCat(cat);
                }
            });
        });

        // Pre-cargar las 3 categorías en paralelo al abrir el chat
        if (contenedor) {
            contenedor.innerHTML = '<p class="small mb-0" style="color:var(--color-texto-suave)">Cargando afinidades...</p>';
        }
        ['comida', 'deporte', 'musica'].forEach(function (cat) {
            cargarAfinidadesCat(cat);
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && estado.activa) {
            polling();
        }
    });

    document.addEventListener('DOMContentLoaded', iniciar);
})();

(function () {
    'use strict';

    var CC_API_URL    = 'apicancionchat.php';
    var CC_INTERVALO  = 3000;  
    var CC_PERFIL_ID  = 0;     

    (function inicializarConfig() {
        var cfg = window.CC_CONFIG || {};
        CC_PERFIL_ID = parseInt(cfg.perfilId, 10) || 0;
    })();

    var ccEstado = {
        cancionActiva: 0,   
        ultimoId: 0,
        timer: null,      
        consultando: false,
        enviando: false,
        avisoFormato: false,
        vistos: {}          
    };

    var ccEl = {
        lista:   null,
        estado:  null,
        texto:   null,
        btnEnviar: null
    };

    function ccResolverDom() {
        ccEl.lista    = document.getElementById('ccMensajes');
        ccEl.estado   = document.getElementById('ccEstado');
        ccEl.texto    = document.getElementById('ccTexto');
        ccEl.btnEnviar = document.getElementById('ccBtnEnviar');
    }

    function ccEscapar(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(String(str || '')));
        return d.innerHTML;
    }

    function ccHora(fechaHora) {
        return formatearHora ? formatearHora(fechaHora) : (String(fechaHora || '').substring(11, 16));
    }

    function ccMostrarEstado(texto) {
        if (ccEl.estado) ccEl.estado.textContent = texto || '';
    }

    function ccScrollAbajo() {
        if (ccEl.lista) ccEl.lista.scrollTop = ccEl.lista.scrollHeight;
    }

    function ccClave(dato) {
        if (dato.tbcancionchatid !== null && dato.tbcancionchatid !== undefined) {
            return 'id:' + dato.tbcancionchatid;
        }
        return 'noid:' + (dato.tbcancionchattexto || '') + ':' + (dato.tbcancionchatfechahora || '');
    }

    function ccAgregarMensaje(dato) {
        if (!ccEl.lista) return;

        var clave = ccClave(dato);
        if (ccEstado.vistos[clave]) return;
        ccEstado.vistos[clave] = true;

        var fila = document.createElement('div');
        fila.className = 'cc-fila' + (dato.tbperfilid === CC_PERFIL_ID ? ' cc-propia' : '');

        var encabezado = document.createElement('div');
        encabezado.className = 'cc-encabezado';
        encabezado.innerHTML =
            '<span class="cc-nombre">' + ccEscapar(dato.tbperfilnombre || ('Perfil ' + dato.tbperfilid)) + '</span>' +
            '<span class="cc-hora">' + ccEscapar(ccHora(dato.tbcancionchatfechahora)) + '</span>';

        var burbuja = document.createElement('p');
        burbuja.className = 'cc-burbuja';
        burbuja.textContent = dato.tbcancionchattexto || '';

        fila.appendChild(encabezado);
        fila.appendChild(burbuja);
        ccEl.lista.appendChild(fila);
    }

    function ccActivarEnvio(activo) {
        if (ccEl.texto)    ccEl.texto.disabled    = !activo;
        if (ccEl.btnEnviar) ccEl.btnEnviar.disabled = !activo;
    }

    function ccDetenerPolling() {
        if (ccEstado.timer !== null) {
            clearInterval(ccEstado.timer);
            ccEstado.timer = null;
        }
    }

    function ccIniciarPolling() {
        ccDetenerPolling();
        ccEstado.timer = setInterval(ccPolling, CC_INTERVALO);
    }

    function ccPolling() {
        var cancionId = ccEstado.cancionActiva;
        if (!cancionId || ccEstado.consultando) return;
        if (document.hidden) return;

        ccEstado.consultando = true;

        var params = new URLSearchParams({
            accion: 'getMensajes',
            cancionId: cancionId,
            desdeId: ccEstado.ultimoId
        });

        peticion(CC_API_URL + '?' + params.toString(), {
            headers: { Accept: 'application/json' }
        }).then(function (res) {
            if (ccEstado.cancionActiva !== cancionId) return;

            if (manejarSesion(res)) {
                ccDetenerPolling();
                return;
            }
            if (!res.exito) {
                ccMostrarEstado(res.mensaje || 'Error al cargar mensajes.');
                return;
            }
            if (!Array.isArray(res.data)) {
                if (!ccEstado.avisoFormato) {
                    console.error('cancionchat.js: formato inesperado en getMensajes ->', res.data);
                    ccEstado.avisoFormato = true;
                }
                ccMostrarEstado('Respuesta del servidor inesperada.');
                return;
            }

            var nuevos = res.data;
            if (nuevos.length === 0) return;

            var i;
            for (i = 0; i < nuevos.length; i++) {
                var dato = nuevos[i];
                if (dato.tbcancionchatid && dato.tbcancionchatid > ccEstado.ultimoId) {
                    ccEstado.ultimoId = dato.tbcancionchatid;
                }
                ccAgregarMensaje(dato);
            }
            ccScrollAbajo();

        }).catch(function (err) {
            ccMostrarEstado('Sin conexión con el servidor, reintentando…');
            console.error('cancionchat.js: error de polling ->', err);
        }).then(function () {
            ccEstado.consultando = false;
        });
    }

    var ccListenerEnvioRegistrado = false;

    function ccRegistrarListenerEnvio() {
        if (ccListenerEnvioRegistrado) return;
        ccListenerEnvioRegistrado = true;

        if (ccEl.btnEnviar) {
            ccEl.btnEnviar.addEventListener('click', ccEnviar);
        }
        if (ccEl.texto) {
            ccEl.texto.addEventListener('keydown', function (e) {
                /* Enter sin Shift envía */
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    ccEnviar();
                }
            });
        }
    }

    function ccEnviar() {
        var cancionId = ccEstado.cancionActiva;
        if (!cancionId || ccEstado.enviando) return;

        var texto = ccEl.texto ? ccEl.texto.value : '';

        if (!texto || !texto.trim()) {
            ccMostrarEstado('Escribe un mensaje antes de enviar.');
            return;
        }
        if (texto.trim().length > 300) {
            ccMostrarEstado('El mensaje no puede superar los 300 caracteres.');
            return;
        }

        ccEstado.enviando = true;
        ccActivarEnvio(false);
        ccMostrarEstado('');

        var datos = new URLSearchParams();
        datos.append('accion', 'enviarMensaje');
        datos.append('cancionId', cancionId);
        datos.append('texto', texto.trim());

        peticion(CC_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: datos.toString()
        }).then(function (res) {
            if (ccEstado.cancionActiva !== cancionId) return;

            if (manejarSesion(res)) {
                ccDetenerPolling();
                return;
            }
            if (!res.exito) {
                ccMostrarEstado(res.mensaje || 'No se pudo enviar el mensaje.');
                return;
            }

            if (!res.data || typeof res.data !== 'object') {
                console.error('cancionchat.js: formato inesperado en enviarMensaje ->', res.data);
                return;
            }

            if (ccEl.texto) ccEl.texto.value = '';
            ccAgregarMensaje(res.data);
            if (res.data.tbcancionchatid) {
                ccEstado.ultimoId = Math.max(ccEstado.ultimoId, res.data.tbcancionchatid);
            }
            ccScrollAbajo();

        }).catch(function (err) {
            ccMostrarEstado('Error de conexión al enviar.');
            console.error('cancionchat.js: error al enviar ->', err);
        }).then(function () {
            ccEstado.enviando = false;
            ccActivarEnvio(true);
        });
    }
    window.abrirCancionChat = function (cancionId) {
        cancionId = parseInt(cancionId, 10) || 0;
        if (!cancionId) return;

        ccResolverDom();

        ccDetenerPolling();
        ccEstado.cancionActiva = cancionId;
        ccEstado.ultimoId      = 0;
        ccEstado.consultando   = false;
        ccEstado.enviando      = false;
        ccEstado.avisoFormato  = false;
        ccEstado.vistos        = {};

        if (ccEl.lista)   ccEl.lista.innerHTML = '';
        ccMostrarEstado('');
        ccActivarEnvio(true);
        if (ccEl.texto) ccEl.texto.value = '';

        ccRegistrarListenerEnvio();

        ccPolling();
        ccIniciarPolling();
    };

    window.cerrarCancionChat = function () {
        ccDetenerPolling();
        ccEstado.cancionActiva = 0;
        ccEstado.ultimoId      = 0;
        ccEstado.consultando   = false;
        ccEstado.enviando      = false;
        ccEstado.vistos        = {};

        if (ccEl.lista)  ccEl.lista.innerHTML = '';
        ccMostrarEstado('');
        ccActivarEnvio(false);
    };

})();

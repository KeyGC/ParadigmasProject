(function () {
    'use strict';

    var config = window.CONFIG_JUEGO || {};
    var apiUrl = config.apiUrl || 'apicomida.php';
    var idCampo = config.idCampo || 'comidaId';
    var nombreOpcion = config.nombreOpcion || 'opción';

    var el = {
        tarjeta: document.getElementById('tarjetaJuego'),
        imagen: document.getElementById('tarjetaImagen'),
        nombre: document.getElementById('tarjetaNombre'),
        tipo: document.getElementById('tarjetaTipo'),
        sentido: document.getElementById('sentidoJuego'),
        racha: document.getElementById('rachaJuego'),
        progreso: document.getElementById('progresoJuego'),
        vacio: document.getElementById('juegoVacio'),
        mensaje: document.getElementById('juegoMensaje'),
        resultado: document.getElementById('resultadoJuego'),
        resultadoSub: document.getElementById('resultadoSub'),
        resultadoPuestos: document.getElementById('resultadoPuestos'),
        listaCoincidencias: document.getElementById('listaCoincidencias'),
        btnLike: document.getElementById('btnLike'),
        btnDislike: document.getElementById('btnDislike'),
        btnResultado: document.getElementById('btnResultado'),
        btnCoincidencias: document.getElementById('btnCoincidencias'),
        btnJugarDeNuevo: document.getElementById('btnJugarDeNuevo')
    };

    var totalPorRonda = 20;
    var umbralSwipe = 80;

    var estado = { mazo: [], indice: 0, racha: 0, rondas: 0 };
    var arrastre = { x: 0, y: 0, dx: 0, dy: 0, activo: false };
    var protegido = false;

    function esperar(ms) {
        return new Promise(function (resolver) { setTimeout(resolver, ms); });
    }

    function mostrarMensaje(texto) {
        if (el.mensaje) el.mensaje.textContent = texto || '';
    }

    function consultar(params) {
        return fetch(apiUrl + '?' + new URLSearchParams(params), {
            headers: { 'Accept': 'application/json' }
        }).then(function (respuesta) {
            return respuesta.json();
        });
    }

    function enviarSwipe(id, gusto) {
        var datos = new URLSearchParams();
        datos.append('accion', 'registrarSwipe');
        datos.append(idCampo, id);
        datos.append('gusto', gusto);
        return fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: datos.toString()
        }).then(function (respuesta) {
            return respuesta.json();
        });
    }

    function barajar(array) {
        var copia = array.slice();
        for (var i = copia.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var temporal = copia[i];
            copia[i] = copia[j];
            copia[j] = temporal;
        }
        return copia;
    }

    function actualizarBarra() {
        var total = estado.mazo.length;
        var vistos = Math.min(estado.indice, total);
        if (el.progreso) {
            var texto = vistos + ' / ' + total;
            if (estado.rondas > 0) {
                texto += ' · ronda ' + (estado.rondas + 1);
            }
            el.progreso.textContent = texto;
        }
        if (el.racha) {
            el.racha.textContent = estado.racha > 0 ? '🔥 ' + estado.racha + ' en fila' : 'Sin racha';
        }
    }

    function cartaActual() {
        return estado.mazo[estado.indice];
    }

    function cargarMazo(reciclar) {
        return consultar({ accion: 'getItems', cantidad: totalPorRonda, reciclar: reciclar ? '1' : '0' })
            .then(function (respuesta) {
                if (!respuesta.exito) {
                    throw new Error(respuesta.mensaje || 'No se pudieron cargar más opciones.');
                }
                var items = respuesta.data || [];
                if (items.length === 0 && !reciclar) {
                    return cargarMazo(true);
                }
                return barajar(items);
            });
    }

    function mostrarCarta(item) {
        if (!el.tarjeta || !item) return false;

        if (el.vacio) el.vacio.classList.add('d-none');
        el.tarjeta.classList.remove('d-none');
        el.tarjeta.style.transition = 'none';
        el.tarjeta.style.transform = '';
        el.tarjeta.style.opacity = '';

        if (el.nombre) el.nombre.textContent = item.nombre;
        if (el.tipo) el.tipo.textContent = item.tipoNombre;
        if (el.sentido) el.sentido.className = 'juego-sentido d-none';

        if (el.imagen) {
            el.imagen.style.display = '';
            el.imagen.src = item.imagenUrl || '';
            el.imagen.alt = item.nombre;
        }
        return true;
    }

    function siguienteCarta() {
        estado.indice++;
        actualizarBarra();

        if (estado.indice >= estado.mazo.length) {
            mostrarMensaje('Cargando más opciones...');
            cargarMazo(false).then(function (nuevoMazo) {
                if (nuevoMazo.length === 0) {
                    mostrarMensaje('No hay más opciones de ' + nombreOpcion + ' por mostrar.');
                    if (el.vacio) el.vacio.classList.remove('d-none');
                    return;
                }
                estado.mazo = nuevoMazo;
                estado.indice = 0;
                estado.rondas++;
                mostrarMensaje('');
                if (mostrarCarta(cartaActual())) {
                    asignarArrastre();
                }
                actualizarBarra();
            }).catch(function (error) {
                mostrarMensaje(error.message || 'Error al cargar más opciones.');
            });
            return;
        }

        if (mostrarCarta(cartaActual())) {
            asignarArrastre();
        }
    }

    function animarSalida(gusto) {
        if (!el.tarjeta) return Promise.resolve();
        el.tarjeta.style.transition = 'transform .3s ease, opacity .3s ease';
        var destino = (gusto === 'like' ? 1 : -1) * (window.innerWidth > 0 ? window.innerWidth : 420);
        el.tarjeta.style.transform = 'translate(' + destino + 'px, -80px) rotate(' + (gusto === 'like' ? 18 : -18) + 'deg)';
        el.tarjeta.style.opacity = '0';
        return esperar(320);
    }

    function decidir(gusto) {
        var item = cartaActual();
        if (protegido || !item) return;
        protegido = true;

        estado.racha = gusto === 'like' ? estado.racha + 1 : 0;
        actualizarBarra();

        enviarSwipe(item.id, gusto)
            .then(function (respuesta) {
                if (!respuesta.exito) {
                    throw new Error(respuesta.mensaje || 'Error al registrar tu elección.');
                }
                mostrarMensaje(gusto === 'like' ? '¡Te encanta! ❤️' : 'Descartado');
            })
            .catch(function (error) {
                mostrarMensaje(error.message || 'Error al registrar tu elección.');
            })
            .then(function () {
                return animarSalida(gusto);
            })
            .then(function () {
                protegido = false;
                siguienteCarta();
            });
    }

    function asignarArrastre() {
        if (!el.tarjeta) return;
        el.tarjeta.onpointerdown = iniciarArrastre;
        el.tarjeta.onpointermove = moverArrastre;
        el.tarjeta.onpointerup = soltarArrastre;
        el.tarjeta.onpointercancel = soltarArrastre;
    }

    function iniciarArrastre(evento) {
        if (protegido || !el.tarjeta) return;
        evento.preventDefault();
        arrastre = { x: evento.clientX, y: evento.clientY, dx: 0, dy: 0, activo: true };
        el.tarjeta.style.transition = 'none';
    }

    function moverArrastre(evento) {
        if (!arrastre.activo) return;
        evento.preventDefault();
        var dx = evento.clientX - arrastre.x;
        var dy = evento.clientY - arrastre.y;
        arrastre.dx = dx;
        arrastre.dy = dy;
        el.tarjeta.style.transform = 'translate(' + dx + 'px, ' + dy * 0.6 + 'px) rotate(' + (dx * 0.06) + 'deg)';

        if (el.sentido) {
            if (dx > 30) {
                el.sentido.textContent = 'ME GUSTA';
                el.sentido.className = 'juego-sentido positivo';
            } else if (dx < -30) {
                el.sentido.textContent = 'NO ME GUSTA';
                el.sentido.className = 'juego-sentido negativo';
            } else {
                el.sentido.className = 'juego-sentido d-none';
            }
        }
    }

    function soltarArrastre() {
        if (!arrastre.activo || !el.tarjeta) return;
        arrastre.activo = false;
        var dx = arrastre.dx;

        if (el.sentido) el.sentido.className = 'juego-sentido d-none';

        if (dx > umbralSwipe) {
            decidir('like');
        } else if (dx < -umbralSwipe) {
            decidir('dislike');
        } else {
            el.tarjeta.style.transition = 'transform .2s ease';
            el.tarjeta.style.transform = '';
        }
    }

    function mostrarResultado(respuesta) {
        if (!respuesta.exito) {
            mostrarMensaje(respuesta.mensaje || 'Aún no puedes ver tu resultado.');
            return;
        }
        mostrarMensaje('');
        if (el.resultado) el.resultado.classList.remove('d-none');

        if (el.resultadoSub) {
            el.resultadoSub.textContent = 'Basado en ' + respuesta.totalSwipes + ' swipes · ' +
                (respuesta.desdeCache ? 'actualizado' : 'recién calculado');
        }

        if (el.resultadoPuestos) {
            el.resultadoPuestos.innerHTML = '';
            (respuesta.resultados || []).forEach(function (resultado, indice) {
                var li = document.createElement('li');
                li.textContent = '#' + (indice + 1) + ' ' + resultado.tipo;
                var span = document.createElement('span');
                span.className = 'text-secondary';
                span.textContent = resultado.porcentaje + '%';
                li.appendChild(span);
                el.resultadoPuestos.appendChild(li);
            });
        }
    }

    function mostrarCoincidencias(respuesta) {
        if (!respuesta.exito) {
            mostrarMensaje(respuesta.mensaje || 'No se pudieron obtener las coincidencias.');
            return;
        }
        if (!el.listaCoincidencias) return;

        var datos = respuesta.data || [];
        el.listaCoincidencias.innerHTML = '';

        if (datos.length === 0) {
            var vacio = document.createElement('p');
            vacio.className = 'text-secondary mb-0';
            vacio.textContent = 'Aún no hay coincidencias por este gusto. Invita a más personas a jugar.';
            el.listaCoincidencias.appendChild(vacio);
            return;
        }

        var lista = document.createElement('ul');
        lista.className = 'coincidencias-lista';
        datos.forEach(function (coincidencia) {
            var li = document.createElement('li');
            var ubicacion = coincidencia.tbubicacionprovincia
                ? coincidencia.tbubicacionprovincia + ', ' + coincidencia.tbubicacioncanton
                : 'Ubicación no definida';
            var afinidad = Math.round((coincidencia.score || 0) * 100);
            li.textContent = coincidencia.tbperfilnombre + ' · ' + ubicacion + ' · afinidad ' + afinidad + '%';
            lista.appendChild(li);
        });
        el.listaCoincidencias.appendChild(lista);
    }

    function iniciar() {
        cargarMazo(false).then(function (items) {
            if (items.length === 0) {
                mostrarMensaje('No hay opciones de ' + nombreOpcion + ' disponibles todavía.');
                if (el.vacio) el.vacio.classList.remove('d-none');
                return;
            }
            estado.mazo = items;
            if (mostrarCarta(cartaActual())) {
                asignarArrastre();
            }
            actualizarBarra();
        }).catch(function (error) {
            mostrarMensaje(error.message || 'Error al iniciar el juego.');
        });
    }

    if (el.btnLike) el.btnLike.addEventListener('click', function () { decidir('like'); });
    if (el.btnDislike) el.btnDislike.addEventListener('click', function () { decidir('dislike'); });
    if (el.btnResultado) el.btnResultado.addEventListener('click', function () {
        consultar({ accion: 'generarPerfilado' }).then(mostrarResultado);
    });
    if (el.btnCoincidencias) el.btnCoincidencias.addEventListener('click', function () {
        consultar({ accion: 'buscarCoincidencias' }).then(mostrarCoincidencias);
    });
    if (el.btnJugarDeNuevo) el.btnJugarDeNuevo.addEventListener('click', function () {
        window.location.reload();
    });

    document.addEventListener('DOMContentLoaded', iniciar);
})();
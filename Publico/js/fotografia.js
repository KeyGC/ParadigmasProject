(function () {
    'use strict';

    var API_URL = 'apireto.php';
    var retoActualId = window.RETO_INICIAL_ID || null;
    var modalProponer = null;

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatearFecha(cadena) {
        if (!cadena) return '';
        var d = new Date(cadena.replace(' ', 'T'));
        if (isNaN(d.getTime())) return cadena;
        return d.toLocaleDateString('es-CR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function badgeEstadoReto(estado) {
        switch (estado) {
            case 'participacion':
                return '<span class="badge bg-success">En Participación</span>';
            case 'votacion':
                return '<span class="badge bg-warning text-dark">En Votación</span>';
            case 'espera_votacion':
                return '<span class="badge bg-info text-dark">Esperando Votación</span>';
            case 'finalizado':
                return '<span class="badge bg-secondary">Finalizado</span>';
            default:
                return '<span class="badge bg-light text-dark">' + escapeHtml(estado) + '</span>';
        }
    }

    function cargarRetosActivos() {
        var contenedor = document.getElementById('contenedorRetosActivos');
        if (!contenedor) return;
        contenedor.innerHTML = '<p class="text-secondary">Cargando retos fotográficos...</p>';

        peticion(API_URL + '?accion=getRetos&filtro=activos')
            .then(function (res) {
                if (!res || !res.exito || !res.data) {
                    contenedor.innerHTML = '<p class="text-danger small">No se pudieron cargar los retos.</p>';
                    return;
                }

                var retos = res.data;
                if (retos.length === 0) {
                    contenedor.innerHTML = '<div class="col-12"><div class="p-4 text-center border rounded bg-white"><p class="text-secondary mb-2">No hay retos activos en este momento.</p><button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById(\'btnIrProponerReto\').click();">Sé el primero en proponer un reto</button></div></div>';
                    return;
                }

                var html = '';
                retos.forEach(function (r) {
                    var badge = badgeEstadoReto(r.estado);
                    var accionBtn = '<button type="button" class="btn btn-outline-primary btn-sm btn-ver-reto" data-id="' + r.tbretoid + '">Ver Detalles</button>';

                    if (r.estado === 'participacion') {
                        accionBtn = '<button type="button" class="btn btn-primary btn-sm btn-ver-reto" data-id="' + r.tbretoid + '">Inscribir Fotografías (' + r.misFotosCount + '/3)</button>';
                    } else if (r.estado === 'votacion') {
                        accionBtn = '<button type="button" class="btn btn-warning text-dark btn-sm btn-ver-reto" data-id="' + r.tbretoid + '">Votar Fotografías ★</button>';
                    }

                    html += '<div class="col-12 col-md-6 col-lg-4">' +
                        '<div class="foto-card h-100 d-flex flex-column p-4">' +
                            '<div class="d-flex justify-content-between align-items-start mb-2">' +
                                badge +
                                '<small class="text-muted">' + r.totalfotos + ' fotos</small>' +
                            '</div>' +
                            '<h3 class="h5 mb-2 text-primary fw-bold">' + escapeHtml(r.tbretotematica) + '</h3>' +
                            '<p class="text-secondary small mb-3 flex-grow-1" style="min-height: 48px;">' + escapeHtml(r.tbretodescripcion) + '</p>' +
                            '<div class="border-top pt-2 mb-3">' +
                                '<div class="small text-muted d-flex justify-content-between">' +
                                    '<span>Fin participación:</span>' +
                                    '<span class="fw-semibold">' + formatearFecha(r.tbretofechafinparticipacion) + '</span>' +
                                '</div>' +
                                '<div class="small text-muted d-flex justify-content-between">' +
                                    '<span>Fin votación:</span>' +
                                    '<span class="fw-semibold">' + formatearFecha(r.tbretofechafinvotacion) + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="mt-auto d-flex justify-content-between align-items-center">' +
                                '<small class="text-secondary">Por: ' + escapeHtml(r.creadornombre) + '</small>' +
                                accionBtn +
                            '</div>' +
                        '</div>' +
                    '</div>';
                });

                contenedor.innerHTML = html;

                contenedor.querySelectorAll('.btn-ver-reto').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        abrirDetalleReto(this.getAttribute('data-id'));
                    });
                });
            })
            .catch(function () {
                contenedor.innerHTML = '<p class="text-danger small">Error de conexión al cargar los retos.</p>';
            });
    }

    function abrirDetalleReto(retoId) {
        retoActualId = parseInt(retoId, 10);
        if (!retoActualId) return;

        document.getElementById('vistaListaRetos').classList.add('d-none');
        document.getElementById('vistaDetalleReto').classList.remove('d-none');

        window.scrollTo({ top: 0, behavior: 'smooth' });

        cargarDatosReto(retoActualId);
    }

    function cerrarDetalleReto() {
        retoActualId = null;
        document.getElementById('vistaDetalleReto').classList.add('d-none');
        document.getElementById('vistaListaRetos').classList.remove('d-none');
        cargarRetosActivos();
    }

    function cargarDatosReto(retoId) {
        var contenedorGaleria = document.getElementById('contenedorGaleriaReto');
        contenedorGaleria.innerHTML = '<p class="text-secondary">Cargando fotografías y ranking...</p>';

        peticion(API_URL + '?accion=getRanking&retoId=' + encodeURIComponent(retoId))
            .then(function (res) {
                if (!res || !res.exito || !res.data) {
                    contenedorGaleria.innerHTML = '<p class="text-danger">No se pudo cargar la información del reto.</p>';
                    return;
                }

                var reto = res.data.reto;
                var fotos = res.data.fotos || [];

                document.getElementById('detalleRetoTematica').textContent = reto.tbretotematica;
                document.getElementById('detalleRetoDescripcion').textContent = reto.tbretodescripcion;
                document.getElementById('detalleRetoCreador').textContent = 'Propuesto por ' + reto.creadornombre;
                document.getElementById('detalleRetoFechasParticipacion').textContent = formatearFecha(reto.tbretofechainicioparticipacion) + ' al ' + formatearFecha(reto.tbretofechafinparticipacion);
                document.getElementById('detalleRetoFechasVotacion').textContent = formatearFecha(reto.tbretofechainiciovotacion) + ' al ' + formatearFecha(reto.tbretofechafinvotacion);

                var badgeContenedor = document.getElementById('detalleRetoBadgeEstado');
                badgeContenedor.outerHTML = '<span id="detalleRetoBadgeEstado">' + badgeEstadoReto(reto.estado) + '</span>';

                var seccionInscribir = document.getElementById('seccionInscripcionFoto');
                document.getElementById('inscribirRetoId').value = reto.tbretoid;
                document.getElementById('detalleRetoContadorMisFotos').textContent = reto.misFotosCount + ' / 3 fotos presentadas';

                if (reto.estado === 'participacion') {
                    seccionInscribir.classList.remove('d-none');
                    var btnEnviar = document.getElementById('btnEnviarFoto');
                    if (reto.misFotosCount >= 3) {
                        btnEnviar.disabled = true;
                        btnEnviar.textContent = 'Límite alcanzado (3/3 fotos)';
                    } else {
                        btnEnviar.disabled = false;
                        btnEnviar.textContent = 'Inscribir Fotografía (' + (reto.misFotosCount + 1) + '/3)';
                    }
                } else {
                    seccionInscribir.classList.add('d-none');
                }

                renderizarMisFotos(reto.misFotos || [], reto.puedeRetirar);

                renderizarGaleriaRanking(fotos, reto);
            })
            .catch(function () {
                contenedorGaleria.innerHTML = '<p class="text-danger">Error de conexión al cargar el reto.</p>';
            });
    }

    function renderizarMisFotos(misFotos, puedeRetirar) {
        var seccion = document.getElementById('seccionMisFotosReto');
        var contenedor = document.getElementById('contenedorMisFotosReto');
        if (!seccion || !contenedor) return;

        if (!misFotos || misFotos.length === 0) {
            seccion.classList.add('d-none');
            contenedor.innerHTML = '';
            return;
        }

        seccion.classList.remove('d-none');
        var html = '';

        misFotos.forEach(function (f) {
            var btnRetirar = puedeRetirar
                ? '<button type="button" class="btn btn-outline-danger btn-sm btn-retirar-foto w-100" data-id="' + f.tbretofotoid + '">Retirar de este reto</button>'
                : '';

            html += '<div class="col-12 col-md-4">' +
                '<div class="foto-card p-3 h-100 d-flex flex-column">' +
                    '<div class="foto-img-container rounded mb-2">' +
                        '<img src="' + escapeHtml(f.tbretofotoarchivo) + '" alt="' + escapeHtml(f.tbretofototitulo) + '">' +
                    '</div>' +
                    '<h4 class="h6 mb-1 fw-bold text-truncate">' + escapeHtml(f.tbretofototitulo) + '</h4>' +
                    '<div class="d-flex flex-wrap gap-1 mb-2">' +
                        '<span class="badge bg-light text-dark border">' + escapeHtml(f.tbretofotocomposicion || 'Normal') + '</span>' +
                        '<span class="badge bg-light text-dark border">' + escapeHtml(f.tbretofotoestilo || 'Clásico') + '</span>' +
                        '<span class="badge bg-light text-dark border">' + escapeHtml(f.tbretofotocolordominante || 'Color') + '</span>' +
                    '</div>' +
                    '<div class="mt-auto pt-2">' +
                        btnRetirar +
                    '</div>' +
                '</div>' +
            '</div>';
        });

        contenedor.innerHTML = html;

        contenedor.querySelectorAll('.btn-retirar-foto').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var fotoId = this.getAttribute('data-id');
                if (confirm('¿Estás seguro de que deseas retirar esta fotografía del reto?')) {
                    retirarFotografia(fotoId);
                }
            });
        });
    }

    function retirarFotografia(fotoId) {
        var formData = new FormData();
        formData.append('accion', 'retirarFoto');
        formData.append('fotoId', fotoId);

        peticion(API_URL, { method: 'POST', body: formData })
            .then(function (res) {
                if (res && res.exito) {
                    if (retoActualId) cargarDatosReto(retoActualId);
                } else {
                    alert((res && res.mensaje) || 'No se pudo retirar la fotografía.');
                }
            })
            .catch(function () {
                alert('Error al comunicarse con el servidor.');
            });
    }

    function renderizarGaleriaRanking(fotos, reto) {
        var contenedor = document.getElementById('contenedorGaleriaReto');
        if (!contenedor) return;

        if (!fotos || fotos.length === 0) {
            contenedor.innerHTML = '<div class="col-12"><div class="p-4 text-center border rounded bg-white"><p class="text-secondary mb-0">Aún no se han inscrito fotografías en este reto.</p></div></div>';
            return;
        }

        var html = '';
        fotos.forEach(function (foto) {
            var badgePos = '';
            if (foto.posicion === 1) {
                badgePos = '<span class="badge badge-podio-1 px-3 py-1 shadow-sm">🥇 1° Lugar</span>';
            } else if (foto.posicion === 2) {
                badgePos = '<span class="badge badge-podio-2 px-3 py-1 shadow-sm">🥈 2° Lugar</span>';
            } else if (foto.posicion === 3) {
                badgePos = '<span class="badge badge-podio-3 px-3 py-1 shadow-sm">🥉 3° Lugar</span>';
            } else {
                badgePos = '<span class="badge bg-light text-dark border px-2 py-1">Posición #' + foto.posicion + '</span>';
            }

            var widgetVotacion = '';
            if (reto.estado === 'votacion') {
                if (foto.esPropia) {
                    widgetVotacion = '<span class="badge bg-light text-secondary border">Foto propia (no puedes votar)</span>';
                } else if (foto.miVoto) {
                    widgetVotacion = '<div class="small fw-semibold text-warning mb-1">Tu voto: ' + foto.miVoto + ' ★</div>' +
                        generarEstrellasHtml(foto.miVoto, true);
                } else {
                    widgetVotacion = '<div class="small text-muted mb-1">Haz clic para votar:</div>' +
                        '<div class="estrellas-widget" data-foto-id="' + foto.tbretofotoid + '">' +
                            '<span class="estrella" data-val="1">★</span>' +
                            '<span class="estrella" data-val="2">★</span>' +
                            '<span class="estrella" data-val="3">★</span>' +
                            '<span class="estrella" data-val="4">★</span>' +
                            '<span class="estrella" data-val="5">★</span>' +
                        '</div>';
                }
            } else if (reto.estado === 'finalizado') {
                widgetVotacion = '<div class="small text-muted">Votación cerrada</div>';
            } else {
                widgetVotacion = '<div class="small text-muted">Votación inicia el ' + formatearFecha(reto.tbretofechainiciovotacion) + '</div>';
            }

            html += '<div class="col-12 col-md-6 col-lg-4">' +
                '<div class="foto-card h-100 d-flex flex-column">' +
                    '<div class="foto-img-container">' +
                        '<img src="' + escapeHtml(foto.tbretofotoarchivo) + '" alt="' + escapeHtml(foto.tbretofototitulo) + '">' +
                        '<div class="position-absolute top-0 start-0 p-2">' + badgePos + '</div>' +
                    '</div>' +
                    '<div class="p-3 d-flex flex-column flex-grow-1">' +
                        '<div class="d-flex justify-content-between align-items-center mb-1">' +
                            '<h4 class="h6 mb-0 fw-bold text-truncate">' + escapeHtml(foto.tbretofototitulo) + '</h4>' +
                            '<span class="badge bg-warning text-dark fw-bold">★ ' + (foto.promediovotos > 0 ? foto.promediovotos : '0.00') + '</span>' +
                        '</div>' +
                        '<div class="small text-muted mb-2">Por: ' + escapeHtml(foto.autornombre) + '</div>' +
                        (foto.tbretofotodescripcion ? '<p class="small text-secondary mb-2">' + escapeHtml(foto.tbretofotodescripcion) + '</p>' : '') +
                        '<div class="d-flex flex-wrap gap-1 mb-3">' +
                            '<span class="badge bg-light text-dark border" style="font-size:0.7rem;">' + escapeHtml(foto.tbretofotocomposicion || 'Horizontal') + '</span>' +
                            '<span class="badge bg-light text-dark border" style="font-size:0.7rem;">' + escapeHtml(foto.tbretofotoestilo || 'Natural') + '</span>' +
                            '<span class="badge bg-light text-dark border" style="font-size:0.7rem;">' + escapeHtml(foto.tbretofotocolordominante || 'Equilibrado') + '</span>' +
                            '<span class="badge bg-light text-secondary border" style="font-size:0.7rem;">' + foto.totalvotos + ' votos (' + foto.votoscincoestrellas + ' de 5★)</span>' +
                        '</div>' +
                        '<div class="mt-auto border-top pt-2 text-center">' +
                            widgetVotacion +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });

        contenedor.innerHTML = html;

        contenedor.querySelectorAll('.estrellas-widget:not(.bloqueada) .estrella').forEach(function (star) {
            star.addEventListener('click', function () {
                var parent = this.closest('.estrellas-widget');
                var fotoId = parent.getAttribute('data-foto-id');
                var estrellas = this.getAttribute('data-val');
                votarFotografia(fotoId, estrellas);
            });
        });
    }

    function generarEstrellasHtml(valor, bloqueada) {
        var num = parseInt(valor, 10) || 0;
        var cl = 'estrellas-widget' + (bloqueada ? ' bloqueada' : '');
        var res = '<div class="' + cl + '">';
        for (var i = 1; i <= 5; i++) {
            res += '<span class="estrella' + (i <= num ? ' activa' : '') + '">★</span>';
        }
        res += '</div>';
        return res;
    }

    function votarFotografia(fotoId, estrellas) {
        var formData = new FormData();
        formData.append('accion', 'votar');
        formData.append('fotoId', fotoId);
        formData.append('estrellas', estrellas);

        peticion(API_URL, { method: 'POST', body: formData })
            .then(function (res) {
                if (res && res.exito) {
                    if (retoActualId) cargarDatosReto(retoActualId);
                } else {
                    alert((res && res.mensaje) || 'No se pudo emitir el voto.');
                }
            })
            .catch(function () {
                alert('Error al comunicarse con el servidor.');
            });
    }

    function inicializarFormularioInscripcion() {
        var inputArchivo = document.getElementById('inputFotoArchivo');
        var previewBox = document.getElementById('previewFotoBox');
        var previewPlaceholder = document.getElementById('previewFotoPlaceholder');
        var previewImg = document.getElementById('previewFotoImg');
        var form = document.getElementById('formInscribirFoto');
        var alerta = document.getElementById('alertaInscripcion');

        if (inputArchivo && previewImg) {
            inputArchivo.addEventListener('change', function () {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('d-none');
                        previewPlaceholder.classList.add('d-none');
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.src = '';
                    previewImg.classList.add('d-none');
                    previewPlaceholder.classList.remove('d-none');
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                alerta.innerHTML = '<div class="alert alert-info py-2 small">Procesando fotografía y analizando contenido...</div>';

                var formData = new FormData(form);
                formData.append('accion', 'inscribirFoto');

                var btnEnviar = document.getElementById('btnEnviarFoto');
                btnEnviar.disabled = true;

                peticion(API_URL, { method: 'POST', body: formData })
                    .then(function (res) {
                        btnEnviar.disabled = false;
                        if (res && res.exito) {
                            alerta.innerHTML = '<div class="alert alert-success py-2 small">¡Fotografía inscrita con éxito!</div>';
                            form.reset();
                            previewImg.src = '';
                            previewImg.classList.add('d-none');
                            previewPlaceholder.classList.remove('d-none');
                            setTimeout(function () {
                                alerta.innerHTML = '';
                                if (retoActualId) cargarDatosReto(retoActualId);
                            }, 1200);
                        } else {
                            alerta.innerHTML = '<div class="alert alert-danger py-2 small">' + escapeHtml((res && res.mensaje) || 'Error al inscribir fotografía.') + '</div>';
                        }
                    })
                    .catch(function () {
                        btnEnviar.disabled = false;
                        alerta.innerHTML = '<div class="alert alert-danger py-2 small">Error de conexión al subir la fotografía.</div>';
                    });
            });
        }
    }

    function inicializarFormularioProponer() {
        var form = document.getElementById('formProponerReto');
        var btnAbrir = document.getElementById('btnIrProponerReto');
        var alerta = document.getElementById('alertaProponerReto');

        if (btnAbrir) {
            btnAbrir.addEventListener('click', function () {
                var modalEl = document.getElementById('modalProponerReto');
                if (modalEl) {
                    if (!modalProponer) {
                        modalProponer = new bootstrap.Modal(modalEl);
                    }
                    prellenarFechasDefecto();
                    modalProponer.show();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                alerta.innerHTML = '<div class="alert alert-info py-2 small">Creando reto e invitando a participantes...</div>';

                var formData = new FormData(form);
                formData.append('accion', 'crearReto');

                var btn = document.getElementById('btnGuardarProponerReto');
                btn.disabled = true;

                peticion(API_URL, { method: 'POST', body: formData })
                    .then(function (res) {
                        btn.disabled = false;
                        if (res && res.exito) {
                            alerta.innerHTML = '<div class="alert alert-success py-2 small">¡Reto creado con éxito! Se enviaron ' + (res.invitados || 0) + ' invitaciones.</div>';
                            form.reset();
                            setTimeout(function () {
                                alerta.innerHTML = '';
                                if (modalProponer) modalProponer.hide();
                                cargarRetosActivos();
                                if (res.retoId) abrirDetalleReto(res.retoId);
                            }, 1200);
                        } else {
                            alerta.innerHTML = '<div class="alert alert-danger py-2 small">' + escapeHtml((res && res.mensaje) || 'Error al crear el reto.') + '</div>';
                        }
                    })
                    .catch(function () {
                        btn.disabled = false;
                        alerta.innerHTML = '<div class="alert alert-danger py-2 small">Error de conexión con el servidor.</div>';
                    });
            });
        }
    }

    function prellenarFechasDefecto() {
        var ahora = new Date();
        var partFin = new Date(ahora.getTime() + (3 * 24 * 60 * 60 * 1000));
        var votFin = new Date(partFin.getTime() + (3 * 24 * 60 * 60 * 1000));

        function aFormatoLocal(d) {
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        var inIniP = document.getElementById('proponerInicioPart');
        var inFinP = document.getElementById('proponerFinPart');
        var inIniV = document.getElementById('proponerInicioVot');
        var inFinV = document.getElementById('proponerFinVot');

        if (inIniP && !inIniP.value) inIniP.value = aFormatoLocal(ahora);
        if (inFinP && !inFinP.value) inFinP.value = aFormatoLocal(partFin);
        if (inIniV && !inIniV.value) inIniV.value = aFormatoLocal(partFin);
        if (inFinV && !inFinV.value) inFinV.value = aFormatoLocal(votFin);
    }

    function cargarInvitaciones() {
        var contenedor = document.getElementById('contenedorInvitacionesFotografia');
        var badge = document.getElementById('badgeInvitacionesFotografia');
        if (!contenedor) return;

        peticion(API_URL + '?accion=getInvitaciones')
            .then(function (res) {
                if (!res || !res.exito || !res.data) {
                    contenedor.innerHTML = '<p class="text-danger small">No se pudieron cargar las invitaciones.</p>';
                    return;
                }

                var invitaciones = res.data.invitaciones || [];
                var noLeidas = res.data.noLeidas || 0;

                if (badge) {
                    if (noLeidas > 0) {
                        badge.textContent = noLeidas;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }
                }

                if (invitaciones.length === 0) {
                    contenedor.innerHTML = '<div class="col-12"><div class="p-4 text-center border rounded bg-white"><p class="text-secondary mb-0">No tienes invitaciones a retos pendientes.</p></div></div>';
                    return;
                }

                var html = '';
                invitaciones.forEach(function (inv) {
                    html += '<div class="col-12 col-md-6">' +
                        '<div class="foto-card p-4 h-100 d-flex flex-column border-start border-4 border-warning">' +
                            '<div class="d-flex justify-content-between align-items-start mb-2">' +
                                '<h3 class="h5 mb-0 fw-bold text-primary">' + escapeHtml(inv.tbretotematica) + '</h3>' +
                                badgeEstadoReto(inv.estadoReto) +
                            '</div>' +
                            '<p class="text-secondary small mb-2">Propuesto por ' + escapeHtml(inv.creadornombre) + ' el ' + formatearFecha(inv.tbretoinvitacionfecha) + '</p>' +
                            '<p class="small text-muted mb-3 flex-grow-1">' + escapeHtml(inv.tbretodescripcion) + '</p>' +
                            '<div class="mt-auto d-flex gap-2">' +
                                '<button type="button" class="btn btn-primary btn-sm btn-abrir-invitacion" data-reto="' + inv.tbretoid + '" data-inv="' + inv.tbretoinvitacionid + '">Ver Reto y Participar</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                });

                contenedor.innerHTML = html;

                contenedor.querySelectorAll('.btn-abrir-invitacion').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var rId = this.getAttribute('data-reto');
                        var invId = this.getAttribute('data-inv');

                        var fd = new FormData();
                        fd.append('accion', 'marcarInvitacion');
                        fd.append('invitacionId', invId);
                        peticion(API_URL, { method: 'POST', body: fd });

                        var tabRetos = document.getElementById('tab-retos-activos');
                        if (tabRetos) bootstrap.Tab.getOrCreateInstance(tabRetos).show();
                        abrirDetalleReto(rId);
                    });
                });
            })
            .catch(function () {
                contenedor.innerHTML = '<p class="text-danger small">Error de conexión al cargar invitaciones.</p>';
            });
    }

    function cargarRetosAnteriores() {
        var contenedor = document.getElementById('contenedorRetosAnteriores');
        if (!contenedor) return;

        peticion(API_URL + '?accion=getRetosAnteriores')
            .then(function (res) {
                if (!res || !res.exito || !res.data) {
                    contenedor.innerHTML = '<p class="text-danger small">No se pudieron cargar los retos anteriores.</p>';
                    return;
                }

                var retos = res.data;
                if (retos.length === 0) {
                    contenedor.innerHTML = '<div class="p-4 text-center border rounded bg-white"><p class="text-secondary mb-0">Aún no hay retos fotográficos finalizados.</p></div>';
                    return;
                }

                var html = '';
                retos.forEach(function (r) {
                    var podioHtml = '';
                    if (r.podio && r.podio.length > 0) {
                        podioHtml = '<div class="row g-3 mt-1">';
                        r.podio.forEach(function (pf) {
                            var med = pf.posicion === 1 ? '🥇 Ganador' : (pf.posicion === 2 ? '🥈 2° Lugar' : '🥉 3° Lugar');
                            var bClass = pf.posicion === 1 ? 'badge-podio-1' : (pf.posicion === 2 ? 'badge-podio-2' : 'badge-podio-3');

                            podioHtml += '<div class="col-12 col-md-4">' +
                                '<div class="p-2 border rounded bg-light text-center">' +
                                    '<div class="foto-img-container rounded mb-2" style="height: 140px;">' +
                                        '<img src="' + escapeHtml(pf.tbretofotoarchivo) + '" alt="' + escapeHtml(pf.tbretofototitulo) + '">' +
                                    '</div>' +
                                    '<span class="badge ' + bClass + ' mb-1">' + med + '</span>' +
                                    '<div class="fw-semibold text-truncate small">' + escapeHtml(pf.tbretofototitulo) + '</div>' +
                                    '<small class="text-secondary">Por ' + escapeHtml(pf.autornombre) + ' (★ ' + pf.promediovotos + ')</small>' +
                                '</div>' +
                            '</div>';
                        });
                        podioHtml += '</div>';
                    } else {
                        podioHtml = '<p class="text-muted small">No hubo fotografías participantes en este reto.</p>';
                    }

                    html += '<div class="card p-4 border-0 shadow-sm" style="border-radius: var(--radio-tarjeta); background:#fff;">' +
                        '<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">' +
                            '<div>' +
                                '<div class="d-flex align-items-center gap-2 mb-1">' +
                                    '<span class="badge bg-secondary">Finalizado</span>' +
                                    '<small class="text-muted">Cerró el ' + formatearFecha(r.tbretofechafinvotacion) + '</small>' +
                                '</div>' +
                                '<h3 class="h5 mb-1 text-primary fw-bold">' + escapeHtml(r.tbretotematica) + '</h3>' +
                            '</div>' +
                            '<button type="button" class="btn btn-outline-primary btn-sm btn-ver-reto" data-id="' + r.tbretoid + '">Ver Clasificación Completa &rarr;</button>' +
                        '</div>' +
                        '<p class="text-secondary small mb-3">' + escapeHtml(r.tbretodescripcion) + '</p>' +
                        '<h4 class="h6 fw-bold mb-2">Podio y Ganadores:</h4>' +
                        podioHtml +
                    '</div>';
                });

                contenedor.innerHTML = html;

                contenedor.querySelectorAll('.btn-ver-reto').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var rId = this.getAttribute('data-id');
                        var tabRetos = document.getElementById('tab-retos-activos');
                        if (tabRetos) bootstrap.Tab.getOrCreateInstance(tabRetos).show();
                        abrirDetalleReto(rId);
                    });
                });
            })
            .catch(function () {
                contenedor.innerHTML = '<p class="text-danger small">Error de conexión al cargar el historial.</p>';
            });
    }

    function cargarMiPerfilFoto() {
        var contenedor = document.getElementById('contenedorDetallePerfilFoto');
        if (!contenedor) return;

        peticion(API_URL + '?accion=getPerfilFotografico')
            .then(function (res) {
                if (!res || !res.exito || !res.data) {
                    contenedor.innerHTML = '<p class="text-danger small">No se pudo cargar el perfil fotográfico.</p>';
                    return;
                }

                var pf = res.data;
                if (pf.totalFotos === 0) {
                    contenedor.innerHTML = '<div class="p-4 text-center border rounded bg-white"><p class="text-secondary mb-2">Aún no has inscrito fotografías en ningún reto.</p><button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById(\'tab-retos-activos\').click();">Ver retos disponibles</button></div>';
                    return;
                }

                var html = '<div class="row g-3 mb-4 text-center">' +
                    '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-white shadow-sm"><div class="h3 mb-0 text-primary fw-bold">' + pf.totalRetos + '</div><small class="text-muted">Retos Participados</small></div></div>' +
                    '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-white shadow-sm"><div class="h3 mb-0 text-success fw-bold">' + pf.totalFotos + '</div><small class="text-muted">Fotos Inscritas</small></div></div>' +
                    '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-white shadow-sm"><div class="h3 mb-0 text-warning fw-bold">' + pf.totalPodios + '</div><small class="text-muted">Podios Obtenidos (' + pf.totalVictorias + ' Victorias)</small></div></div>' +
                    '<div class="col-6 col-md-3"><div class="p-3 border rounded bg-white shadow-sm"><div class="h3 mb-0 text-info fw-bold">★ ' + pf.promedioGeneral + '</div><small class="text-muted">Calificación Promedio</small></div></div>' +
                '</div>';

                if (pf.perfilEstructural) {
                    var pe = pf.perfilEstructural;
                    html += '<div class="card p-4 mb-4 border-0 shadow-sm" style="border-radius: var(--radio-tarjeta); background:#fff;">' +
                        '<h3 class="h5 mb-2 fw-bold text-primary">Análisis Estructural de tu Fotografía</h3>' +
                        '<p class="text-secondary small mb-3">Determinado por el algoritmo a partir de la luminosidad, contraste, paleta cromática y relación de aspecto de tus imágenes.</p>' +
                        '<div class="row g-3">' +
                            '<div class="col-12 col-md-4"><div class="p-3 rounded bg-light"><strong>Estilo Característico:</strong><div class="h6 mb-0 mt-1 text-primary text-uppercase">' + escapeHtml(pe.estiloPrincipal) + '</div></div></div>' +
                            '<div class="col-12 col-md-4"><div class="p-3 rounded bg-light"><strong>Tonalidad Dominante:</strong><div class="h6 mb-0 mt-1 text-secondary text-uppercase">' + escapeHtml(pe.colorPrincipal) + '</div></div></div>' +
                            '<div class="col-12 col-md-4"><div class="p-3 rounded bg-light"><strong>Composición Preferida:</strong><div class="h6 mb-0 mt-1 text-info text-dark text-uppercase">' + escapeHtml(pe.composicionPrincipal) + '</div></div></div>' +
                        '</div>' +
                    '</div>';
                }

                html += '<h3 class="h5 mb-3 fw-bold">Tus Fotografías en Retos:</h3><div class="row g-4">';
                pf.fotos.forEach(function (f) {
                    var badgePos = '';
                    if (f.posicionEnReto === 1) {
                        badgePos = '<span class="badge badge-podio-1">🥇 Ganador</span>';
                    } else if (f.posicionEnReto === 2) {
                        badgePos = '<span class="badge badge-podio-2">🥈 2° Lugar</span>';
                    } else if (f.posicionEnReto === 3) {
                        badgePos = '<span class="badge badge-podio-3">🥉 3° Lugar</span>';
                    } else if (f.posicionEnReto) {
                        badgePos = '<span class="badge bg-light text-dark border">Posición #' + f.posicionEnReto + '</span>';
                    }

                    html += '<div class="col-12 col-md-4 col-lg-3">' +
                        '<div class="foto-card h-100 d-flex flex-column">' +
                            '<div class="foto-img-container" style="height:170px;">' +
                                '<img src="' + escapeHtml(f.tbretofotoarchivo) + '" alt="' + escapeHtml(f.tbretofototitulo) + '">' +
                                '<div class="position-absolute top-0 start-0 p-2">' + badgePos + '</div>' +
                            '</div>' +
                            '<div class="p-3 d-flex flex-column flex-grow-1">' +
                                '<h4 class="h6 mb-1 fw-bold text-truncate">' + escapeHtml(f.tbretofototitulo) + '</h4>' +
                                '<small class="text-muted mb-2">Reto: ' + escapeHtml(f.tbretotematica) + '</small>' +
                                '<div class="mt-auto d-flex justify-content-between align-items-center">' +
                                    '<span class="badge bg-warning text-dark">★ ' + f.promediovotos + '</span>' +
                                    '<small class="text-muted">' + f.totalvotos + ' votos</small>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                });
                html += '</div>';

                contenedor.innerHTML = html;
            })
            .catch(function () {
                contenedor.innerHTML = '<p class="text-danger small">Error de conexión al cargar tu perfil fotográfico.</p>';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cargarRetosActivos();
        cargarInvitaciones();
        inicializarFormularioInscripcion();
        inicializarFormularioProponer();

        var btnVolver = document.getElementById('btnVolverListaRetos');
        if (btnVolver) {
            btnVolver.addEventListener('click', cerrarDetalleReto);
        }

        var btnRefrescarRetos = document.getElementById('btnRefrescarRetos');
        if (btnRefrescarRetos) {
            btnRefrescarRetos.addEventListener('click', cargarRetosActivos);
        }

        var btnActualizarRanking = document.getElementById('btnActualizarRankingReto');
        if (btnActualizarRanking) {
            btnActualizarRanking.addEventListener('click', function () {
                if (retoActualId) cargarDatosReto(retoActualId);
            });
        }

        var btnRefrescarInv = document.getElementById('btnRefrescarInvitaciones');
        if (btnRefrescarInv) {
            btnRefrescarInv.addEventListener('click', cargarInvitaciones);
        }

        var btnRefrescarAnt = document.getElementById('btnRefrescarAnteriores');
        if (btnRefrescarAnt) {
            btnRefrescarAnt.addEventListener('click', cargarRetosAnteriores);
        }

        var btnRefrescarPerfil = document.getElementById('btnRefrescarPerfilFoto');
        if (btnRefrescarPerfil) {
            btnRefrescarPerfil.addEventListener('click', cargarMiPerfilFoto);
        }

        var tabAnteriores = document.getElementById('tab-retos-anteriores');
        if (tabAnteriores) {
            tabAnteriores.addEventListener('shown.bs.tab', cargarRetosAnteriores);
        }

        var tabPerfil = document.getElementById('tab-mi-perfil-foto');
        if (tabPerfil) {
            tabPerfil.addEventListener('shown.bs.tab', cargarMiPerfilFoto);
        }

        var tabInv = document.getElementById('tab-invitaciones');
        if (tabInv) {
            tabInv.addEventListener('shown.bs.tab', cargarInvitaciones);
        }

        if (retoActualId) {
            abrirDetalleReto(retoActualId);
        }
    });

})();

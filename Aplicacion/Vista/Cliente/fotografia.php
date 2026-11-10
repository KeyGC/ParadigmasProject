<?php
if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$perfilIdActual = (int) $_SESSION['perfil']['tbperfilid'];
$perfilNombreActual = $_SESSION['perfil']['tbperfilnombre'] ?? '';
$retoSeleccionadoId = (int) ($_GET['reto'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Club de Fotografía</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .foto-card {
            border-radius: var(--radio-tarjeta);
            overflow: hidden;
            border: 1px solid var(--color-borde);
            background: var(--color-superficie);
            box-shadow: var(--sombra-tarjeta);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .foto-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--sombra-flotante);
        }

        .foto-img-container {
            width: 100%;
            height: 240px;
            background-color: #222C3E;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .foto-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .foto-card:hover .foto-img-container img {
            transform: scale(1.03);
        }

        .estrellas-widget {
            display: inline-flex;
            gap: 4px;
            font-size: 1.35rem;
            color: #d1c9d2;
            cursor: pointer;
            user-select: none;
        }

        .estrellas-widget .estrella {
            transition: color 0.15s ease, transform 0.1s ease;
        }

        .estrellas-widget .estrella:hover,
        .estrellas-widget .estrella.activa {
            color: #E5A93C;
        }

        .estrellas-widget .estrella:hover {
            transform: scale(1.15);
        }

        .estrellas-widget.bloqueada {
            cursor: default;
        }

        .estrellas-widget.bloqueada .estrella:hover {
            transform: none;
        }

        .nav-pills-fotografia .nav-link {
            border-radius: var(--radio-control);
            color: var(--color-texto);
            font-weight: 500;
            padding: 0.6rem 1.1rem;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-pills-fotografia .nav-link.active {
            background-color: var(--color-primario);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(124, 41, 72, 0.25);
        }

        .badge-podio-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #2c2430;
            font-weight: 700;
        }

        .badge-podio-2 {
            background: linear-gradient(135deg, #E0E0E0, #B0BEC5);
            color: #2c2430;
            font-weight: 700;
        }

        .badge-podio-3 {
            background: linear-gradient(135deg, #CD7F32, #B87333);
            color: #ffffff;
            font-weight: 700;
        }

        .preview-upload-box {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--color-borde);
            border-radius: var(--radio-control);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #faf8fa;
            overflow: hidden;
            position: relative;
        }

        .preview-upload-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-lg px-lg-4 py-4">

            <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="titulo-pagina">Club de Fotografía</h1>
                    <p class="subtitulo-pagina">Propón retos temáticos, sube tus mejores capturas y vota por el talento de la comunidad.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" id="btnIrProponerReto">
                        <span>+</span> Proponer Nuevo Reto
                    </button>
                </div>
            </header>

            <ul class="nav nav-pills nav-pills-fotografia gap-2 mb-4" id="pillsFotografia" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-retos-activos" data-bs-toggle="pill" data-bs-target="#panel-retos-activos" type="button" role="tab">
                        Retos Activos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="tab-invitaciones" data-bs-toggle="pill" data-bs-target="#panel-invitaciones" type="button" role="tab">
                        Invitaciones
                        <span class="badge rounded-pill bg-danger ms-1 d-none" id="badgeInvitacionesFotografia">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-retos-anteriores" data-bs-toggle="pill" data-bs-target="#panel-retos-anteriores" type="button" role="tab">
                        Retos Anteriores y Ganadores
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="pillsFotografiaContent">

                <div class="tab-pane fade show active" id="panel-retos-activos" role="tabpanel">
                    <div id="vistaListaRetos">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Retos en Curso</h2>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefrescarRetos">Actualizar lista</button>
                        </div>
                        <div class="row g-4" id="contenedorRetosActivos">
                            <p class="text-secondary">Cargando retos fotográficos...</p>
                        </div>
                    </div>

                    <div id="vistaDetalleReto" class="d-none">
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnVolverListaRetos">
                                &larr; Volver a la lista de retos
                            </button>
                        </div>

                        <div class="card p-4 mb-4 border-0 shadow-sm" style="border-radius: var(--radio-tarjeta); background: #ffffff;">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge" id="detalleRetoBadgeEstado">Estado</span>
                                        <small class="text-secondary" id="detalleRetoCreador">Propuesto por ...</small>
                                    </div>
                                    <h2 class="h3 mb-2" id="detalleRetoTematica">Temática del Reto</h2>
                                    <p class="text-secondary mb-3" id="detalleRetoDescripcion">Descripción...</p>
                                </div>
                                <div class="text-md-end p-3 rounded" style="background: var(--color-fondo); min-width: 250px;">
                                    <div class="small mb-1"><strong>Participación:</strong></div>
                                    <div class="small text-muted mb-2" id="detalleRetoFechasParticipacion">...</div>
                                    <div class="small mb-1"><strong>Votación:</strong></div>
                                    <div class="small text-muted" id="detalleRetoFechasVotacion">...</div>
                                </div>
                            </div>
                        </div>

                        <div class="card p-4 mb-4 border-0 shadow-sm d-none" id="seccionInscripcionFoto" style="border-radius: var(--radio-tarjeta); background: #ffffff;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Subir Fotografía Participante</h3>
                                <span class="badge bg-light text-dark border" id="detalleRetoContadorMisFotos">0 / 3 fotos</span>
                            </div>
                            <p class="text-secondary small mb-3">
                                Presenta hasta 3 fotografías originales relacionadas con la temática. Cada fotografía será analizada automáticamente para comprobar que no contenga material prohibido y se extraerá su perfil estructural.
                            </p>

                            <form id="formInscribirFoto" class="row g-3" enctype="multipart/form-data">
                                <input type="hidden" id="inscribirRetoId" name="retoId" value="">
                                
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">Seleccionar archivo de imagen <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="inputFotoArchivo" name="foto" accept=".jpg,.jpeg,.png,.webp,.avif,.gif,image/jpeg,image/png,image/webp,image/avif,image/gif" required>
                                    <small class="text-muted">Formatos admitidos: JPG, PNG, WebP o AVIF desde tu dispositivo.</small>
                                    
                                    <div class="preview-upload-box mt-3" id="previewFotoBox">
                                        <span class="text-muted small" id="previewFotoPlaceholder">Vista previa de la imagen seleccionada</span>
                                        <img src="" id="previewFotoImg" class="d-none" alt="Vista previa">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Título de la fotografía <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="inputFotoTitulo" name="titulo" maxlength="200" placeholder="Ej. Atardecer en la montaña" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Descripción o técnica (opcional)</label>
                                            <textarea class="form-control" id="inputFotoDescripcion" name="descripcion" rows="3" placeholder="Comentarios sobre la captura, iluminación o encuadre..."></textarea>
                                        </div>
                                    </div>

                                    <div id="alertaInscripcion" class="mb-2"></div>

                                    <div class="text-end mt-2">
                                        <button type="submit" class="btn btn-primary" id="btnEnviarFoto">
                                            Inscribir Fotografía
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="mb-4 d-none" id="seccionMisFotosReto">
                            <h3 class="h5 mb-3">Mis Fotografías en este Reto</h3>
                            <div class="row g-3" id="contenedorMisFotosReto"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h3 class="h5 mb-1">Galería de Participantes y Ranking</h3>
                                <p class="text-secondary small mb-0">
                                    Valoración de 1 a 5 estrellas. Resultados ordenados por regla de desempate predefinida.
                                </p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnActualizarRankingReto">
                                Actualizar Votos
                            </button>
                        </div>

                        <div class="row g-4" id="contenedorGaleriaReto">
                            <p class="text-secondary">Cargando fotografías participantes...</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="panel-invitaciones" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Retos Sugeridos de tu Interés</h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefrescarInvitaciones">Actualizar</button>
                    </div>
                    <p class="text-secondary small mb-4">
                        Cuando un usuario crea un reto con una temática afín a tus gustos o temas de interés, se genera una invitación automática para que participes.
                    </p>
                    <div class="row g-3" id="contenedorInvitacionesFotografia">
                        <p class="text-secondary">Cargando invitaciones...</p>
                    </div>
                </div>

                <div class="tab-pane fade" id="panel-retos-anteriores" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Historial de Retos Finalizados y Ganadores</h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefrescarAnteriores">Actualizar</button>
                    </div>
                    <p class="text-secondary small mb-4">
                        Consulta la clasificación final y las fotografías ganadoras de los retos cerrados.
                    </p>
                    <div class="d-flex flex-column gap-4" id="contenedorRetosAnteriores">
                        <p class="text-secondary">Cargando retos anteriores...</p>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <div class="modal fade" id="modalProponerReto" tabindex="-1" aria-labelledby="modalProponerRetoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: var(--radio-tarjeta);">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="modalProponerRetoLabel">Proponer Nuevo Reto Fotográfico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formProponerReto">
                    <div class="modal-body p-4">
                        <div id="alertaProponerReto" class="mb-3"></div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Temática del Reto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="proponerTematica" name="tematica" maxlength="150" placeholder="Ej. Arquitectura Colonial, Microfotografía de Flores, Sombras y Luces" required>
                            <small class="text-muted">Se invitará automáticamente a personas de la comunidad afines a este tema.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción del Reto e Instrucciones <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="proponerDescripcion" name="descripcion" rows="3" placeholder="Indica los criterios artísticos, elementos a resaltar o técnicas deseadas..." required></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Inicio de Participación <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="proponerInicioPart" name="inicioPart" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Cierre de Participación <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="proponerFinPart" name="finPart" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Inicio de Votación <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="proponerInicioVot" name="inicioVot" required>
                                <small class="text-muted">Generalmente coincide con el cierre de participación.</small>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Cierre Final de Votación <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="proponerFinVot" name="finVot" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarProponerReto">Publicar Reto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script>
        window.RETO_INICIAL_ID = <?= $retoSeleccionadoId ?>;
    </script>
    <script src="js/fotografia.js?v=<?= time() ?>"></script>
</body>

</html>

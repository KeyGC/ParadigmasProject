<?php
require_once __DIR__ . '/../../Utilidades/autenticacion.php';
exigirRol(['admin']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Conciertos</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoAdmin">

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid fondo-panel" id="contenedorPrincipal">
        <div class="container contenedor-panel">

            <div class="encabezado-panel d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="titulo-pagina">Gestión de Conciertos</h1>
                    <p class="subtitulo-pagina">Administra los conciertos disponibles en la plataforma.</p>
                </div>
                <div id="contenedorBotonNuevo">
                    <button class="btn btn-accion" id="btnNuevo">
                        + Nuevo
                    </button>
                </div>
            </div>

            <div class="tarjeta-panel" id="contenedorFormulario" style="display: none;">
                <h2 class="titulo-tarjeta">Nuevo registro</h2>

                <form id="formConcierto">
                    <input type="hidden" id="conciertoId" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del concierto</label>
                            <input type="text" id="nombre" class="form-control" placeholder="Nombre del concierto" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Artista</label>
                            <input type="text" id="artista" class="form-control" placeholder="Artista" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Género</label>
                            <select id="genero" class="form-select" required>
                                <option value="">Seleccione un género</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ubicación (nombre del lugar)</label>
                            <input type="text" id="ubicacion" class="form-control" placeholder="Ej: Estadio Nacional, San José" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Latitud</label>
                            <input type="number" step="any" id="latitud" class="form-control" placeholder="9.93000000" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Longitud</label>
                            <input type="number" step="any" id="longitud" class="form-control" placeholder="-84.08000000" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha</label>
                            <input type="date" id="fecha" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hora</label>
                            <input type="time" id="hora" class="form-control" required>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                            <button type="button" class="btn btn-secondary" id="btnCancelar" style="display:none;">Cancelar edición</button>
                        </div>
                    </div>
                </form>

            </div>

            <div class="tarjeta-panel">

                <div class="busqueda">
                    <span>🔍</span>
                    <input type="text" id="buscador" placeholder="Buscar por nombre, artista o ubicación..." class="form-control">
                </div>

                <div class="table-responsive">
                    <table class="table tabla-panel cabecera-oscura" id="tablaConciertos">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Artista</th>
                                <th>Género</th>
                                <th>Ubicación</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla">
                            <!-- AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <?php require_once APP_PATH . '/Vista/Componentes/footeradmin.php'; ?>

    <script src="js/conciertos.js"></script>
</body>

</html>
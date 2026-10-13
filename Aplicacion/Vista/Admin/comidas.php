<?php
require_once __DIR__ . '/../../Utilidades/autenticacion.php';
exigirRol(['admin']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ítems de Comida</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoAdmin">

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid fondo-panel" id="contenedorPrincipal">
        <div class="container contenedor-panel">

            <div class="encabezado-panel d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="titulo-pagina">Gestión de Ítems de Comida</h1>
                    <p class="subtitulo-pagina">Administra los ítems de comida para el módulo de descubrimiento.</p>
                </div>
                <div id="contenedorBotonNuevo">
                    <button class="btn btn-accion" id="btnNuevo">
                        + Nuevo
                    </button>
                </div>
            </div>

            <div class="tarjeta-panel" id="contenedorFormulario" style="display: none;">
                <h2 class="titulo-tarjeta">Nuevo registro</h2>

                <form id="formItem">
                    <input type="hidden" id="itemId" value="">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <select id="tipoId" class="form-select" required>
                                <option value="">Seleccione un tipo</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="text" id="nombre" class="form-control" placeholder="Nombre del ítem" required>
                        </div>

                        <div class="col-12">
                            <input type="text" id="imagenUrl" class="form-control" placeholder="URL de imagen (opcional)">
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
                    <input type="text" id="buscador" placeholder="Buscar por nombre o tipo..." class="form-control">
                </div>

                <div class="table-responsive">
                    <table class="table tabla-panel cabecera-oscura" id="tablaItems">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
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

    <script src="js/comidas.js"></script>
</body>

</html>
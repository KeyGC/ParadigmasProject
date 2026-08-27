<?php
if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CRUD de Perfiles</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">

</head>

<body class="cuerpoAdmin">

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid fondo-panel" id="contenedorPrincipal">
        <div class="container contenedor-panel">

            <div class="encabezado-panel d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="titulo-pagina">Gestión de Perfiles</h1>
                    <p class="subtitulo-pagina">Administra los usuarios registrados en la plataforma.</p>
                </div>
                <div id="contenedorBotonNuevo">
                    <button class="btn btn-accion" id="btnNuevo">
                        + Nuevo
                    </button>
                </div>
            </div>

            <div class="tarjeta-panel" id="contenedorFormulario" style="display: none;">
                <h2 class="titulo-tarjeta">Nuevo registro</h2>

                <form id="formPerfil">
                    <input type="hidden" id="perfilId" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" id="nombre" class="form-control" placeholder="Sobrenombre" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" id="contra" class="form-control" placeholder="Contraseña" required>
                        </div>

                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon1">@</span>
                                <input type="email" id="correo" class="form-control" placeholder="Correo Electrónico" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="ubicacionEstado" class="form-select">
                                <option value="1">Ubicación: Activa</option>
                                <option value="0">Ubicación: Inactiva</option>
                            </select>
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
                    <input type="text" id="buscador" placeholder="Buscar por nombre..." class="form-control">
                </div>

                <div class="table-responsive">
                    <table class="table tabla-panel cabecera-oscura" id="tablaPerfiles">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Contraseña</th>
                                <th>Correo</th>
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

    <script src="js/perfil.js"></script>
</body>

</html>
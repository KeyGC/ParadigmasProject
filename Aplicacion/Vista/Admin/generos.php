<?php
require_once __DIR__ . '/../../Utilidades/autenticacion.php';
exigirRol(['admin']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Géneros</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid bg-light text-primary p-5 text-center" id="contenedorPrincipal">
        <div class="container">

            <div id="contenedorBotonNuevo">
                <button class="btn btn-success mb-3" id="btnNuevo">
                    + Nuevo
                </button>
            </div>

            <div class="container mb-5" id="contenedorFormulario" style="display: none;">
                <h1>Form</h1>

                <form id="formGenero">
                    <input type="hidden" id="generoId" value="">
                    <input type="text" id="nombre" class="form-control mb-2" placeholder="Nombre del género" required>
                    <button type="submit" class="btn btn-primary mb-2" id="btnGuardar">Guardar</button>
                    <button type="button" class="btn btn-secondary" id="btnCancelar" style="display:none;">Cancelar edición</button>
                </form>

            </div>

            <div class="container">

                <input type="text" id="buscador" placeholder="Buscar por nombre..." class="form-control mb-3">

                <table class="table" id="tablaGeneros">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
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

    <?php require_once APP_PATH . '/Vista/Componentes/footeradmin.php'; ?>

    <script src="js/generos.js"></script>
</body>

</html>
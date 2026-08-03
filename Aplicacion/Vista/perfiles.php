<!-- Aplicacion/Vista/perfiles.php -->
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

<body>

    <nav class="navbar navbar-expand-lg bg-dark navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Universidad Nacional</a>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-light text-primary p-5 text-center" id="contenedorPrincipal">
        <div class="container">

            <div id="contenedorBotonNuevo">
                <button class="btn btn-success mb-3" id="btnNuevo">
                    + Nuevo
                </button>
            </div>


            <div class="container mb-5" id="contenedorFormulario" style="display: none;">
                <h1>Form</h1>


                <form id="formPerfil">
                    <input type="hidden" id="perfilId" value="">
                    <input type="text" id="nombre" class="form-control mb-2" placeholder="Sobrenombre" required>
                    <input type="text" id="contra" class="form-control mb-2" placeholder="Contraseña" required>
                    <div class="input-group mb-2">
                        <span class="input-group-text" id="basic-addon1">@</span>
                        <input type="email" id="correo" class="form-control" placeholder="Correo Electrónico" required>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2" id="btnGuardar">Guardar</button>
                    <button type="button" class="btn btn-secondary" id="btnCancelar" style="display:none;">Cancelar edición</button>
                </form>

            </div>


            <div class="container">

                <input type="text" id="buscador" placeholder="Buscar por nombre..." class="form-control mb-3">

                <table class="table" id="tablaPerfiles">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Contraseña</th>
                            <th>Correo</th>
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

    <footer class="container-fluid text-center bg-dark text-light p-3 mt-auto">
        <p>&copy; 2026 Gestión de Perfiles</p>
    </footer>


    <script src="js/perfil.js"></script>
</body>

</html>
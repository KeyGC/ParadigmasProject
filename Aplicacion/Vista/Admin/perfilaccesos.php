<?php
if (!isset($_SESSION['perfil']) || $_SESSION['perfil']['tbperfilrol'] !== 'admin') {
    header('Location: index.php?vista=login');
    exit;
}

$idPerfil = $_GET['id'] ?? null;
if (!$idPerfil) {
    header('Location: index.php?vista=perfiles');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Accesos del Perfil</title>
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
                        <a class="nav-link active" href="index.php?vista=perfiles">Volver a Perfiles</a>
                        <a class="nav-link active" href="index.php?vista=logout">Cerrar sesion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid bg-light text-primary p-5 text-center" id="contenedorPrincipal">
        <div class="container">
            <h1 id="tituloPerfil">Accesos</h1>
            <p id="resumenFechas" class="text-secondary"></p>

            <table class="table table-bordered" id="tablaMatriz">
                <thead class="table-dark">
                    <tr>
                        <th>Semana</th>
                        <th>Lun</th>
                        <th>Mar</th>
                        <th>Mie</th>
                        <th>Jue</th>
                        <th>Vie</th>
                        <th>Sab</th>
                        <th>Dom</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="cuerpoMatriz">
                    <!-- AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <footer class="container-fluid text-center bg-dark text-light p-3 mt-auto">
        <p>&copy; 2026 Gestión de Perfiles</p>
    </footer>

    <input type="hidden" id="idPerfil" value="<?php echo htmlspecialchars($idPerfil); ?>">
    <script src="js/perfilacceso.js"></script>
</body>
</html>
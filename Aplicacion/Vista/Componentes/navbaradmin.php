<?php

$vistaActual = $_GET['vista'] ?? '';
$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? 'Admin';
$esVistaPerfiles = in_array($vistaActual, ['perfiles', 'perfilaccesos', 'perfilreproducciones', 'perfilubicaciones'], true);
?>
<nav class="navbar navbar-expand-lg navbar-admin sticky-top" id="navbarAdmin">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="index.php?vista=perfiles">
            <span class="marca-emblema">M</span>
            <span>Match<span class="marca-suave">Admin</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-lg-4 me-auto gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $esVistaPerfiles ? 'activo' : '' ?>" href="index.php?vista=perfiles">Perfiles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $vistaActual === 'canciones' ? 'activo' : '' ?>" href="index.php?vista=canciones">Canciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $vistaActual === 'generos' ? 'activo' : '' ?>" href="index.php?vista=generos">Géneros</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <span class="usuario-pill"><?= htmlspecialchars($usuarioActivo) ?></span>
                </li>
                <li class="nav-item">
                    <a class="btn-cerrar" href="index.php?vista=logout">Cerrar sesión</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
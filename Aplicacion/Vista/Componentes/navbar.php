<?php

$vistaActual = $_GET['vista'] ?? '';
$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-cliente sticky-top" id="navCliente">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="index.php?vista=cliente">
            <span class="marca-corazon">U</span>
            <span class="marca-texto">UnaMatch</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-lg-4 me-auto gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $vistaActual === 'cliente' ? 'activo' : '' ?>" href="index.php?vista=cliente">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $vistaActual === 'perfil' ? 'activo' : '' ?>" href="index.php?vista=perfil">Perfil</a>
                </li>
            </ul>

            <?php if ($usuarioActivo): ?>
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <span class="usuario-pill"><?= htmlspecialchars($usuarioActivo) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-cerrar-cliente" href="index.php?vista=logout">Cerrar sesión</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
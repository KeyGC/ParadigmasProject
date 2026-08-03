<?php

$vistaActual = $_GET['vista'] ?? '';
$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? null;
?>
<nav class="navbar navbar-expand-lg" id="navCliente" style="background-color: #db2777;">
    <div class="container-fluid">
        <a class="navbar-brand text-light" href="index.php?vista=cliente">Universidad Nacional</a>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link text-light <?= $vistaActual === 'cliente' ? 'active fw-bold border-bottom border-light' : '' ?>" href="index.php?vista=cliente">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-light <?= $vistaActual === 'perfil' ? 'active fw-bold border-bottom border-light' : '' ?>" href="index.php?vista=perfil">Perfil</a>
                </li>
            </ul>

            <?php if ($usuarioActivo): ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            👤 <?= htmlspecialchars($usuarioActivo) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-light" href="index.php?vista=logout">Cerrar sesión</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
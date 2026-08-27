<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? null;

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UnaMatch - Mi perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-fluid" id="contenedorPPerfil">

            <div class="contenedorFormulario" id="contenedorFormularioPerfil">
                <h1>Información Personal</h1>
                <div id="alertaPerfil" class="mt-2"></div>

                <form id="formPerfil" class="mt-3 d-flex flex-column gap-3">
                    <input type="hidden" id="perfilId" value="<?= $_SESSION['perfil']['tbperfilid'] ?>">
                    <input type="text" id="nombre" class="form-control" placeholder="Sobrenombre" required disabled>
                    <input type="text" id="contra" class="form-control" placeholder="Contraseña" required disabled>
                    <div class="input-group">
                        <span class="input-group-text" id="addonPerfil">@</span>
                        <input type="email" id="correo" class="form-control" placeholder="Correo Electrónico" required disabled>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="btnEditar">Actualizar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarPerfil" style="display: none;">Guardar</button>
                        <button type="button" class="btn btn-secondary" id="btnCancelarPerfil" style="display:none;">Cancelar edición</button>
                    </div>
                </form>
            </div>

            <div class="contenedorFormulario" id="contenedorFormularioUbicacion">
                <h1>Ubicación</h1>

                <form id="formUbicacion" class="mt-3">
                    <select id="provincia" class="form-select" required>
                        <option value="">Seleccione provincia</option>
                    </select>

                    <select id="canton" class="form-select" required disabled>
                        <option value="">Seleccione cantón</option>
                    </select>

                    <select id="distrito" class="form-select" required disabled>
                        <option value="">Seleccione distrito</option>
                    </select>

                    <div id="mapaUbicacion" style="height: 300px;"></div>
                    <p class="small text-center text-secondary mb-0">Haga clic en el mapa para marcar su ubicación exacta</p>

                    <input type="hidden" id="latitud" value="">
                    <input type="hidden" id="longitud" value="">

                    <button type="submit" class="btn btn-primary w-100" id="btnGuardarUbicacion">Guardar ubicación</button>
                </form>
            </div>

        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/perfilcliente.js"></script>
    <script src="js/ubicacion.js"></script>
</body>

</html>
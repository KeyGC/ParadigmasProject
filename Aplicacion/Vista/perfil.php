<?php

if (!isset($_SESSION['perfil'])) {
    header('Location: index.php?vista=login');
    exit;
}
$usuarioActivo = $_SESSION['perfil']['tbperfilnombre'] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente-MatchCitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoCliente">

    <?php include APP_PATH . '/Vista/Componentes/navbar.php'; ?>

    <main>
        <div class="container-fluid p-5 text-center" id="contenedorPPerfil">


            <div class="container contenedorFormulario p-5 me-5" id="contenedorFormularioPerfil">
                <h1>Información Personal</h1>
                <div id="alertaPerfil"></div>

                <form id="formPerfil">
                    <input type="hidden" id="perfilId" value="<?= $_SESSION['perfil']['tbperfilid'] ?>">
                    <input type="text" id="nombre" class="form-control p-2 my-4" placeholder="Sobrenombre" required disabled>
                    <input type="text" id="contra" class="form-control p-2 mb-4" placeholder="Contraseña" required disabled>
                    <div class="input-group">
                        <span class="input-group-text" id="addonPerfil">@</span>
                        <input type="email" id="correo" class="form-control p-2" placeholder="Correo Electrónico" required disabled>
                    </div>
                    <button type="button" class="btn btn-primary p-2 my-4" id="btnEditar">Actualizar</button>
                    <button type="submit" class="btn btn-primary p-2 mt-4" id="btnGuardarPerfil" style="display: none;">Guardar</button>
                    <button type="button" class="btn btn-secondary p-2 my-4" id="btnCancelarPerfil" style="display:none;">Cancelar edición</button>
                </form>
            </div>


            <div class="container contenedorFormulario p-5" id="contenedorFormularioUbicacion">
                <h1>Ubicación</h1>

                <form id="formUbicacion">
                    <select id="provincia" class="form-control my-3" required>
                        <option value="">Seleccione provincia</option>
                    </select>

                    <select id="canton" class="form-control my-3" required disabled>
                        <option value="">Seleccione cantón</option>
                    </select>

                    <select id="distrito" class="form-control my-3" required disabled>
                        <option value="">Seleccione distrito</option>
                    </select>

                    <div id="mapaUbicacion" style="height: 300px; border-radius: 12px;" class="my-3"></div>
                    <p class="small">Haga clic en el mapa para marcar su ubicación exacta</p>

                    <input type="hidden" id="latitud" value="">
                    <input type="hidden" id="longitud" value="">

                    <button type="submit" class="btn btn-primary p-2 my-4" id="btnGuardarUbicacion">Guardar</button>
                </form>
            </div>

        </div>
    </main>

    <?php include APP_PATH . '/Vista/Componentes/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/perfilCliente.js"></script>
    <script src="js/ubicacion.js"></script>
</body>

</html>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body id="bodyLogin">
    <main>
        <div class="container-fluid p-5 text-center" id="contenedorPrincipal">
            <div class="container bg contenedorFormulario text-light p-5 mt-5" id="">
                <h1>Registro</h1>

                <div id="alertaRegistro"></div>

                <form id="registroForm">
                    <input type="text" id="nombre" class="form-control my-3" placeholder="SobreNombre" required>
                    <input type="email" id="correo" class="form-control mb-3" placeholder="Correo Electrónico" required>
                    <button type="submit" class="btn btn-primary my-3">Registrarme</button>
                    <a href="index.php?vista=login" class="btn btn-link p-0">Ya tienes cuenta? Inicia sesión</a>
                </form>
            </div>
        </div>
    </main>

    <script src="js/registro.js"></script>
</body>

</html>
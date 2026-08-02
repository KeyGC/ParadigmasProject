<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body id="bodyLogin">
    <main>
        <div class="container-fluid  p-5  text-center" id="contenedorPrincipal">
            <div class="container bg text-light p-5 mt-5" id="contenedorFormulario">
                <h1>Login</h1>
                <form id="loginForm" >
                    <input type="text" id="nombre" class="form-control my-3" placeholder="SobreNombre" required>
                    <input type="password" id="contra" class="form-control mb-3" placeholder="Contraseña" required>
                    <button type="submit" class="btn btn-primary my-3">Iniciar Sesión</button>
                    <a href="registro.php" class="btn btn-link p-0">Aun no tienes cuenta?</a>
                </form>
            </div>
        </div>
    </main>

</body>

</html>
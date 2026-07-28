<!-- Aplicacion/Vista/perfiles.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CRUD de Perfiles</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body>

    <div class="container">

        <div class="info-container">
            <h1>Gestión de Perfiles</h1>

            <form id="formPerfil">
                <input type="hidden" id="perfilId" value="">
                <input type="text" id="nickname" class="inputPerfil" placeholder="Sobre-Nombre" required>
                <input type="text" id="password" class="inputPerfil" placeholder="Contraseña" required>
                <button type="submit" class="btnPerfil" id="btnGuardar">Guardar</button>
                <button type="button" class="btnPerfil" id="btnCancelar" style="display:none;">Cancelar edición</button>
            </form>

        </div>


        <div class="tabla-container">
            <input type="text" id="buscador" placeholder="Buscar por nombre..." class="inputTabla">

            <table class="tabla" id="tablaPerfiles" border="1" cellpadding="8" style="margin-top:20px; width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Contraseña</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpoTabla">
                    <!-- Se llena por AJAX -->
                </tbody>
            </table>
        </div>



    </div>

    <footer>
        <p>&copy; 2026 Gestión de Perfiles</p>
    </footer>


    <script src="js/perfil.js"></script>
</body>

</html>
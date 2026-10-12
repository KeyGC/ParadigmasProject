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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis de Discurso</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/estilos.css">
</head>

<body class="cuerpoAdmin">

    <?php require_once APP_PATH . '/Vista/Componentes/navbaradmin.php'; ?>

    <div class="container-fluid fondo-panel">
        <div class="container contenedor-panel">

            <div class="encabezado-panel">
                <div>
                    <h1 class="titulo-pagina">Análisis de Discurso</h1>
                    <p class="subtitulo-pagina">Intereses detectados, señales de moderación y cohortes de afinidad.</p>
                </div>
            </div>

            <p class="text-danger small" id="analisisError" hidden></p>

            <div class="tarjeta-panel mb-4">
                <h2 class="titulo-tarjeta">Intereses por tema</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Categoría</th>
                                <th>Tema</th>
                                <th class="text-end">Señales</th>
                                <th class="text-end">Perfiles</th>
                            </tr>
                        </thead>
                        <tbody id="analisisTemas"></tbody>
                    </table>
                </div>
                <p class="text-secondary small mb-0" id="analisisTemasVacio">Sin señales registradas.</p>
            </div>

            <div class="tarjeta-panel">
                <h2 class="titulo-tarjeta">Cohortes de afinidad</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Tema</th>
                                <th>Origen</th>
                                <th>Estado</th>
                                <th class="text-end">Miembros</th>
                                <th class="text-end">Aceptados</th>
                                <th class="text-end">Pendientes</th>
                                <th class="text-end">Rechazados</th>
                                <th class="text-end">Grupo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="analisisCohortes"></tbody>
                    </table>
                </div>
                <p class="text-secondary small mb-0" id="analisisCohortesVacio">Sin cohortes de afinidad.</p>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict';
            var apiUrl = 'apiadminafinidad.php';
            var error = document.getElementById('analisisError');

            function consultar(params) {
                return fetch(apiUrl + '?' + new URLSearchParams(params), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); });
            }

            function escribir(datos) {
                var temas = document.getElementById('analisisTemas');
                var cohortes = document.getElementById('analisisCohortes');
                temas.innerHTML = '';
                cohortes.innerHTML = '';

                var listaTemas = (datos && datos.temas) || [];
                var listaCohortes = (datos && datos.cohortes) || [];

                document.getElementById('analisisTemasVacio').hidden = listaTemas.length > 0;
                document.getElementById('analisisCohortesVacio').hidden = listaCohortes.length > 0;

                listaTemas.forEach(function (t) {
                    var fila = document.createElement('tr');
                    fila.innerHTML = '<td>' + escapeHtml(t.tipo) + '</td>' +
                        '<td>' + escapeHtml(t.categoria) + '</td>' +
                        '<td>' + escapeHtml(t.tema || '—') + '</td>' +
                        '<td class="text-end">' + t.senales + '</td>' +
                        '<td class="text-end">' + t.perfiles + '</td>';
                    temas.appendChild(fila);
                });

                listaCohortes.forEach(function (c) {
                    var fila = document.createElement('tr');
                    fila.innerHTML = '<td>' + escapeHtml(c.tbafinidadtema || c.tbafinidadcategoria) + '</td>' +
                        '<td>' + escapeHtml(c.tbafinidadorigen) + '</td>' +
                        '<td>' + escapeHtml(c.tbafinidadestado) + '</td>' +
                        '<td class="text-end">' + c.miembros + '</td>' +
                        '<td class="text-end">' + c.aceptados + '</td>' +
                        '<td class="text-end">' + c.pendientes + '</td>' +
                        '<td class="text-end">' + c.rechazados + '</td>' +
                        '<td class="text-end">' + (c.tbgrupoid ? ('#' + c.tbgrupoid) : '—') + '</td>' +
                        '<td></td>';

                    if (c.tbafinidadestado !== 'cerrada') {
                        var boton = document.createElement('button');
                        boton.type = 'button';
                        boton.className = 'btn btn-sm btn-outline-danger';
                        boton.textContent = 'Cerrar';
                        boton.addEventListener('click', function () { cerrar(c.tbafinidadid); });
                        fila.lastChild.appendChild(boton);
                    }

                    cohortes.appendChild(fila);
                });
            }

            function escapeHtml(v) {
                var d = document.createElement('div');
                d.textContent = v == null ? '' : String(v);
                return d.innerHTML;
            }

            function cargar() {
                error.hidden = true;
                consultar({ accion: 'getResumen' })
                    .then(function (r) {
                        if (!r.exito) {
                            error.textContent = r.mensaje || 'No se pudo cargar el resumen.';
                            error.hidden = false;
                            return;
                        }
                        escribir(r.data);
                    })
                    .catch(function () {
                        error.textContent = 'Sin conexión con el servidor.';
                        error.hidden = false;
                    });
            }

            function cerrar(afinidadId) {
                var datos = new URLSearchParams();
                datos.append('accion', 'cerrar');
                datos.append('afinidadId', afinidadId);
                fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: datos.toString()
                }).then(function (r) { return r.json(); }).then(function () { cargar(); });
            }

            document.addEventListener('DOMContentLoaded', cargar);
        })();
    </script>
</body>

</html>

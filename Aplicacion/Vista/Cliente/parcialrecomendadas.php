<?php
// Parcial reutilizable: "Recomendadas para ti". No depende de comunidades.
// Requiere que js/notificaciones.js esté cargado (lo incluye la navbar).
?>
<section class="mt-5" id="recomendadasSeccion">
    <header class="mb-3">
        <h2 class="h5 mb-1">Recomendadas para ti</h2>
        <p class="text-secondary mb-0">
            Afinidades detectadas por tus intereses. Acepta para conectar con quienes comparten el tema.
        </p>
    </header>

    <p class="text-danger small mb-2" id="recomendadasError" hidden></p>

    <div class="row g-3" id="recomendadasLista"></div>

    <p class="chat-vacio text-secondary mb-0" id="recomendadasVacio" hidden>
        Aún no hay afinidades para tus intereses.
    </p>
</section>

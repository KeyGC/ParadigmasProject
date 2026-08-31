document.addEventListener('DOMContentLoaded', () => {
    const idPerfil   = document.getElementById('idPerfil').value;
    const tipoPerfil = document.getElementById('tipoPerfil').value;
    generarPerfilado(idPerfil, tipoPerfil);
});

function generarPerfilado(idPerfil, tipo) {
    fetch(`apiperfilado.php?accion=getPerfilado&perfilId=${idPerfil}&tipo=${encodeURIComponent(tipo)}`)
        .then(res => res.json())
        .then(res => {
            document.getElementById('contenedorCargando').style.display = 'none';

            if (!res.exito) {
                const contenedor = document.getElementById('contenedorSinDatos');
                contenedor.style.display = 'block';
                contenedor.textContent   = res.mensaje;
                return;
            }

            document.getElementById('contenedorResultados').style.display = 'block';

            const contenedorTarjetas = document.getElementById('tarjetasResultado');
            contenedorTarjetas.innerHTML = '';

            const etiquetasTipo = {
                'especifico': 'Patron especifico (dia y hora)',
                'franja':     'Patron por franja horaria',
                'dia':        'Patron por dia de la semana'
            };

            const bordes = [
                'var(--color-dorado)',
                'var(--color-oscuro)',
                'var(--color-oscuro-suave)'
            ];

            res.resultados.forEach((r, i) => {
                const col = document.createElement('div');
                col.className = 'col-md-4';
                col.innerHTML = `
                    <div class="tarjeta-panel h-100"
                         style="border-top: 4px solid ${bordes[i] || 'var(--color-dorado)'};">
                        <h5 class="titulo-tarjeta">Resultado ${i + 1}</h5>
                        <p>${r.texto}.</p>
                        <p class="subtitulo-pagina mb-0" style="font-size:0.85rem;">
                            Confianza: <strong>${r.confianza}%</strong><br>
                            Basado en <strong>${r.soporte}</strong> registros
                        </p>
                    </div>
                `;
                contenedorTarjetas.appendChild(col);
            });

            document.getElementById('notaEventos').textContent =
                `Modelo entrenado con ${res.totalEventos} eventos registrados en total.`;
        })
        .catch(err => {
            document.getElementById('contenedorCargando').style.display = 'none';
            const contenedor = document.getElementById('contenedorSinDatos');
            contenedor.style.display = 'block';
            contenedor.textContent   = 'Error al conectar con el servidor.';
            console.error('Error al generar el perfilado:', err);
        });
}
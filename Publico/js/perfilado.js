document.addEventListener('DOMContentLoaded', () => {
    const idPerfil = document.getElementById('idPerfil').value;
    generarPerfilado(idPerfil);
});

function generarPerfilado(idPerfil) {
    fetch(`apiperfilmusical.php?accion=getPerfilado&perfilId=${idPerfil}`)
        .then(res => res.json())
        .then(res => {
            document.getElementById('contenedorCargando').style.display = 'none';

            if (!res.exito) {
                const contenedor = document.getElementById('contenedorSinDatos');
                contenedor.style.display = 'block';
                contenedor.textContent = res.mensaje;
                return;
            }

            document.getElementById('contenedorResultados').style.display = 'block';
            const contenedorTarjetas = document.getElementById('tarjetasResultado');
            contenedorTarjetas.innerHTML = '';

            const colores = ['#db2777', '#9d174d', '#831843'];

            const etiquetasTipo = {
                'especifico': '📍 Patrón específico (día y hora)',
                'franja': '🕐 Patrón por franja horaria',
                'dia': '📅 Patrón por día de la semana'
            };

            res.resultados.forEach((r, i) => {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-3';
                col.innerHTML = `
                    <div class="card h-100" style="border-top: 4px solid ${colores[i] || '#db2777'};">
                        <div class="card-body">
                            <span class="badge bg-secondary mb-2">${etiquetasTipo[r.tipo] || ''}</span>
                            <h5 class="card-title">Resultado ${i + 1}</h5>
                            <p class="card-text">${r.texto}.</p>
                            <p class="text-muted small">
                                Confianza: ${r.confianza}%<br>
                                Basado en ${r.soporte} reproducciones
                            </p>
                        </div>
                    </div>
                `;
                contenedorTarjetas.appendChild(col);
            });
            const nota = document.createElement('p');
            nota.className = 'text-secondary mt-3';
            nota.textContent = `Modelo entrenado con ${res.totalEventos} eventos de reproducción en total.`;
            document.getElementById('contenedorResultados').appendChild(nota);
        })
        .catch(err => console.error('Error al generar el perfilado:', err));
}
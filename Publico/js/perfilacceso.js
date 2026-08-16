document.addEventListener('DOMContentLoaded', () => {
    const idPerfil = document.getElementById('idPerfil').value;
    cargarMatriz(idPerfil);
});

function cargarMatriz(idPerfil) {
    fetch(`apiperfilacceso.php?accion=getMatriz&idPerfil=${idPerfil}`)
        .then(res => res.json())
        .then(res => {
            if (!res.exito) {
                alert(res.mensaje);
                return;
            }

            const data = res.data;
            document.getElementById('tituloPerfil').textContent = `Accesos de: ${data.perfil}`;

            const fp = data.fechaPrimera ? new Date(data.fechaPrimera).toLocaleString() : 'Sin registros';
            const fu = data.fechaUltima ? new Date(data.fechaUltima).toLocaleString() : 'Sin registros';
            document.getElementById('resumenFechas').textContent =
                `Primer acceso: ${fp} | Último acceso: ${fu}`;

            const dias = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];
            const cuerpo = document.getElementById('cuerpoMatriz');
            cuerpo.innerHTML = '';

            const semanas = {};
            data.semanas.forEach(item => {
                if (!semanas[item.semana]) semanas[item.semana] = {};
                semanas[item.semana][item.dia] = item.cantidad;
            });

            const numerosSemana = Object.keys(semanas).sort((a, b) => a - b);

            if (numerosSemana.length === 0) {
                cuerpo.innerHTML = '<tr><td colspan="9">Este perfil aún no tiene accesos registrados</td></tr>';
                return;
            }

            numerosSemana.forEach(semana => {
                let total = 0;
                let fila = `<tr><td><strong>${semana}</strong></td>`;
                dias.forEach(dia => {
                    const cantidad = semanas[semana][dia] || 0;
                    total += cantidad;
                    fila += `<td>${cantidad > 0 ? cantidad : '-'}</td>`;
                });
                fila += `<td><strong>${total}</strong></td></tr>`;
                cuerpo.innerHTML += fila;
            });
        })
        .catch(err => console.error('Error al cargar la matriz de accesos:', err));
}
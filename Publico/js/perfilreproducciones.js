document.addEventListener('DOMContentLoaded', () => {
    const idPerfil = document.getElementById('idPerfil').value;
    cargarReproducciones(idPerfil);
});

function cargarReproducciones(idPerfil) {
    fetch(`apicancion.php?accion=getReproduccionesPorPerfil&perfilId=${idPerfil}`)
        .then(res => res.json())
        .then(res => {
            if (!res.exito) {
                alert(res.mensaje);
                return;
            }

            const cuerpo = document.getElementById('cuerpoReproducciones');
            cuerpo.innerHTML = '';

            if (res.data.length === 0) {
                cuerpo.innerHTML = '<tr><td colspan="4">Este perfil aún no ha reproducido canciones</td></tr>';
                return;
            }

            res.data.forEach(r => {
                cuerpo.innerHTML += `
                    <tr>
                        <td>${r.tbcancionnombre}</td>
                        <td>${r.tbcancionartista}</td>
                        <td>${r.tbreproducciontiempo}</td>
                        <td>${r.tbreproduccioncontador}</td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error('Error al cargar reproducciones:', err));
}
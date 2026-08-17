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
                cuerpo.innerHTML = '<tr><td colspan="5">Este perfil aún no ha reproducido canciones</td></tr>';
                return;
            }

            res.data.forEach(r => {
                cuerpo.innerHTML += `
                    <tr>
                        <td>${r.tbcancionnombre}</td>
                        <td>${r.tbcancionartista}</td>
                        <td>${r.tbreproducciontiempo}</td>
                        <td>${r.tbreproduccioncontador}</td>
                        <td>
                            <button onclick="toggleEstado(${r.tbreproduccionid}, '${idPerfil}')" style="margin: 5px; padding: 5px 10px; color: white; background-color: #DC3545; border: none; cursor: pointer;">
                                Desactivar
                            </button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error('Error al cargar reproducciones:', err));
}

function toggleEstado(id, idPerfil) {
    if (!confirm('¿Confirma que desea desactivar este registro de reproducción?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch('apicancion.php?accion=toggleReproduccionEstado', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                cargarReproducciones(idPerfil);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}
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
                cuerpo.innerHTML = '<tr><td colspan="6">Este perfil aún no ha reproducido canciones</td></tr>';
                return;
            }

            res.data.forEach(r => {
                const activo = r.tbreproduccionestado == 1;
                cuerpo.innerHTML += `
                    <tr>
                        <td class="fw-medium">${r.tbcancionnombre}</td>
                        <td>${r.tbcancionartista}</td>
                        <td class="text-center">${r.tbreproducciontiempo}</td>
                        <td class="text-center">${r.tbreproduccioncontador}</td>
                        <td class="estado-celda">
                            <span class="badge-estado ${activo ? 'activo' : 'inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
                        </td>
                        <td>
                            <button class="accion-tabla ${activo ? 'accion-desactivar' : 'accion-activar'}" onclick="toggleEstado(${r.tbreproduccionid}, '${idPerfil}')">
                                ${activo ? 'Desactivar' : 'Activar'}
                            </button>
                        </td>
                    </tr>
                `;
            });
        })
        .catch(err => console.error('Error al cargar reproducciones:', err));
}

function toggleEstado(id, idPerfil) {
    if (!confirm('¿Confirma que desea cambiar el estado de este registro?')) return;

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
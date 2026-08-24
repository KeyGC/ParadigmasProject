document.addEventListener('DOMContentLoaded', () => {
    const idPerfil = document.getElementById('idPerfil').value;
    cargarHistoricoUbicaciones(idPerfil);
});

function cargarHistoricoUbicaciones(idPerfil) {

    fetch(`apiubicacion.php?accion=getHistoricoPerfil&idPerfil=${idPerfil}`)
        .then(res => res.json())
        .then(res => {

            if (!res.exito) {
                alert(res.mensaje);
                return;
            }

            const data = res.data;
            document.getElementById('tituloPerfil').textContent = `Histórico de Ubicaciones de: ${data.perfil}`;

            const total = data.ubicaciones.length;
            const automaticas = data.ubicaciones.filter(u => u.tipo === 'AUTOMATICA').length;
            document.getElementById('resumenHistorico').textContent =
                `Total de ubicaciones registradas: ${total} (Automáticas: ${automaticas} | Manuales: ${total - automaticas})`;

            const cuerpo = document.getElementById('cuerpoUbicaciones');
            cuerpo.innerHTML = '';

            if (!total) {
                cuerpo.innerHTML = '<tr><td colspan="8">Este perfil aún no tiene ubicaciones registradas</td></tr>';
                return;
            }

            data.ubicaciones.forEach(u => {
                cuerpo.innerHTML += `
                    <tr>
                        <td>${u.fecha}</td>
                        <td>${u.hora}</td>
                        <td>
                            <span class="badge ${u.tipo === 'AUTOMATICA' ? 'bg-success' : 'bg-primary'}">
                                ${u.tipo}
                            </span>
                        </td>
                        <td>${u.provincia}</td>
                        <td>${u.canton}</td>
                        <td>${u.distrito}</td>
                        <td>${u.latitud}</td>
                        <td>${u.longitud}</td>
                    </tr>
                `;
            });

        })
        .catch(err => console.error('Error al cargar el histórico de ubicaciones:', err));
}

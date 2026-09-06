const URL_CONTROLADOR = 'apiconcierto.php';
const URL_GENEROS = 'apigenero.php';
let listaConciertos = [];
let listaGeneros = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarGeneros();
    cargarConciertos();

    document.getElementById('formConcierto').addEventListener('submit', guardarConcierto);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
    document.getElementById('buscador').addEventListener('input', buscarConciertos);
    document.getElementById('btnNuevo').addEventListener('click', mostrarFormulario);
});

function mostrarFormulario() {
    document.getElementById('contenedorFormulario').style.display = 'block';
    document.getElementById('formConcierto').reset();
    document.getElementById('conciertoId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cargarGeneros() {
    fetch(`${URL_GENEROS}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaGeneros = respuesta.data.filter(g => g.tbgeneroestado == 1);
                const select = document.getElementById('genero');
                listaGeneros.forEach(g => {
                    const option = document.createElement('option');
                    option.value = g.tbgeneroid;
                    option.textContent = g.tbgeneronombre;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => console.error('Error al cargar géneros:', err));
}

function cargarConciertos() {
    fetch(`${URL_CONTROLADOR}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaConciertos = respuesta.data;
                pintarTabla(listaConciertos);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar conciertos:', err));
}

function buscarConciertos() {
    const query = document.getElementById('buscador').value.trim().toLowerCase();

    if (query === '') {
        pintarTabla(listaConciertos);
        return;
    }

    const filtrados = listaConciertos.filter(c =>
        c.tbconciertonombre.toLowerCase().includes(query) ||
        c.tbconciertoartista.toLowerCase().includes(query) ||
        c.tbconciertoubicacion.toLowerCase().includes(query)
    );

    pintarTabla(filtrados);
}

function pintarTabla(conciertos) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    if (conciertos.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="8" class="text-center">No se encontraron resultados</td></tr>`;
        return;
    }

    conciertos.forEach(c => {
        const activo = c.tbconciertoestado == 1;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td class="fw-medium">${c.tbconciertonombre}</td>
            <td>${c.tbconciertoartista}</td>
            <td>${c.tbgeneronombre}</td>
            <td>${c.tbconciertoubicacion}</td>
            <td>${c.tbconciertofecha}</td>
            <td>${c.tbconciertohora}</td>
            <td class="estado-celda">
                <span class="badge-estado ${activo ? 'activo' : 'inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
            </td>
            <td>
                <button class="accion-tabla" onclick="editarConcierto(${c.tbconciertoid})">Editar</button>
                <button class="accion-tabla ${activo ? 'accion-desactivar' : 'accion-activar'}" onclick="toggleEstado(${c.tbconciertoid})">
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

function editarConcierto(id) {
    fetch(`${URL_CONTROLADOR}?accion=getConcierto&id=${id}`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                const c = respuesta.data;
                document.getElementById('contenedorFormulario').style.display = 'block';
                document.getElementById('conciertoId').value = c.tbconciertoid;
                document.getElementById('nombre').value = c.tbconciertonombre;
                document.getElementById('artista').value = c.tbconciertoartista;
                document.getElementById('genero').value = c.tbgeneroid;
                document.getElementById('ubicacion').value = c.tbconciertoubicacion;
                document.getElementById('latitud').value = c.tbconciertolatitud;
                document.getElementById('longitud').value = c.tbconciertolongitud;
                document.getElementById('fecha').value = c.tbconciertofecha;
                document.getElementById('hora').value = c.tbconciertohora.substring(0, 5);
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(respuesta.mensaje);
            }
        });
}

function cancelarEdicion() {
    document.getElementById('formConcierto').reset();
    document.getElementById('conciertoId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    document.getElementById('contenedorFormulario').style.display = 'none';
}

function guardarConcierto(e) {
    e.preventDefault();

    const id = document.getElementById('conciertoId').value;

    const formData = new FormData();
    formData.append('nombre', document.getElementById('nombre').value);
    formData.append('artista', document.getElementById('artista').value);
    formData.append('generoId', document.getElementById('genero').value);
    formData.append('ubicacion', document.getElementById('ubicacion').value);
    formData.append('latitud', document.getElementById('latitud').value);
    formData.append('longitud', document.getElementById('longitud').value);
    formData.append('fecha', document.getElementById('fecha').value);
    formData.append('hora', document.getElementById('hora').value);

    let accion = 'insert';
    if (id) {
        accion = 'update';
        formData.append('id', id);
    }

    fetch(`${URL_CONTROLADOR}?accion=${accion}`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            alert(respuesta.mensaje);
            if (respuesta.exito) {
                cancelarEdicion();
                document.getElementById('buscador').value = '';
                cargarConciertos();
            }
        })
        .catch(err => console.error('Error al guardar concierto:', err));
}

function toggleEstado(id) {
    if (!confirm('¿Confirma que desea cambiar el estado de este concierto?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(`${URL_CONTROLADOR}?accion=toggleEstado`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                cargarConciertos();
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}
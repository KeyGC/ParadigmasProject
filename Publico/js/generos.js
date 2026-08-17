const URL_CONTROLADOR = 'apigenero.php';
let listaGeneros = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarGeneros();

    document.getElementById('formGenero').addEventListener('submit', guardarGenero);
    document.getElementById('btnCancelar').addEventListener('click', cancelarEdicion);
    document.getElementById('buscador').addEventListener('input', buscarGeneros);
    document.getElementById('btnNuevo').addEventListener('click', mostrarFormulario);
});

function mostrarFormulario() {
    document.getElementById('contenedorFormulario').style.display = 'block';
    document.getElementById('formGenero').reset();
    document.getElementById('generoId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function cargarGeneros() {
    fetch(`${URL_CONTROLADOR}?accion=getList`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                listaGeneros = respuesta.data;
                pintarTabla(listaGeneros);
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cargar géneros:', err));
}

function buscarGeneros() {
    const query = document.getElementById('buscador').value.trim().toLowerCase();

    if (query === '') {
        pintarTabla(listaGeneros);
        return;
    }

    const filtrados = listaGeneros.filter(g =>
        g.tbgeneronombre.toLowerCase().includes(query)
    );

    pintarTabla(filtrados);
}

function pintarTabla(generos) {
    const cuerpo = document.getElementById('cuerpoTabla');
    cuerpo.innerHTML = '';

    if (generos.length === 0) {
        cuerpo.innerHTML = `<tr><td colspan="4" style="text-align:center;">No se encontraron resultados</td></tr>`;
        return;
    }

    generos.forEach(g => {
        const activo = g.tbgeneroestado == 1;
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${g.tbgeneronombre}</td>
            <td>${activo ? 'Activo' : 'Inactivo'}</td>
            <td>
                <button onclick="editarGenero(${g.tbgeneroid})" style="margin: 5px; padding: 5px 10px; color: white; background-color: #2C5F8A; border: none; cursor: pointer;">Editar</button>
                <button onclick="toggleEstado(${g.tbgeneroid})" style="margin: 5px; padding: 5px 10px; color: white; background-color: ${activo ? '#DC3545' : '#28A745'}; border: none; cursor: pointer;">
                    ${activo ? 'Desactivar' : 'Activar'}
                </button>
            </td>
        `;
        cuerpo.appendChild(fila);
    });
}

function editarGenero(id) {
    fetch(`${URL_CONTROLADOR}?accion=getGenero&id=${id}`)
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                document.getElementById('contenedorFormulario').style.display = 'block';
                document.getElementById('generoId').value = respuesta.data.tbgeneroid;
                document.getElementById('nombre').value = respuesta.data.tbgeneronombre;
                document.getElementById('btnGuardar').textContent = 'Actualizar';
                document.getElementById('btnCancelar').style.display = 'inline-block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                alert(respuesta.mensaje);
            }
        });
}

function cancelarEdicion() {
    document.getElementById('formGenero').reset();
    document.getElementById('generoId').value = '';
    document.getElementById('btnGuardar').textContent = 'Guardar';
    document.getElementById('btnCancelar').style.display = 'none';
    document.getElementById('contenedorFormulario').style.display = 'none';
}

function guardarGenero(e) {
    e.preventDefault();

    const id = document.getElementById('generoId').value;
    const nombre = document.getElementById('nombre').value;

    const formData = new FormData();
    formData.append('nombre', nombre);

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
                cargarGeneros();
            }
        })
        .catch(err => console.error('Error al guardar género:', err));
}

function toggleEstado(id) {
    if (!confirm('¿Confirma que desea cambiar el estado de este género?')) return;

    const formData = new FormData();
    formData.append('id', id);

    fetch(`${URL_CONTROLADOR}?accion=toggleEstado`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(respuesta => {
            if (respuesta.exito) {
                cargarGeneros();
            } else {
                alert(respuesta.mensaje);
            }
        })
        .catch(err => console.error('Error al cambiar estado:', err));
}

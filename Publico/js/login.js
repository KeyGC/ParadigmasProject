const URL_CONTROLADOR = 'api.php';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('loginForm').addEventListener('submit', loguear);
});

function loguear(e) {
    e.preventDefault();

    const nombre = document.getElementById('nombre').value.trim();
    const contra = document.getElementById('contra').value.trim();

    const formData = new FormData();
    formData.append('nombre', nombre);
    formData.append('contra', contra);

    fetch(`${URL_CONTROLADOR}?accion=login`, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(resp => {
            if (resp.exito) {
                window.location.href = 'index.php?vista=cliente';
            } else {
                document.getElementById('alertaLogin').innerHTML = `<div class="alert alert-danger">${resp.mensaje}</div>`;
            }
        })
        .catch(err => {
            document.getElementById('alertaLogin').innerHTML = `<div class="alert alert-danger">Error de conexión: ${err}</div>`;
        });
}
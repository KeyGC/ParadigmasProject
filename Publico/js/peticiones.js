
var MENSAJES_PETICION = {
    red: 'No se pudo conectar con el servidor. Verifica tu conexion e intenta nuevamente.',
    servidor: 'Ocurrio un error en el servidor. Intenta nuevamente mas tarde.',
    autenticacion: 'Tu sesion expiro. Inicia sesion nuevamente.',
    permiso: 'No tienes permiso para realizar esta accion.'
};

var __sesionRedirigida = false;

function redirigirLogin() {
    if (__sesionRedirigida) return;
    __sesionRedirigida = true;
    if (typeof window !== 'undefined' && window.location) {
        window.location.href = 'index.php?vista=login';
    }
}

function __errorPeticion(mensaje, tipo, estado) {
    var error = new Error(mensaje);
    error.tipo = tipo;
    error.estado = estado || 0;
    return error;
}

function peticion(url, opciones) {
    return fetch(url, opciones)
        .catch(function () {
            throw __errorPeticion(MENSAJES_PETICION.red, 'red', 0);
        })
        .then(function (respuesta) {
            var estado = respuesta.status || 0;
            return respuesta.text().then(function (texto) {
                var datos = null;
                if (texto) {
                    try {
                        datos = JSON.parse(texto);
                    } catch (e) {
                        datos = null;
                    }
                }

                if (datos && typeof datos === 'object' && typeof datos.exito !== 'undefined') {
                    if (!datos.tipo) {
                        if (estado === 401) datos.tipo = 'autenticacion';
                        else if (estado === 403) datos.tipo = 'permiso';
                    }
                    return datos;
                }

                if (estado === 401 || estado === 403) {
                    return {
                        exito: false,
                        tipo: estado === 401 ? 'autenticacion' : 'permiso',
                        mensaje: MENSAJES_PETICION[estado === 401 ? 'autenticacion' : 'permiso']
                    };
                }

                throw __errorPeticion(MENSAJES_PETICION.servidor, 'servidor', estado);
            });
        });
}

function esSesionInvalida(datos) {
    return !!datos && (datos.tipo === 'autenticacion' || datos.tipo === 'permiso');
}

function manejarSesion(datos) {
    if (datos && datos.tipo === 'autenticacion') {
        redirigirLogin();
        return true;
    }
    return false;
}

function mostrarErrorPeticion(error, destino) {
    var mensaje = (error && error.message) ? error.message : MENSAJES_PETICION.servidor;
    if (typeof destino === 'function') {
        destino(mensaje);
    } else if (destino && typeof destino.textContent !== 'undefined') {
        destino.textContent = mensaje;
    } else if (typeof alert !== 'undefined') {
        alert(mensaje);
    }
}

var Peticiones = {
    peticion: peticion,
    redirigirLogin: redirigirLogin,
    esSesionInvalida: esSesionInvalida,
    manejarSesion: manejarSesion,
    mostrarErrorPeticion: mostrarErrorPeticion,
    MENSAJES: MENSAJES_PETICION
};

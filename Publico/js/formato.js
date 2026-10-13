// Helpers de formato de presentación (fechas en dd/mm/aaaa y hora HH:mm,
// porcentajes con decimales consistentes). Se comparten entre las vistas para
// no repetir la lógica en cada archivo.

function formatearFecha(valor) {
    if (!valor) return '';
    var partes = String(valor).replace('T', ' ').split(' ');
    var fecha = partes[0].split('-');
    if (fecha.length !== 3) return String(valor);
    return fecha[2] + '/' + fecha[1] + '/' + fecha[0];
}

function formatearHora(valor) {
    if (!valor) return '';
    var partes = String(valor).replace('T', ' ').split(' ');
    return partes[1] ? partes[1].slice(0, 5) : '';
}

function formatearFechaHora(valor) {
    var fecha = formatearFecha(valor);
    var hora = formatearHora(valor);
    return (fecha + ' ' + hora).trim();
}

function formatearPorcentaje(valor, decimales) {
    var numero = Number(valor);
    if (isNaN(numero)) return '';
    var posiciones = typeof decimales === 'number' ? decimales : 1;
    return numero.toFixed(posiciones) + '%';
}

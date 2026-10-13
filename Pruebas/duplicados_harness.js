'use strict';


const fs = require('fs');
const vm = require('vm');
const path = require('path');

const JS = process.env.CHAT_JS || path.join(__dirname, '..', 'Publico', 'js', 'chat.js');
const JS_COM = process.env.COM_JS || path.join(__dirname, '..', 'Publico', 'js', 'comunidades.js');
const JS_GRUPO = process.env.GRUPO_JS || path.join(__dirname, '..', 'Publico', 'js', 'grupos.js');
const JS_NOTIF = process.env.NOTIF_JS || path.join(__dirname, '..', 'Publico', 'js', 'notificaciones.js');

const ME = 7;
const OTRO = 9;


const CHAT_IDS = [
    'chatConversaciones', 'chatListaVacia', 'chatNombre', 'chatMensajes',
    'chatMensajesVacio', 'chatForm', 'chatTexto', 'chatEnviar', 'chatEstado'
];
const COMUNIDAD_IDS = [
    'comunidadCatalogo', 'comunidadCargando', 'comunidadVacio', 'comunidadSala',
    'comunidadVolver', 'comunidadNombre', 'comunidadMensajes', 'comunidadMensajesVacio',
    'comunidadForm', 'comunidadTexto', 'comunidadEnviar', 'comunidadEstado',
    'comunidadError', 'comunidadSeccionGenero', 'comunidadSeccionComida',
    'comunidadSeccionDeporte', 'comunidadGridGenero', 'comunidadGridComida',
    'comunidadGridDeporte'
];
const GRUPO_IDS = [
    'grupoCatalogo', 'grupoCargando', 'grupoVacio', 'grupoLista', 'grupoSala',
    'grupoVolver', 'grupoNombre', 'grupoMiembros', 'grupoMensajes', 'grupoMensajesVacio',
    'grupoForm', 'grupoTexto', 'grupoEnviar', 'grupoEstado', 'grupoError'
];
const NOTIF_IDS = [
    'campanaBadge', 'notificacionesLista', 'notificacionesVacio', 'notificacionesError',
    'recomendadasLista', 'recomendadasVacio', 'recomendadasError'
];

class StubClassList {
    constructor(el) { this.el = el; }
    add(c) { if (!this.el._classes.includes(c)) this.el._classes.push(c); }
    remove(c) { this.el._classes = this.el._classes.filter((x) => x !== c); }
    toggle(c, f) { if (f === undefined) { this.contains(c) ? this.remove(c) : this.add(c); } else { f ? this.add(c) : this.remove(c); } }
    contains(c) { return this.el._classes.includes(c); }
}

class StubElement {
    constructor(tag) {
        this.tagName = String(tag).toUpperCase();
        this.children = [];
        this.parent = null;
        this.dataset = {};
        this._classes = [];
        this._events = {};
        this._text = '';
        this.className = '';
        this.value = '';
        this.type = '';
        this.disabled = false;
        this.hidden = false;
        this.scrollTop = 0;
        this.scrollHeight = 0;
        this.style = { setProperty() { } };
        this.classList = new StubClassList(this);
    }
    appendChild(c) { c.parent = this; this.children.push(c); return c; }
    addEventListener(t, fn) { (this._events[t] = this._events[t] || []).push(fn); }
    fire(t, evt) { (this._events[t] || []).slice().forEach((fn) => fn(evt)); }
    get innerHTML() { return this._html || ''; }
    set innerHTML(v) {
        const html = v == null ? '' : String(v);
        this._html = html;
        this.children = [];
        this._text = '';
        let m;
        const re = /data-accion="([^"]+)"/g;
        while ((m = re.exec(html)) !== null) {
            const boton = new StubElement('button');
            boton.dataset.accion = m[1];
            boton.parent = this;
            this.children.push(boton);
        }
    }
    querySelector(selector) {
        const m = /^\[data-accion="([^"]+)"\]$/.exec(String(selector));
        if (!m) { return null; }
        const out = [];
        (function walk(n) { for (const c of n.children) { out.push(c); walk(c); } })(this);
        return out.find((n) => n.dataset && n.dataset.accion === m[1]) || null;
    }
    get textContent() { return this._text + this.children.map((c) => c.textContent).join(''); }
    set textContent(v) { this._text = v == null ? '' : String(v); this.children = []; }
    focus() { }
}

class StubDocument {
    constructor() {
        this._byId = {};
        this._events = {};
        this.hidden = false;
        CHAT_IDS.concat(COMUNIDAD_IDS, GRUPO_IDS, NOTIF_IDS).forEach((id) => { this._byId[id] = new StubElement('div'); });
    }
    get(id) { return this._byId[id]; }
    getElementById(id) { return this._byId[id] || null; }
    createElement(tag) { return new StubElement(tag); }
    addEventListener(t, fn) { (this._events[t] = this._events[t] || []).push(fn); }
    fire(t, evt) { (this._events[t] || []).slice().forEach((fn) => fn(evt)); }
}

const wrap = (value) => ({
    json: () => Promise.resolve(value),
    text: () => Promise.resolve(JSON.stringify(value))
});
const v = (value) => ({ value });
const deferred = () => {
    let resolve, reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    return { promise, resolve, reject };
};

class StubFormData {
    constructor() { this._datos = []; }
    append(clave, valor) { this._datos.push([String(clave), String(valor)]); }
    get(clave) { const p = this._datos.find((d) => d[0] === clave); return p ? p[1] : null; }
    entries() { return this._datos.slice()[Symbol.iterator](); }
}

function bodyToParams(body) {
    if (body && typeof body.entries === 'function') {
        const salida = new URLSearchParams();
        for (const [clave, valor] of body.entries()) { salida.append(clave, valor); }
        return salida;
    }
    return new URLSearchParams(String(body));
}

function makeFetch(routes) {
    return function (url, opts) {
        let params = new URLSearchParams(new URL(String(url), 'http://local').search);
        if (opts && opts.body) { params = bodyToParams(opts.body); }
        const accion = params.get('accion');
        const cola = routes[accion];
        if (!cola || cola.length === 0) {
            return Promise.resolve(wrap({ exito: false, mensaje: 'ruta no definida: ' + accion }));
        }
        const entrada = cola.shift();
        if (entrada.deferred) { return entrada.deferred.promise.then(wrap); }
        return Promise.resolve(wrap(entrada.value));
    };
}

function makeTimers() {
    const cbs = {};
    let seq = 0;
    return {
        set(fn) { const id = ++seq; cbs[id] = fn; return id; },
        clear(id) { delete cbs[id]; },
        get active() { return Object.keys(cbs).length; },
        tick() { Object.values(cbs).slice().forEach((fn) => fn()); }
    };
}

function buildEnv(script, windowObj, routes) {
    const doc = new StubDocument();
    const timers = makeTimers();
    const fetchMock = makeFetch(routes);
    const sandbox = {
        window: windowObj,
        document: doc,
        fetch: fetchMock,
        setInterval: (fn) => timers.set(fn),
        clearInterval: (id) => timers.clear(id),
        URL, URLSearchParams, FormData: StubFormData,
        console: { log() { }, error() { }, warn() { } }
    };
    vm.createContext(sandbox);
    const helper = path.join(__dirname, '..', 'Publico', 'js', 'peticiones.js');
    vm.runInContext(fs.readFileSync(helper, 'utf8'), sandbox, { filename: helper });
    vm.runInContext(fs.readFileSync(script, 'utf8'), sandbox, { filename: script });
    return { doc, timers, fetchMock, routes };
}

const settle = async (n = 8) => { for (let i = 0; i < n; i++) { await new Promise((r) => setTimeout(r, 0)); } };


const conv = (id, otro, nombre) => ({
    tbconversacionid: id, tbperfilidotro: otro, tbperfilnombreotro: nombre,
    tbconversacionfechacreacion: '2026-01-01 00:00:00', tbmensajetextoultimo: null,
    tbmensajefechahoraultimo: null, tbmensajeemisorultimo: null, tbmensajenoleidos: 0
});
const msg = (id, texto, convId = 1, emisor = OTRO) => ({
    tbmensajeid: id, tbconversacionid: convId, tbperfilidemisor: emisor,
    tbmensajetexto: texto, tbmensajefechahora: '2026-01-01 10:00:00',
    tbmensajeleido: false, tbmensajebloqueado: false, tbmensajecategoria: null, tbmensajescore: null
});
const blockedChat = (convId = 1) => ({
    tbmensajeid: null, tbconversacionid: convId, tbperfilidemisor: ME,
    tbmensajetexto: '[bloqueado]', tbmensajefechahora: '2026-01-01 10:00:00',
    tbmensajeleido: false, tbmensajebloqueado: true, tbmensajecategoria: 'drogas',
    tbmensajescore: 0.91, moderacionDisponible: false
});

const comunidad = (id, nombre) => ({ tbcomunidadid: id, tbcomunidadnombre: nombre });
const catalogo = (lista) => ({ genero: lista, comida: [], deporte: [] });
const cmsg = (id, texto, comId = 1, emisor = OTRO) => ({
    tbcomunidadmensajeid: id, tbcomunidadid: comId, tbperfilidemisor: emisor,
    tbperfilnombre: 'Otro', tbcomunidadmensajetexto: texto,
    tbcomunidadmensajefechahora: '2026-01-01 10:00:00', tbcomunidadmensajebloqueado: false
});
const blockedCom = (comId = 1) => ({
    tbcomunidadmensajeid: null, tbcomunidadid: comId, tbperfilidemisor: ME,
    tbperfilnombre: 'Yo', tbcomunidadmensajetexto: '[bloqueado]',
    tbcomunidadmensajefechahora: '2026-01-01 10:00:00', tbcomunidadmensajebloqueado: true
});

const grupo = (id, nombre, miembros = 2) => ({
    tbgrupoid: id, tbgruponombre: nombre, tbgrupocategoria: 'genero',
    tbgrupocantidadmiembros: miembros, tbgrupoultimotexto: '', tbgrupomensajenoleidos: 0
});
const gmsg = (id, texto, grupoId = 1, emisor = OTRO) => ({
    tbgrupomensajeid: id, tbgrupoid: grupoId, tbperfilidemisor: emisor,
    tbperfilnombre: 'Otro', tbgrupomensajetexto: texto,
    tbgrupomensajefechahora: '2026-01-01 10:00:00', tbgrupomensajebloqueado: false
});
const blockedGrupo = (grupoId = 1) => ({
    tbgrupomensajeid: null, tbgrupoid: grupoId, tbperfilidemisor: ME,
    tbperfilnombre: 'Yo', tbgrupomensajetexto: '[bloqueado]',
    tbgrupomensajefechahora: '2026-01-01 10:00:00', tbgrupomensajebloqueado: true
});

const pendiente = (id, tema) => ({
    tbafinidadid: id, tbafinidadtipo: 'bueno', tbafinidadcategoria: 'genero',
    tbafinidadtema: tema, tbafinidadorigen: 'comunidad',
    tbafinidadfechacreacion: '2026-01-01 00:00:00', tbafinidadcantidadotros: 1,
    tbafinidadmostrarnombres: true,
    tbafinidadotros: [{ tbperfilid: OTRO, tbperfilnombre: 'Otro', tbafinidadmiembroestado: 'pendiente' }]
});
const recomendada = (id, tema) => ({
    tbafinidadid: id, tbafinidadtipo: 'bueno', tbafinidadcategoria: 'genero',
    tbafinidadtema: tema, tbafinidadorigen: 'comunidad', tbafinidadestado: 'abierta',
    tbafinidadmiembroestado: 'aceptada', tbgrupoid: 10,
    tbafinidadmostrarnombres: true, tbafinidadotros: []
});

const desc = (el) => {
    const out = [];
    (function walk(n) { for (const c of n.children) { out.push(c); walk(c); } })(el);
    return out;
};
const filas = (env, containerId) => desc(env.doc.get(containerId)).filter((n) => String(n.className).includes('chat-fila'));
const porTexto = (env, containerId, texto) => filas(env, containerId).filter((n) => n.textContent.includes(texto));
const porId = (env, containerId, id) => filas(env, containerId).filter((n) => String(n.dataset.mensaje) === String(id));
const filasLista = (env) => env.doc.get('chatConversaciones').children.filter((n) => n.tagName === 'LI');

function clickConv(env, id) {
    const li = filasLista(env).find((n) => String(n.dataset.conversacion) === String(id));
    if (!li) throw new Error('conversacion ' + id + ' no esta en la lista');
    li.children[0].fire('click', {});
}
function clickComunidad(env, id) {
    const tarjeta = desc(env.doc.get('comunidadGridGenero')).find((n) => String(n.dataset.comunidad) === String(id));
    if (!tarjeta) throw new Error('comunidad ' + id + ' no esta en el catalogo');
    tarjeta.fire('click', {});
}
const filasGrupo = (env, containerId) => desc(env.doc.get(containerId)).filter((n) => String(n.className).includes('chat-fila'));
const porTextoGrupo = (env, containerId, texto) => filasGrupo(env, containerId).filter((n) => n.textContent.includes(texto));
function clickGrupo(env, id) {
    const boton = env.doc.get('grupoLista').children.find((n) => String(n.dataset.grupo) === String(id));
    if (!boton) throw new Error('grupo ' + id + ' no esta en el listado');
    boton.fire('click', {});
}
const botonesAccion = (env, containerId, accion) => desc(env.doc.get(containerId)).filter((n) => n.dataset && n.dataset.accion === accion);

const chatConfig = { apiUrl: 'apichat.php', perfilId: ME, perfilNombre: 'Yo', conversacionInicial: 0 };
const comConfig = { apiUrl: 'apicomunidad.php', perfilId: ME, perfilNombre: 'Yo', comunidadInicial: 0 };
const grupoConfig = { apiUrl: 'apigrupo.php', perfilId: ME, perfilNombre: 'Yo', grupoInicial: 0 };
const notifConfig = { apiUrl: 'apiafinidad.php', intervalo: 10000, perfilId: ME };
const many = (value, n = 12) => Array.from({ length: n }, () => v(value));
const ok = { exito: true, data: {} };


async function chatBloqueadoUnaVez() {
    const A = conv(1, OTRO, 'Ana');
    const routes = {
        getConversaciones: many({ exito: true, data: [A] }),
        getMensajesNuevos: [v({ exito: true, data: [] }), v({ exito: true, data: [blockedChat()] })],
        enviarMensaje: [v({ exito: true, data: blockedChat() })],
        marcarLeidos: many(ok)
    };
    const env = buildEnv(JS, { CONFIG_CHAT: chatConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    env.doc.get('chatTexto').value = 'texto bloqueado';
    env.doc.get('chatForm').fire('submit', { preventDefault() { } }); await settle();
    env.timers.tick(); await settle();
    const n = porTexto(env, 'chatMensajes', '[bloqueado]').length;
    return { name: 'chat · bloqueado se pinta una sola vez (envío + tick)', ok: n === 1, detail: 'filas "[bloqueado]" = ' + n + ' (esperado 1)' };
}

async function chatEnvioDuranteFetchInicial() {
    const A = conv(1, OTRO, 'Ana');
    const hist = []; for (let i = 1; i <= 105; i++) { hist.push(msg(i, 'msg-' + i)); }
    const DEF = deferred();
    const routes = {
        getConversaciones: many({ exito: true, data: [A] }),
        getMensajesNuevos: [{ deferred: DEF }],
        enviarMensaje: [v({ exito: true, data: msg(105, 'msg-105', 1, ME) })],
        marcarLeidos: many(ok)
    };
    const env = buildEnv(JS, { CONFIG_CHAT: chatConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    env.doc.get('chatTexto').value = 'msg-105';
    env.doc.get('chatForm').fire('submit', { preventDefault() { } }); await settle();
    DEF.resolve({ exito: true, data: hist }); await settle();
    const dup = porId(env, 'chatMensajes', 105).length;
    const total = filas(env, 'chatMensajes').length;
    return { name: 'chat · envío durante fetch inicial no duplica', ok: dup === 1 && total === 105, detail: 'msg-105 = ' + dup + ', total filas = ' + total + ' (esperado 1 y 105)' };
}

async function chatCambioConPollingPendiente() {
    const A = conv(1, OTRO, 'Ana');
    const B = conv(2, OTRO, 'Bea');
    const DEF = deferred();
    const routes = {
        getConversaciones: many({ exito: true, data: [A, B] }),
        getMensajesNuevos: [
            v({ exito: true, data: [msg(1, 'msg-A1', 1)] }),
            { deferred: DEF },
            v({ exito: true, data: [msg(2, 'msg-B1', 2)] })
        ],
        marcarLeidos: many(ok)
    };
    const env = buildEnv(JS, { CONFIG_CHAT: chatConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    env.timers.tick(); await settle();
    clickConv(env, 2); await settle();
    DEF.resolve({ exito: true, data: [msg(3, 'msg-A2', 1)] }); await settle();
    const a2 = porTexto(env, 'chatMensajes', 'msg-A2').length;
    const b1 = porTexto(env, 'chatMensajes', 'msg-B1').length;
    const total = filas(env, 'chatMensajes').length;
    const intervalos = env.timers.active;
    const bien = a2 === 0 && b1 === 1 && total === 1 && intervalos === 1;
    return { name: 'chat · cambio de conversación con polling pendiente', ok: bien, detail: 'A2 = ' + a2 + ', B1 = ' + b1 + ', total = ' + total + ', intervalos = ' + intervalos };
}

async function chatReabrirNoDuplicaLista() {
    const A = conv(1, OTRO, 'Ana');
    const B = conv(2, OTRO, 'Bea');
    const routes = {
        getConversaciones: many({ exito: true, data: [A, B] }),
        getMensajesNuevos: many({ exito: true, data: [] }),
        marcarLeidos: many(ok)
    };
    const env = buildEnv(JS, { CONFIG_CHAT: chatConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickConv(env, 2); await settle();
    clickConv(env, 1); await settle();
    clickConv(env, 2); await settle();
    clickConv(env, 1); await settle();
    const rows = filasLista(env);
    const a = rows.filter((n) => String(n.dataset.conversacion) === '1').length;
    const b = rows.filter((n) => String(n.dataset.conversacion) === '2').length;
    const bien = a === 1 && b === 1 && rows.length === 2;
    return { name: 'chat · reabrir conversaciones no duplica la lista', ok: bien, detail: 'filas A=' + a + ' B=' + b + ' total=' + rows.length + ' (esperado 1,1,2)' };
}

async function comBloqueadoUnaVez() {
    const A = comunidad(1, 'Rock');
    const B = comunidad(2, 'Pop');
    const routes = {
        getComunidades: many({ exito: true, data: catalogo([A, B]) }),
        getMensajesComunidad: [v({ exito: true, data: [] }), v({ exito: true, data: [blockedCom()] })],
        enviarMensajeComunidad: [v({ exito: true, data: blockedCom() })]
    };
    const env = buildEnv(JS_COM, { CONFIG_COMUNIDAD: comConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickComunidad(env, 1); await settle();
    env.doc.get('comunidadTexto').value = 'texto bloqueado';
    env.doc.get('comunidadForm').fire('submit', { preventDefault() { } }); await settle();
    env.timers.tick(); await settle();
    const n = porTexto(env, 'comunidadMensajes', '[bloqueado]').length;
    return { name: 'comunidad · bloqueado se pinta una sola vez (envío + tick)', ok: n === 1, detail: 'filas "[bloqueado]" = ' + n + ' (esperado 1)' };
}

async function comEnvioDuranteFetchInicial() {
    const A = comunidad(1, 'Rock');
    const hist = []; for (let i = 1; i <= 50; i++) { hist.push(cmsg(i, 'msg-' + i)); }
    const DEF = deferred();
    const routes = {
        getComunidades: many({ exito: true, data: catalogo([A]) }),
        getMensajesComunidad: [{ deferred: DEF }],
        enviarMensajeComunidad: [v({ exito: true, data: cmsg(50, 'msg-50', 1, ME) })]
    };
    const env = buildEnv(JS_COM, { CONFIG_COMUNIDAD: comConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickComunidad(env, 1); await settle();
    env.doc.get('comunidadTexto').value = 'msg-50';
    env.doc.get('comunidadForm').fire('submit', { preventDefault() { } }); await settle();
    DEF.resolve({ exito: true, data: hist }); await settle();
    const dup = porTexto(env, 'comunidadMensajes', 'msg-50').length;
    const total = filas(env, 'comunidadMensajes').length;
    return { name: 'comunidad · envío durante fetch inicial no duplica', ok: dup === 1 && total === 50, detail: 'msg-50 = ' + dup + ', total filas = ' + total + ' (esperado 1 y 50)' };
}

async function comCambioSalaConPollingPendiente() {
    const A = comunidad(1, 'Rock');
    const B = comunidad(2, 'Pop');
    const DEF = deferred();
    const routes = {
        getComunidades: many({ exito: true, data: catalogo([A, B]) }),
        getMensajesComunidad: [
            v({ exito: true, data: [cmsg(1, 'msg-A1', 1)] }),
            { deferred: DEF },
            v({ exito: true, data: [cmsg(2, 'msg-B1', 2)] })
        ]
    };
    const env = buildEnv(JS_COM, { CONFIG_COMUNIDAD: comConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickComunidad(env, 1); await settle();
    env.timers.tick(); await settle();
    clickComunidad(env, 2); await settle();
    DEF.resolve({ exito: true, data: [cmsg(3, 'msg-A2', 1)] }); await settle();
    const a2 = porTexto(env, 'comunidadMensajes', 'msg-A2').length;
    const b1 = porTexto(env, 'comunidadMensajes', 'msg-B1').length;
    const total = filas(env, 'comunidadMensajes').length;
    const intervalos = env.timers.active;
    const bien = a2 === 0 && b1 === 1 && total === 1 && intervalos === 1;
    return { name: 'comunidad · cambio de sala con polling pendiente', ok: bien, detail: 'A2 = ' + a2 + ', B1 = ' + b1 + ', total = ' + total + ', intervalos = ' + intervalos };
}

async function comReabrirNoDuplicaCatalogo() {
    const A = comunidad(1, 'Rock');
    const B = comunidad(2, 'Pop');
    const routes = {
        getComunidades: many({ exito: true, data: catalogo([A, B]) }),
        getMensajesComunidad: many({ exito: true, data: [] })
    };
    const env = buildEnv(JS_COM, { CONFIG_COMUNIDAD: comConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickComunidad(env, 1); await settle();
    env.doc.get('comunidadVolver').fire('click', {}); await settle();
    clickComunidad(env, 2); await settle();
    env.doc.get('comunidadVolver').fire('click', {}); await settle();
    const tarjetas = desc(env.doc.get('comunidadGridGenero')).filter((n) => n.dataset.comunidad !== undefined);
    const a = tarjetas.filter((n) => String(n.dataset.comunidad) === '1').length;
    const b = tarjetas.filter((n) => String(n.dataset.comunidad) === '2').length;
    const bien = a === 1 && b === 1 && tarjetas.length === 2;
    return { name: 'comunidad · reabrir salas no duplica el catálogo', ok: bien, detail: 'tarjetas A=' + a + ' B=' + b + ' total=' + tarjetas.length + ' (esperado 1,1,2)' };
}

async function grupoBloqueadoUnaVez() {
    const G = grupo(1, 'Rock');
    const routes = {
        getGrupos: many({ exito: true, data: [G] }),
        getMensajesGrupo: [v({ exito: true, data: [] }), v({ exito: true, data: [blockedGrupo()] })],
        enviarMensajeGrupo: [v({ exito: true, data: blockedGrupo() })],
        marcarAbierto: many(ok)
    };
    const env = buildEnv(JS_GRUPO, { CONFIG_GRUPO: grupoConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickGrupo(env, 1); await settle();
    env.doc.get('grupoTexto').value = 'texto bloqueado';
    env.doc.get('grupoForm').fire('submit', { preventDefault() { } }); await settle();
    env.timers.tick(); await settle();
    const n = porTextoGrupo(env, 'grupoMensajes', '[bloqueado]').length;
    return { name: 'grupo · bloqueado se pinta una sola vez (envío + tick)', ok: n === 1, detail: 'filas "[bloqueado]" = ' + n + ' (esperado 1)' };
}

async function grupoEnvioDuranteFetchInicial() {
    const G = grupo(1, 'Rock');
    const hist = []; for (let i = 1; i <= 40; i++) { hist.push(gmsg(i, 'msg-' + i)); }
    const DEF = deferred();
    const routes = {
        getGrupos: many({ exito: true, data: [G] }),
        getMensajesGrupo: [{ deferred: DEF }],
        enviarMensajeGrupo: [v({ exito: true, data: gmsg(40, 'msg-40', 1, ME) })],
        marcarAbierto: many(ok)
    };
    const env = buildEnv(JS_GRUPO, { CONFIG_GRUPO: grupoConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickGrupo(env, 1); await settle();
    env.doc.get('grupoTexto').value = 'msg-40';
    env.doc.get('grupoForm').fire('submit', { preventDefault() { } }); await settle();
    DEF.resolve({ exito: true, data: hist }); await settle();
    const dup = porTextoGrupo(env, 'grupoMensajes', 'msg-40').length;
    const total = filasGrupo(env, 'grupoMensajes').length;
    return { name: 'grupo · envío durante el fetch inicial no duplica', ok: dup === 1 && total === 40, detail: 'msg-40 = ' + dup + ', total filas = ' + total + ' (esperado 1 y 40)' };
}

async function grupoCambioConPollingPendiente() {
    const A = grupo(1, 'Rock');
    const B = grupo(2, 'Pop');
    const DEF = deferred();
    const routes = {
        getGrupos: many({ exito: true, data: [A, B] }),
        getMensajesGrupo: [
            v({ exito: true, data: [gmsg(1, 'msg-A1', 1)] }),
            { deferred: DEF },
            v({ exito: true, data: [gmsg(2, 'msg-B1', 2)] })
        ],
        marcarAbierto: many(ok)
    };
    const env = buildEnv(JS_GRUPO, { CONFIG_GRUPO: grupoConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    clickGrupo(env, 1); await settle();
    env.timers.tick(); await settle();
    clickGrupo(env, 2); await settle();
    DEF.resolve({ exito: true, data: [gmsg(3, 'msg-A2', 1)] }); await settle();
    const a2 = porTextoGrupo(env, 'grupoMensajes', 'msg-A2').length;
    const b1 = porTextoGrupo(env, 'grupoMensajes', 'msg-B1').length;
    const total = filasGrupo(env, 'grupoMensajes').length;
    const intervalos = env.timers.active;
    const bien = a2 === 0 && b1 === 1 && total === 1 && intervalos === 1;
    return { name: 'grupo · cambio de grupo con polling pendiente', ok: bien, detail: 'A2 = ' + a2 + ', B1 = ' + b1 + ', total = ' + total + ', intervalos = ' + intervalos };
}

async function notifAceptarRefrescaSinDuplicar() {
    const routes = {
        contador: many({ exito: true, data: { total: 1 } }),
        getPendientes: [v({ exito: true, data: [pendiente(1, 'Rock')] }), v({ exito: true, data: [] })],
        getRecomendadas: [v({ exito: true, data: [] }), v({ exito: true, data: [recomendada(1, 'Rock')] })],
        aceptar: [v({ exito: true, data: { tbafinidadid: 1 } })]
    };
    const env = buildEnv(JS_NOTIF, { CONFIG_NOTIFICACIONES: notifConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    const antes = env.doc.get('notificacionesLista').children.length;
    const boton = botonesAccion(env, 'notificacionesLista', 'aceptar')[0];
    if (boton) { boton.fire('click', {}); await settle(); }
    const lista = env.doc.get('notificacionesLista').children.length;
    const rec = env.doc.get('recomendadasLista').children.length;
    const intervalos = env.timers.active;
    const bien = antes === 1 && boton !== undefined && lista === 0 && rec === 1 && intervalos === 1;
    return { name: 'notificaciones · aceptar refresca sin duplicar tarjetas', ok: bien, detail: 'pendientes ' + antes + '->' + lista + ', recomendadas = ' + rec + ', intervalos = ' + intervalos + ' (esperado 1->0, 1, 1)' };
}

async function notifContadorRepetidoNoDuplica() {
    const routes = {
        contador: many({ exito: true, data: { total: 2 } }),
        getPendientes: many({ exito: true, data: [pendiente(1, 'Rock'), pendiente(2, 'Jazz')] }),
        getRecomendadas: many({ exito: true, data: [] })
    };
    const env = buildEnv(JS_NOTIF, { CONFIG_NOTIFICACIONES: notifConfig }, routes);
    env.doc.fire('DOMContentLoaded'); await settle();
    for (let i = 0; i < 3; i++) { env.timers.tick(); await settle(); }
    const lista = env.doc.get('notificacionesLista').children.length;
    const intervalos = env.timers.active;
    const bien = lista === 2 && intervalos === 1;
    return { name: 'notificaciones · refrescos repetidos no duplican tarjetas', ok: bien, detail: 'tarjetas pendientes = ' + lista + ' (esperado 2), intervalos = ' + intervalos };
}

const CASES = [
    chatBloqueadoUnaVez,
    chatEnvioDuranteFetchInicial,
    chatCambioConPollingPendiente,
    chatReabrirNoDuplicaLista,
    comBloqueadoUnaVez,
    comEnvioDuranteFetchInicial,
    comCambioSalaConPollingPendiente,
    comReabrirNoDuplicaCatalogo,
    grupoBloqueadoUnaVez,
    grupoEnvioDuranteFetchInicial,
    grupoCambioConPollingPendiente,
    notifAceptarRefrescaSinDuplicar,
    notifContadorRepetidoNoDuplica
];

async function main() {
    const labelIdx = process.argv.indexOf('--label');
    const label = labelIdx > -1 ? process.argv[labelIdx + 1] : 'ACTUAL';

    console.log('== Harness duplicados chat — fase: ' + label + ' ==');
    console.log('Archivos: ' + path.relative(process.cwd(), JS) + ' , ' + path.relative(process.cwd(), JS_COM)
        + ' , ' + path.relative(process.cwd(), JS_GRUPO) + ' , ' + path.relative(process.cwd(), JS_NOTIF));
    console.log('');

    let pass = 0;
    for (const caso of CASES) {
        let res;
        try {
            res = await caso();
        } catch (e) {
            res = { name: caso.name, ok: false, detail: 'excepción: ' + e.message };
        }
        if (res.ok) { pass++; }
        console.log((res.ok ? '[PASS] ' : '[FAIL] ') + res.name);
        console.log('        ' + res.detail);
    }

    console.log('');
    console.log('Resumen: ' + pass + '/' + CASES.length + ' casos pasan');
    process.exitCode = pass === CASES.length ? 0 : 1;
}

main();

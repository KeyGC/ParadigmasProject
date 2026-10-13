# Informe de Auditoría y Correcciones (Fase B) — UNAMATCH

Fecha: 2026-10-12
Rama: KeyDeveloper

## Objetivo
Este informe documenta las correcciones realizadas conforme a la rúbrica, las limitaciones conocidas y el checklist de defensa criterio por criterio.

## 1. Hallazgos relevantes de BD (Aiven compartida)

Al consultar los perfiles con posibles duplicados de correo se identificaron 4 registros con `keylorg255@gmail.com`:
- id 5: Key (original) — conservar
- id 24: Tay
- id 25: Juan
- id 34: Pedro (NO mencionado previamente por el usuario; requiere decisión)

Total de perfiles en BD: 6.

Semántica de duplicados (ya vigente en código): `existeCorreo/existeNombre` (case-insensitive, excluyendo el propio id) bloquea editar un perfil manteniendo un correo ya duplicado con mensaje "El correo ya está registrado por otro perfil".

## 2. Plan de limpieza de correos duplicados (NO aplicar sin aprobación)

SELECT antes:
```sql
SELECT tbperfilid, tbperfilnombre, tbperfilcorreo, tbperfilrol, tbperfilactivo
FROM tbperfil
WHERE tbperfilid IN (5, 24, 25, 34)
ORDER BY tbperfilid;
```

Sentencias propuestas (sub-direccionamiento):
```sql
UPDATE tbperfil SET tbperfilcorreo='keylorg255+tay@gmail.com' WHERE tbperfilid=24;
UPDATE tbperfil SET tbperfilcorreo='keylorg255+juan@gmail.com' WHERE tbperfilid=25;
```

Resultado esperado:
- 5 -> `keylorg255@gmail.com` (conservar)
- 24 -> `keylorg255+tay@gmail.com`
- 25 -> `keylorg255+juan@gmail.com`
- 34 (Pedro) -> pendiente de decisión (duplicado con id 5 persiste si no se corrige)

**Acción requerida:** el usuario debe aprobar explícitamente la aplicación de estas sentencias en Aiven y decidir qué hacer con id 34 (Pedro). No se ejecutarán desde este entorno.

## 3. Limitación: almacenamiento de contraseñas

- Estado actual: contraseñas almacenadas en texto plano (`tbperfilcontra`). También existe historial en `perfilregistrosmodelo.php` con contraseñas en texto plano.
- Decisión del usuario: mantener tal cual por ahora. NO migrar nada.
- Documentación: se deja constancia de esta limitación con **mejora propuesta** (no aplicada):
  - Usar `password_hash()` al registrar/establecer contraseña.
  - Al login, comparar con `password_verify()` y, si el hash es antiguo/insuficiente, realizar **rehash** (`password_needs_rehash`) y actualizar en BD.
- Riesgo: exposición ante compromiso de BD. No se modificó el flujo ni las tablas por solicitud explícita.

## 4. Riesgo Qdrant: tipos/géneros activos

Qdrant aloja colección `temas` (green, 520 puntos, dimensión 1233). El clasificador híbrido usa "puerta + ranking" (`TEMAS_UMBRAL=0.35`).
- Si se **cambia la cantidad de tipos o géneros activos** (habilitar/deshabilitar muchos o cambiar catálogo) puede **recrear/reconfigurar la colección** `temas`.
- Documentar: no modificar moderación/Qdrant/perfilado más allá de mensajes. Cualquier cambio en el universo activo de tipos/géneros debe considerarse en coordinación para evitar inconsistencia entre catálogo activo y embeddings/colección Qdrant.
- Verificado: afinidad fases 1–5 + límites + throttle operativo; `temas_calibracion.php` cumple criterio (0 FP en a, >=60% en b).

## 5. Checklist de defensa por criterio (rúbrica)

### C1 — CRUD (30%)
- [x] CRUD admin de ítems de **comida** (crear, editar, activar/desactivar, eliminar con guarda).
- [x] CRUD admin de ítems de **deporte** (crear, editar, activar/desactivar, eliminar con guarda).
- [x] No se creó CRUD de comunidades ni de tipos (según solicitud).
- [x] Patrón consistente con `canciones.php`/`canciones.js`.
- [x] Acciones admin protegidas con `exigirRol(['admin'])`.

### C2 — Validaciones servidor (15%)
- [x] `Aplicacion/Utilidades/validacion.php` reutilizable.
- [x] Validación PHP en perfil/género/evento/canción e ítems comida/deporte.
- [x] Tipo **existente y activo** (comida/deporte).
- [x] Duplicado de nombre **dentro del mismo tipo sin distinguir mayúsculas** (comida/deporte: `existeNombre` con `LOWER` + `tipoId`).
- [x] Campos obligatorios, longitudes, URL válida cuando aplica.
- [x] Mensajes claros con `tipo` (`validacion`, `duplicado`, `servidor`, `conflicto`).

### C3 — Duplicados / integridad (15%)
- [x] `existeNombre`/`existeCorreo`/`existeEvento`/`existeDuplicada` con case-insensitive y exclusión del propio id (editar).
- [x] Para comida/deporte: unicidad semántica (nombre+tipo) vía validación PHP (no restricciones MySQL de negocio forzadas).
- [x] No permitir eliminar ítem con swipes: `tieneSwipes` → mensaje "Este elemento tiene swipes registrados. Puede desactivarlo en lugar de eliminarlo."
- [x] Gestión de correos duplicados documentada (plan con aprobación).

### C4 — Formatos (10%)
- [x] Fechas `dd/mm/aaaa`, horas `HH:mm` mediante `Publico/js/formato.js` (`formatearFecha`, `formatearHora`, `formatearFechaHora`, `formatearPorcentaje`).
- [x] Includes en `eventos.php`, `perfilubicaciones.php`, `perfilaccesos.php`.
- [x] `hora()` autocontenida en `chat.js`, `comunidades.js`, `grupos.js` (sin dependencia de `formato.js`) con espacio delimitador para harness.
- [x] `eventos.js`, `perfilubicaciones.js`, `perfilacceso.js` usan helpers.
- [x] `perfilado.js`, `swipe.js` usan `Number(x).toFixed(1)` donde corresponde.
- [x] Separadores de miles / decimales coherentes con interfaz.

### C5 — Auth / sesiones / permisos (10%)
- [x] `autenticacion.php`: `esPeticionApi`, `denegarAcceso` (401/403 JSON con `tipo`), `exigirSesion`, `exigirRol` JSON-aware.
- [x] Guards en operaciones admin (perfil, canciones, géneros, eventos, comidas, deportes).
- [x] `session_regenerate_id(true)` en login normal y biométrico.
- [x] Logout completo en `Publico/index.php` (vaciar `$_SESSION`, limpiar cookie, `session_destroy()`).
- [x] Router: cliente no accede a admin; admin no accede a cliente.
- [x] Semántica login genérica; cuenta inactiva solo tras credenciales válidas.

### C6 — Errores (20%)
- [x] `Configuracion/configuracion.php`: `display_errors='0'`, `log_errors='1'`, handlers globales (excepción/error/fatal). Para APIs devuelve JSON `{exito:false,tipo:'servidor',...}` con HTTP 500.
- [x] `Configuracion/basedatos.php`: `error_log` + `throw RuntimeException` (sin `die(getMessage())`).
- [x] Fugas de `getMessage()` saneadas (4 controladores + 3 modelos revisados).
- [x] JS: mensajes genéricos para error de red/servidor (eliminados strings crudos tipo `Error de conexión: ...` en login/registro/perfil/biometria donde aplicaba).
- [x] Helper `peticiones.js`: mapea 401→`autenticacion`, 403→`permiso`, 5xx/no-JSON→`servidor`, caída de red→`red`; `redirigirLogin()` evita doble redirección; `manejarSesion()` para polling (401 redirige sin repetir).
- [x] `rubrica.php` verifica: `display_errors='0'`, handlers, ausencia de `die(getMessage)`, `getMessage()` no vuelca a `"mensaje" =>`, `autenticacion.php` con `http_response_code`/`'tipo'`/`esPeticionApi`, guards y `session_regenerate_id(true)`.

## 6. Regresión
- [x] `php Pruebas/pruebas.php` → 55/55
- [x] `php Pruebas/rubrica.php` → 41/41
- [x] `node Pruebas/duplicados_harness.js` (via `docker run node:22-alpine`) → 13/13
- [x] `node --check` en JS tocados/nuevos → OK
- [x] `temas_calibracion.php` → cumple
- [x] Sintaxis PHP verificada (`php -l`) en archivos nuevos/modificados

## 7. Notas de entrega
- Helper `peticiones.js` y su aplicación forman un **cambio en commit separable** (solicitado).
- No se tocaron credenciales ni se migraron contraseñas.
- No se mutaron datos en Aiven (solo plan documentado).

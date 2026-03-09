# Changelog

## 0.27.59
- Revisión editorial: se corrige la ventana de apelación (24h) para usar la zona horaria de WordPress de forma consistente al guardar y validar `appeal_deadline_at`.
- Evita cierres anticipados o tardíos de apelación en sitios con desfase horario respecto a UTC.

## 0.27.58
- Navegación móvil: tab bar inferior actualizada para mostrar los **6 accesos** solicitados: Publicaciones, Ranking, Adopciones, Inicio, Subir y Perfil.
- Se ajusta el layout de la tab bar móvil (`flex: 1` por item, iconos/texto compactos) para evitar que desaparezcan tabs por espacio en pantallas pequeñas.
- Ranking y Adopciones quedan visibles a la vez; Adopciones no reemplaza Ranking.

## 0.27.57
- Navegación principal (tab bar inferior): se restaura el acceso visible a **Ranking** (`/catgame/leaderboard`) que había sido reemplazado por Adopciones.
- Adopciones se mantiene como sección adicional (accesible desde navegación superior y rutas propias) sin reemplazar Ranking.

## 0.27.56
- Nueva sección separada **Adopciones** con rutas públicas `/catgame/adoptions`, `/catgame/adoptions/new` y detalle `/catgame/adoptions/{id}`, aislada del feed/ranking/eventos del juego.
- Adopciones: formulario específico con campos obligatorios (foto, nombre, sexo, edad, ciudad, país, tipo, descripción y contacto) para publicar `En adopción` o `Hogar temporal`.
- Adopciones frontend: listado propio (orden reciente) y detalle con badge de tipo, datos completos y contacto; imagen grande optimizada en móvil con `max-height` + `object-fit: contain`.
- Admin: nuevo panel `Cat Game > Adopciones` para revisar publicaciones y aplicar acciones `Marcar resuelta` o `Eliminar` sin mezclar con Moderation/Revisión.
- DB: esquema `15` con nueva tabla `catgame_adoptions`.

## 0.27.55
- Perfil: nueva sección **Comentarios y sugerencias** con formulario para enviar comentario, sugerencia, error técnico o reporte de bug.
- Feedback backend: nuevo handler `catgame_submit_feedback` para guardar mensajes del usuario autenticado en tabla dedicada `catgame_feedback` con tipo, contenido, fecha, estado y origen.
- Admin: nuevo menú **Feedback** en Cat Game con listado de mensajes y acciones `Marcar revisado`, `Eliminar` y `Agradecer` (envía notificación al usuario).
- DB: versión de esquema `14` con nueva tabla `catgame_feedback`.

## 0.27.54
- Nueva página del juego **Acerca de nosotros** accesible en `/catgame/about` con contenido breve: qué es el juego, misión, visión, valores y futuro del proyecto.
- Inicio: el botón `ℹ️ Acerca de nosotros` ahora abre una vista real dentro del juego (no placeholder), manteniendo navegación simple con `← Volver al inicio`.
- Router/render: soporte de ruta pública `about` sin tocar lógica de eventos, ranking, publicaciones o moderación.

## 0.27.53
- Inicio: nueva card informativa **Cómo funciona el juego** con pasos breves mobile-first para usuarios nuevos, usando narrativa `La Arena` / `El Parque`.
- Inicio: nuevo resumen corto de moderación para contextualizar reglas sin reemplazar el popup de reglas completo.
- Inicio: nuevos botones `📜 Ver reglas completas` (abre el modal existente de reglas) y `ℹ️ Acerca de nosotros` (enlace preparado a `/catgame/about`).

## 0.27.52
- UX copy de modos de publicación unificado para usuario final: `event` ahora se muestra como **La Arena** y `free` como **El Parque**, sin cambios en lógica interna ni base de datos.
- Feed, cards, perfil y home: badges y etiquetas visibles actualizadas a `🏆 La Arena` y `🐾 El Parque`.
- Subir publicación: botones y ayudas renovados a `🏆 Entrar a La Arena` y `🐾 Publicar en El Parque` con narrativa clara de ambos espacios.
- Ranking y mensajes contextuales: lenguaje orientado a **La Arena** en la experiencia competitiva.

## 0.27.51
- Ranking / Ganadores anteriores: rediseño visual de cada evento histórico con cabecera dedicada (nombre + período) y podio móvil-first de jerarquía clara.
- Ranking / Ganadores anteriores: el 1° lugar ahora destaca en bloque principal, y 2°/3° se presentan en bloques secundarios compactos.
- Ranking / Ganadores anteriores: miniaturas más grandes, medallas más visibles y fallback `Sin título` en estilo secundario, manteniendo enlaces al detalle de publicación.

## 0.27.50
- Perfil / Mis publicaciones: se elimina duplicación visual de reacciones, dejando un único bloque reactivo (emoji+conteo) por publicación.
- Perfil / Mis publicaciones: se mantiene ubicación + total `Reacciones: X` y acciones existentes sin romper reacciones clickeables.
- Perfil / Mis publicaciones: se oculta el mensaje `No existe una moderación activa para esta publicación.` en esta vista para reducir ruido.

## 0.27.49
- Perfil / Mis publicaciones: rediseño de cards para orden visual consistente con Publicaciones (header compacto con `#ID + título` y acción `Eliminar`, foto, ubicación, total de reacciones y resumen por emoji+conteo).
- Perfil / Mis publicaciones: reducción de espaciados verticales y alineación compacta de elementos en la card.
- iPhone/Safari: prevención de auto-zoom en formularios del juego estableciendo `font-size: 16px` para `input`, `textarea` y `select` en móvil.

## 0.27.48
- Subir / iPhone (Safari): compatibilidad mejorada en render de etiquetas guardadas reemplazando `replaceAll()` por `replace(.../g)` en `escapeHtml` para evitar fallas en versiones iOS con soporte parcial.
- Subir / chips: se fuerza `-webkit-text-fill-color: currentColor` en botones de etiquetas para asegurar texto visible en Safari iOS.

## 0.27.47
- Subir / Mis etiquetas guardadas: corrección de chips sin texto visible en sugerencias guardadas (se fuerza color de texto y fallback de label en render).
- Subir / etiquetas: se mantiene la selección integrada al mismo input de tags, sin duplicados y sin cambiar fuente de datos personal del usuario.

## 0.27.46
- Subir: nueva sección desplegable **Mis etiquetas guardadas** (cerrada por defecto) que muestra solo etiquetas personales del usuario actual cuando existen.
- Subir / etiquetas: chips seleccionables reutilizables integrados al mismo input/payload de tags del formulario (compatible con tags manuales), sin duplicados.
- Subir / etiquetas: mejoras visuales del bloque desplegable y estado activo de chips seleccionados.

## 0.27.45
- Perfil: corrección de desfase horario en "Normas aceptadas" para usar timezone de WordPress al renderizar `catgame_terms_accepted_at`, evitando mostrar +3h por doble conversión.
- Perfil: formato de fecha/hora actualizado a `d/m/Y a las H:i` (sin segundos) para mayor claridad.

## 0.27.44
- Compartir perfil: `Mi perfil` ahora comparte la URL pública real (`/catgame/user/{username}`) en lugar de la vista privada de perfil.
- Compartir publicación destacada: ahora comparte el detalle exacto de la destacada (`/catgame/submission/{id}`) y no el feed general.
- Publicaciones (cards): nueva acción `Compartir` por publicación (evento/libre) apuntando al detalle exacto.
- Share unificado: para perfil/destacada/publicación se usa Web Share API cuando está disponible; fallback a copiado de enlace con toast `Enlace copiado`.

## 0.27.43
- Revisión admin: la bandeja ahora muestra solo publicaciones creadas en las últimas 24 horas para evitar acumulación histórica.
- Revisión admin: publicaciones con al menos un reporte dejan de aparecer en Revisión (quedan para flujo de Moderation).

## 0.27.42
- Admin: nuevo submenú **Revisión** separado de **Moderation**, con tabla de publicaciones (miniatura, ID, título, usuario, tipo, fecha, estado) y filtros por tipo (`Todas/Evento/Libre`) y estado (`Pendientes/Revisadas/Eliminadas/Apeladas`).
- Revisión editorial: nuevo flujo interno en `submissions` con estados `pending_review`, `reviewed`, `removed_review`, `appealed_review` y metadatos de decisión/motivo/revisor/ventana de apelación.
- Upload: cada publicación nueva entra automáticamente en `pending_review` sin alterar su visibilidad pública inicial.
- Revisión admin: acciones **Mantener** y **Eliminar publicación** (con motivo y detalle), notificación al usuario y ventana de apelación de 24h.
- Perfil usuario: nueva apelación de revisión (24h) para publicaciones eliminadas por revisión editorial, separada de apelaciones de Moderation.
- Revisión admin (apeladas): aceptar apelación restaura publicación; rechazar apelación mantiene eliminación y borra adjunto de medios.
- Limpieza 24h: rutina automática que purga adjuntos vencidos sin apelación en eliminaciones por revisión, conservando registro mínimo interno.
- DB: versión de esquema `13` con columnas e índices editoriales de revisión en `catgame_submissions`.

## 0.27.41
- Moderación admin (`Resueltos`): ajuste visual del cuadro **Editar acción** para mostrarse horizontal en una sola franja bajo la información del caso, con scroll horizontal cuando falta ancho.

## 0.27.40
- Moderación admin: nueva acción directa **Eliminar publicación** con modal de motivo (`Incumple normas` / `Imagen repetida en el evento` / `Otro`) y notificación automática al usuario afectado.
- Moderación `Resueltos`: redistribución del bloque `Editar acción` en una fila horizontal de ancho completo, más legible en móvil y escritorio.
- Panel técnico: renombres UX (`Revisar sanciones pendientes`, `Copiar informe técnico`, `Historial de revisiones automáticas`) y uso de acordeones para reducir ruido visual.

## 0.27.39
- Capitalización visual: nuevo helper reutilizable `format_first_capital()` (sin tocar BD), usado en render para ciudad/país/título/tags con primera letra en mayúscula y resto intacto.
- Ranking filtros/histórico: labels de país/ciudad en selects y títulos de ganadores históricos ahora se muestran capitalizados en frontend.
- Detalle alterno (`detail.php`): título/ubicación alineados a capitalización visual estándar.

## 0.27.38
- Detalle de publicación: imagen principal ahora respeta proporción y viewport (`max-height` con `object-fit: contain`) para mejor visual en móvil/escritorio.
- Detalle de publicación: se ocultan el mensaje de apelación sin moderación activa y el bloque de tamaño de imagen para una vista más limpia.

## 0.27.37
- Inicio / Últimas publicaciones: ahora usa feed cronológico mixto (`all`) con publicaciones de evento + libre (solo activas/no ocultas), orden `created_at DESC, id DESC`.
- Inicio / cards recientes: click en foto abre detalle reutilizando `/catgame/submission/{id}`, mantiene badges `🏆 Evento` / `🐾 Libre` y widget de reacciones.

## 0.27.36
- Perfil: se corrige apertura del modal de `Ver normas` en la sección de aceptación de normas/sanciones (ya no depende de la inicialización del formulario de Subir).

## 0.27.35
- Perfil: la aceptación de normas/sanciones pasa a ser requisito único y persistente (`catgame_terms_accepted`, `catgame_terms_accepted_at`) junto a ciudad/país para completar perfil.
- Subir: se elimina checkbox de aceptación; queda solo botón `Ver normas` y bloqueo con CTA a perfil cuando falta completar perfil + aceptar normas.
- Modal `Normas y sanciones`: contenido reordenado en secciones claras (permitido, no permitido, sanciones y apelaciones) con mejor spacing visual.
- Capitalización visual: ciudad, país, títulos y etiquetas se muestran con mayúscula inicial en frontend sin migrar datos históricos.

## 0.27.34
- Historial de ganadores: el listado ahora filtra estrictamente eventos de tipo `competitive` para evitar incluir eventos heredados sin tipo explícito.

## 0.27.33
- Ranking: nueva sección **Ganadores anteriores** debajo del ranking actual, mostrando historial de eventos competitivos finalizados con top 3 (🥇🥈🥉, imagen, título y usuario).
- Historial de ganadores: nuevo helper backend para listar snapshots persistidos de `event_winners` (solo eventos competitivos finalizados), ordenados por más reciente.
- UX vacío de históricos: mensaje `Aún no hay eventos finalizados con ganadores.` cuando no hay snapshots.

## 0.27.32
- Ranking: ahora usa desempate completo por `total_reactions DESC`, luego `first_reaction_at ASC` y finalmente `created_at ASC` (con `id ASC` como estabilidad).
- Ranking UX: cuando no hay evento activo competitivo muestra `No hay un evento competitivo activo en este momento.` y header con nombre/vigencia cuando sí existe.
- Persistencia base de ganadores: nueva tabla `event_winners` y finalización automática de eventos competitivos terminados para guardar top 3 (`event_id`, puestos 1/2/3, `finalized_at`).
- Admin eventos (detalle/preview): se muestra sección `Ganadores guardados` cuando ya existe finalización persistida.

## 0.27.31
- Gestor de eventos: nuevo campo **Tipo de evento** (`competitive` / `thematic`) en creación/edición, persistido en BD con fallback compatible para eventos existentes (`competitive`).
- Admin preview: ahora muestra etiqueta de tipo (**Competitivo**/**Temático**) y, si es temático, mensaje explícito de que no compite en ranking.
- Juego / Subir: si el evento activo es **competitivo** se mantienen ambos modos (evento/libre); si es **temático** se oculta participar en evento y queda solo modo libre con mensaje `Tema actual: {nombre}`.
- Juego / Ranking: usa únicamente el evento activo **competitivo** (si el activo es temático, no se cargan publicaciones competitivas).
- Popup Reglas del evento: siempre muestra nombre+vigencia y, cuando el evento es temático, muestra aviso de no-competencia en ranking.

## 0.27.30
- Upload UX: nueva selección explícita de destino con 2 botones grandes (`🏆 Participar en el evento` / `🐾 Publicar en modo libre`) antes del selector de foto.
- Con evento activo: se exige elegir modo antes de publicar; sin evento activo: se preselecciona automáticamente modo libre y se oculta la opción de evento.
- Upload backend: nuevo campo `publish_mode` para resolver `event_id` al guardar (`event` => id evento activo, `free` => `event_id=0`) con fallback seguro a libre si el evento deja de estar activo al enviar.

## 0.27.29
- Feed/Publicaciones (regla de alcance): se elimina el filtrado por etiquetas residual en la query del feed filtrado (`all/event/free`) para mantener el comportamiento exclusivamente por `event_id`.
- Se conserva orden único `created_at DESC, id DESC` y paginación `Cargar más` por filtro sin prioridad especial a publicaciones de evento.

## 0.27.28
- Feed filtro `Evento`: se corrige para incluir cualquier publicación con `event_id IS NOT NULL AND event_id != 0` (no solo del evento activo), manteniendo orden `created_at DESC, id DESC`.
- Estado vacío `Evento`: se unifica a `No hay publicaciones de evento disponibles.` para alinearse con la lógica del filtro.

## 0.27.27
- Feed Publicaciones: nuevo filtro visual por pestañas `Todo`, `🏆 Evento`, `🐾 Libre` sin cambiar estructura de cards ni reacciones.
- Feed backend/paginación: el endpoint `catgame_feed_more` ahora acepta `filter` (`all/event/free`) y `Cargar más` respeta el filtro activo.
- Estado vacío contextual por filtro: evento sin activo (`No hay evento activo en este momento.`), evento sin items (`No hay publicaciones de evento disponibles.`) y libre sin items (`Aún no hay publicaciones en modo libre.`).

## 0.27.26
- Upload: ahora permite publicar aunque no exista evento activo; en ese caso la submission se guarda en modo libre con `event_id=0` (sin bloquear el envío).
- Feed Publicaciones: ahora muestra tanto publicaciones del evento activo como publicaciones en modo libre, incluyendo paginación "Cargar más".
- Upload UI: se agrega mensaje contextual de destino de publicación ("Evento activo — {nombre}" o "Modo libre (no competitivo)").

## 0.27.25
- Feed UX: se agrega badge visual por tipo de publicación sobre la foto en cada card del feed: `🏆 Evento` cuando `event_id` existe y `🐾 Libre` cuando `event_id` está vacío.
- Estilos del badge: posición absoluta esquina superior izquierda y variantes de color (`#f5b942`/`#6ec7a8`) sin alterar reacciones, acciones de card ni paginación.

## 0.27.24
- Ranking UX: filtros de ubicación ahora usan valores guiados con `select` para País/Ciudad (catálogo real del evento activo) en lugar de texto libre.
- Ranking robustez: si `country/city` de querystring no existen en catálogo del evento, se normalizan a vacío para evitar filtros inválidos por typo/manual URL.

## 0.27.23
- Auth rate limit UX/safety: las validaciones de campos obligatorios en login/registro/recuperación/reset se ejecutan antes de incrementar buckets para evitar bloqueos falsos por envíos vacíos.
- Se mantiene sin cambios la protección de intentos reales (`rate_limited`) y la lógica de buckets por IP+acción e identificador+acción.

## 0.27.22
- Auth rate limit (DX/compat): se agregan filtros `catgame_auth_rate_limit_max_attempts` y `catgame_auth_rate_limit_window_seconds` para ajustar límites sin editar core del plugin.
- Auth rate limit (infra): nuevo filtro `catgame_auth_rate_limit_ip` para resolver IP en entornos con proxy/CDN; por defecto se mantiene `REMOTE_ADDR` validada.

## 0.27.21
- Auth rate limit hardening: se evita bypass por rotación de identificador aplicando bucket por IP+acción y bucket adicional por identificador+acción.
- Seguridad de origen IP: el cálculo del rate limit usa `REMOTE_ADDR` validada para evitar depender de headers spoofeables (`X-Forwarded-For`/`Client-IP`).

## 0.27.20
- Seguridad auth: se agrega rate limit en endpoints de login, registro, recuperación y reset de contraseña para mitigar abuso por intentos masivos.
- UX auth: nuevos mensajes de error `rate_limited` en vistas de acceso para feedback consistente cuando se supera el límite temporal.

## 0.27.19
- Event rules popup/game: ahora refleja exactamente el evento activo normalizado (`rules.mode` + `items`), incluyendo prioridad absoluta de `mode: none` sobre reglas legacy.
- Si el evento no usa reglas (`mode=none`): el popup muestra solo **Reglas generales (resumen)** y no renderiza items del evento.
- Si el evento usa reglas: el popup muestra items del evento + bloque corto de reglas generales.
- Admin Gestor (previsualización): se alinea 1:1 con el texto/estructura que ve el juego para `mode=none` y para eventos con reglas.
- UX cache/session: el popup usa revisión del evento (`data-event-revision`) en la key de sessionStorage para invalidar estado visto cuando cambia el evento activo/rules_json.

## 0.27.18
- Upload UX/accesibilidad: se agrega microcopy bajo “Acepto los términos” para reforzar que la aceptación confirma lectura de normas y sanciones.
- Upload modal UX: al abrir “Normas y sanciones”, el foco se mueve al botón de acción “Entendido”.

## 0.27.17
- Upload UX/copy: el modal de términos pasa a **"Normas y sanciones"** con contenido más claro en bullets (prohibiciones, severidades y consecuencias).
- Upload UX: el modal se abre desde **Ver normas**, desde el link junto a "Acepto los términos" y también al hacer click en el checkbox obligatorio.
- Copy enforcement: se explicitan puntos y bloqueos (>=3 => 3 días, >=9 => 7 días), hold grave 24h + apelación 24h y desenlace de perma-ban si no apela o se rechaza.

## 0.27.16
- Tags UX: se eliminan sugerencias/globales predefinidas; Upload sugiere únicamente etiquetas propias del usuario logueado.
- Tags backend: nuevo storage de sugerencias personales en `user_meta` `catgame_user_tags` (array normalizado), con merge automático al subir publicación.
- Normalización tags en upload: trim/lower/slug, deduplicación, máximo 20 etiquetas por publicación y máximo 20 caracteres por etiqueta.
- Perfil: sección **Mis etiquetas** y eliminación de etiqueta sólo afecta sugerencias personales (no borra etiquetas históricas en publicaciones existentes).

## 0.27.15
- Moderation Admin UX: al cambiar entre pestañas **Pendientes/Resueltos** se conservan los filtros de historial corto (`grave_history_source`, `grave_history_status`) para mantener el contexto de diagnóstico.
- Consistencia de navegación: los enlaces de pestaña ahora usan `add_query_arg` con parámetros normalizados activos.

## 0.27.14
- Moderation Admin UX: el botón **Ejecutar enforcement ahora** conserva también el filtro principal de reportes (`status=pending|resolved`) para no cambiar de pestaña tras la ejecución.
- Redirect consistency: `catgame_run_grave_enforcement` valida/normaliza `status` y lo incluye en el redirect junto a los filtros de historial corto.

## 0.27.13
- Moderation Admin hardening: se valida/normaliza también en UI admin los filtros `grave_history_source` y `grave_history_status` (whitelist) para evitar estados inválidos por querystring manual.
- Consistencia de diagnóstico: ante valores no permitidos en URL, los filtros vuelven a `all` de forma predecible.

## 0.27.12
- Moderation Admin UX: al ejecutar enforcement manual se conservan los filtros activos del historial corto (`grave_history_source`, `grave_history_status`) para evitar perder el contexto de diagnóstico.
- Seguridad/robustez: el endpoint `catgame_run_grave_enforcement` valida y normaliza ambos filtros antes de reutilizarlos en el redirect (`all/runtime/manual/cli` y `all/ok/error`).

## 0.27.11
- Moderation Admin (operación): se agrega filtro de historial corto de enforcement por `origen` (`runtime/manual/cli`) y `estado` (`ok/error`) para diagnóstico más rápido.
- Moderation Admin (soporte): nuevo botón **Copiar diagnóstico (JSON)** que copia el historial filtrado para pegar en tickets/incidencias.
- Telemetry UX: el historial muestra mensaje explícito cuando el filtro no devuelve resultados.

## 0.27.10
- Moderación/Operación: se agrega historial corto de corridas de enforcement de casos graves (ring buffer de 20 runs) con `ran_at`, `processed`, `source`, `duration_ms` y `status`.
- Admin Moderation: el panel de enforcement ahora muestra tabla de historial corto para diagnóstico rápido sin revisar logs del servidor.
- Enforcement internals: `enforce_grave_case_deadlines` acepta `source` (`runtime|manual|cli`) y registra métricas mínimas por corrida.

## 0.27.9
- Moderación Admin: nuevo panel en `WP Admin > Moderation` para ejecutar manualmente el enforcement de casos graves y visualizar el último run (`ran_at`, `processed`).
- Enforcement: `enforce_grave_case_deadlines` ahora devuelve cantidad procesada y persiste telemetry mínima en opción (`catgame_grave_enforcement_last_run`).
- Cron/Operación: nuevo endpoint admin seguro `catgame_run_grave_enforcement` (nonce + capability) para forzar corrida manual cuando WP-Cron no corre por bajo tráfico.

## 0.27.8
- Operación/Enforcement: se agenda cron hourly `catgame_enforce_grave_cases_event` para ejecutar automáticamente `enforce_grave_case_deadlines` sin depender de login/acciones de usuario.
- Lifecycle plugin: al desactivar el plugin se desagenda el cron de casos graves para evitar eventos huérfanos.
- DX/Soporte: nuevo comando WP-CLI `wp catgame bans-rebuild [--user_id=<id>]` para recalcular bans desde `infractions` + `grave_cases` (auditoría/recovery operacional).

## 0.27.7
- Moderación (nuevo motor): se agregan tablas `catgame_infractions`, `catgame_bans`, `catgame_perma_bans` y `catgame_grave_cases` para modelar puntos, bloqueos y casos graves con lifecycle.
- Puntos y escalamiento: leve=+1, moderada=+3, grave=+9 (expiran a 1 año); umbrales automáticos de ban de subida (>=3 => 3 días, >=9 => 7 días, preservando el mayor).
- Grave/hard hold: al sancionar grave se aplica bloqueo inmediato de subir/reaccionar por 24h y se abre caso grave; si vence sin apelación, se ejecuta perma-ban + borrado fuerte de datos del juego.
- Apelaciones: ventana dinámica por severidad (72h leve/moderada, 24h grave); en grave pending se mantiene hard hold extendido hasta veredicto.
- Veredicto apelaciones: aceptar revierte infracción por `submission_id`, restaura publicación y recalcula bans; rechazar grave dispara perma-ban.
- Enforcement: login bloqueado para perma-ban, upload/reacciones bloqueadas por bans/hard hold, y bloqueo de re-registro por hash de email en `catgame_perma_bans`.

## 0.27.6
- Apelaciones: nueva tabla `catgame_appeals` (1 apelación por publicación) con estados `pending|accepted|rejected`, trazabilidad de decisión admin y nota opcional.
- Regla 72h: una moderación es apelable solo si existe acción actual (`moderation_actions.is_current=1`), no venció la ventana de 72 horas y no hay apelación previa.
- UX usuario: botón **Apelar** + modal en frontend (AJAX `catgame_submit_appeal`, nonce, mensaje máx 500, toast de éxito y estado "Apelación pendiente").
- Anti-abuso: rate limit de apelaciones a 3 envíos por usuario cada 24h con respuesta `429` y mensaje explícito.
- Admin Moderation: sección de **Apelaciones pendientes** con acciones Aceptar/Rechazar.
- Al aceptar: se restaura publicación, se revierte suspensión/strike asociado al caso y se agrega acción `restore` en historial de moderación.
- Notificaciones campana: decisión de apelación notifica al dueño con deduplicación por `event_key` (`appeal:{appeal_id}:{status}`).

## 0.27.5
- Moderación Admin: se agrega historial de acciones en DB (`catgame_moderation_actions`) con encadenado por `prev_action_id` y marca `is_current` para soportar edición de la decisión actual.
- Moderación Admin (resueltos): nuevo formulario **Editar acción** (acción, gravedad, motivo y detalle) con guardado idempotente; si no hay cambios se informa "Sin cambios".
- Moderación Admin: se bloquea la edición cuando la acción previa es `delete_account` por tratarse de una decisión irreversible.
- Moderación UX: nueva guía rápida colapsable de gravedad/acción en la pantalla de moderación y persistencia de estado expandido vía `localStorage`.
- Notificaciones: al editar una acción se envía aviso al dueño de la publicación con resumen antes/ahora y `event_key` deduplicable `moderation_update:{submission_id}:{new_action_id}`.

## 0.27.4
- Moderación: notificaciones automáticas al dueño de la publicación en revisión admin (reporte revisado/restaurado, publicación eliminada, sanción aplicada y suspensión de cuenta cuando corresponde).
- Moderación: mensajes user-facing enriquecidos con título de publicación (fallback `Publicación #ID`), motivo y gravedad.
- Notificaciones: se agrega deduplicación por `event_key` para evitar duplicados por refresh/reintentos en acciones de moderación.
- Moderación: log mínimo en modo debug para deduplicación y acciones ya resueltas.

## 0.27.3
- Perfil: se agrega campanita de notificaciones con badge de no leídas y modal con listado user-facing.
- Notificaciones (MVP): se migran a `user_meta` (`catgame_notifications`) con helpers para agregar, listar y marcar todas como leídas.
- AJAX: nuevos endpoints `catgame_get_notifications` y `catgame_mark_notifications_read` para UI de campana.
- Flujo reportes: al enviar reporte se crea notificación "Reporte recibido" y al resolver moderación se notifica "Reporte resuelto".

## 0.27.2
- Upload: se expone `upload_restriction` en payload (`upload_banned`, `upload_banned_until`) reutilizando helpers de bans existentes.
- Upload UI: nueva tarjeta móvil-first "Subida restringida" con fecha límite y mensaje "Puedes seguir reaccionando" para evitar confusión cuando hay ban activo.
- Upload no-regresión: si hay restricción activa, no se muestra el formulario de publicación en esa vista.

## 0.27.1
- Perfil: nueva tarjeta "Estado de tu cuenta" con strikes activos de autor/reportante, umbral 3 y texto de expiración en 1 año.
- Perfil: muestra estado de bloqueo de subida y fecha límite cuando aplica, manteniendo que durante la restricción se puede reaccionar.
- Backend perfil: se agrega payload de estado de cuenta (`strikes` y `bans`) sin exponer datos sensibles.

## 0.27.0
- Moderación/Strikes: `catgame_strikes` se endurece con `kind/severity` tipados, `reason_code` ampliado, `admin_user_id` e índice compuesto (`user_id`,`expires_at`).
- Strikes y bans de upload: al resolver moderación se aplica strike a autor/reportante y bloqueo temporal de subida por 7 días al acumular 3 strikes activos; severidad grave aplica restricción de 365 días.
- Enforce upload-only: el bloqueo se aplica solo al endpoint de subida; las reacciones permanecen permitidas.
- Upload UX: al intentar subir con bloqueo activo se muestra mensaje claro con fecha límite y se evita procesar la carga.

## 0.26.19
- Reportes UX (red/proxy): en el submit se valida `response.ok` antes de procesar éxito; si llega HTTP `403` se muestra mensaje explícito de bloqueo de red/proxy.
- Reportes UX (conectividad): si el envío falla por `Failed to fetch` / `NetworkError`, se muestra mensaje claro de problema de conexión en la red actual.
- Seguridad UX de estado: la publicación solo se remueve y el modal solo se cierra en éxito real (`response.ok` + `payload.success`).

## 0.26.18
- Reportes AJAX: envío del modal mediante `admin-ajax.php` con `application/x-www-form-urlencoded`, `credentials: same-origin` y payload explícito (`action`, `nonce`, `submission_id`, `reason`, `detail`).
- Backend reportes: nuevo hook `wp_ajax_catgame_report_submission` con validación `check_ajax_referer('catgame_nonce','nonce')` y respuestas JSON consistentes.
- Cards de Publicaciones/Ranking: CTA contextual movido a cabecera superior derecha fuera de la foto con estilo mini (`Eliminar` para dueño, `Reportar` para terceros logueados).

## 0.26.17
- UI cards (Publicaciones/Ranking): acción pequeña en cabecera (arriba-derecha) fuera de la foto con lógica exclusiva por usuario: dueño => **Eliminar**, tercero logueado => **Reportar**.
- Reportes UX: radios del modal de reporte en lista vertical para mejor legibilidad móvil.
- Reportes fix: envío del formulario agrega `action=catgame_report_submission` y nonce en `FormData`, corrigiendo el fallo de envío.

## 0.26.16
- Moderación/Reportes: nuevo sistema de reportes con ocultamiento inmediato (`is_hidden=1`) al primer reporte y registro en tabla `catgame_reports`.
- Moderación Admin: pantalla de reportes pendientes/resueltos con acciones Restaurar, Eliminar (leve/moderado/grave) y Reporte falso.
- Sanciones: nueva tabla `catgame_strikes` (expiran en 1 año), bloqueo de participación por strike grave o por acumulación de strikes activos, y sanción por reporte falso.
- Notificaciones: tabla `catgame_notifications` y visualización en Perfil para decisiones de moderación.
- Frontend: botón/modal "Reportar" (solo logueados, no autor) en cards/detalle/perfil público/ranking; al reportar, se oculta la publicación en la vista actual.

## 0.26.15
- Perfil público: nueva ruta `/catgame/user/{username}` (read-only) con header de `@usuario` y ubicación (meta de perfil con fallback a última publicación).
- Perfil público: secciones **Evento activo** (reacciones habilitadas solo para visitantes logueados) y **Recientes (30 días)** para eventos cerrados (solo lectura con mensaje "Evento finalizado").
- Navegación: desde cards de Publicaciones y Ranking, el `@usuario` ahora enlaza al perfil público.
- Reacciones: `render_widget` ahora soporta modo `readonly` con motivo configurable para deshabilitar interacción sin perder conteos/estado visual.

## 0.26.14
- Upload UX (iOS Safari): se simplifica a un solo CTA **Seleccionar foto** y se ocultan por completo **Subir archivo** y **Tomar foto** para evitar duplicación visual de opciones.
- Upload UX (Android/desktop): se mantienen dos CTAs explícitos **Subir archivo** + **Tomar foto**.
- Upload inputs: se usa `inputUniversal` en iOS (`accept="image/*"`, sin `capture`), `inputUpload` sin `capture` y `inputCamera` con `capture="environment"` fuera de iOS.
- Flujo unificado: todos los inputs continúan en el mismo handler de selección/preview/compresión/envío.

## 0.26.13
- Upload UX (iOS): nueva detección robusta `isIOS()` (UA + platform + touch heuristic) para mostrar solo **Elegir de Fotos** + **Tomar foto** y ocultar **Subir archivo**.
- Upload UX (Android/otros): se mantiene **Subir archivo** + **Tomar foto** como acciones separadas.
- Upload inputs: se agrega `inputPhotos` (`accept="image/*"`, sin `capture`) para iOS, se mantiene `inputUpload` sin `capture` y `inputCamera` con `capture="environment"`.
- Upload flujo: `inputPhotos`, `inputUpload` e `inputCamera` convergen al mismo handler de selección/preview/compresión/envío.

## 0.26.12
- Upload (iOS/Android): se ajustan inputs separados para acciones explícitas: **Subir archivo** (sin `capture`, `accept=".jpg,.jpeg,.png,.webp"`) y **Tomar foto** (`accept="image/*"` + `capture="environment"`).
- Upload UX: ambos CTAs quedan con estilo activo consistente en morado fuerte (sin apariencia deshabilitada) y estado de presión visual (`:active`).
- Compatibilidad: se mantiene convergencia de ambos pickers al mismo handler de preview/compresión/envío, sin cambios en el backend de `cat_image`.

## 0.26.11
- Upload UX: reemplaza selector único por dos CTAs explícitos: **Subir archivo** y **Tomar foto** (con `capture="environment"` para cámara en móviles).
- Upload UX: botón principal **Enviar** pasa a color morado para destacar el CTA de envío.
- Upload UX: se oculta el texto de estado/tamaño de compresión en pantalla de subida para una interfaz más limpia.
- Validación título: mantiene campo obligatorio y agrega mensaje nativo personalizado "El título es obligatorio." cuando falta completar.

## 0.26.10
- Perfil/Ubicación: se centraliza lectura de ubicación por usuario en helpers (`get_user_default_location` / `has_user_default_location`) para evitar volver a exigir ciudad/país tras re-login cuando ya existe en `user_meta`.
- Perfil: guardado valida ciudad/país obligatorios; ante error mantiene inputs ingresados y muestra mensaje claro sin sobrescribir metadatos con vacío.
- Subir: mantiene uso de ubicación predeterminada desde `user_meta` y guarda snapshot en submissions con esos valores.

## 0.26.9
- Reacciones (UX visual): el indicador de selección vuelve a 2 huellitas azules rellenas, pequeñas y fuera del chip (arriba/derecha), eliminando la patita rosada grande sin cambiar tamaño/layout de la pill.

## 0.26.8
- Reacciones (UX visual): se corrige la "patita" del seleccionado para que aparezca como detalle externo pequeño (arriba-derecha) sin cubrir emoji/conteo ni deformar el chip.

## 0.26.7
- Reacciones (UX visual): se refuerza visibilidad del contorno "patita" seleccionado en móvil (outline SVG más grande/contrastado, trazo más grueso y sombra más notoria) sin cambiar tamaño de la pill.

## 0.26.6
- Reacciones (UX visual): se refuerza el resaltado "patita" del seleccionado con contorno de mayor contraste (2px), sombra suave y pseudo-elemento tipo outline para mejor visibilidad en móvil, manteniendo tamaño compacto.

## 0.26.5
- Reacciones (UX): long-press robusto en móvil con tooltip global fijo (siempre visible sobre la UI) y voto al soltar, manteniendo tap rápido para votar/cambiar.
- Reacciones (UX): pills compactadas adicionalmente (gap y tamaño menores) para mejorar densidad visual en Ranking/Feed/Detalle sin scroll horizontal.

## 0.26.4
- Reacciones (UX): barra compacta sin scroll horizontal en cards (Ranking/Publicaciones/Detalle), con wrap en 2 filas cuando hace falta.
- Reacciones (UX): se refuerza visibilidad de selección tipo patita y se mejora tooltip de long-press (>=350ms) manteniendo tap rápido para votar.
- Reacciones (UX): emoji flotante al votar/cambiar ahora usa posicionamiento fijo para mantenerse visible incluso con scroll.

## 0.26.3
- Ranking (mobile-first): se reestructura cada card para mostrar arriba `#puesto + título + autor`, luego una imagen grande a ancho completo y debajo la metadata en líneas separadas (ubicación y reacciones).
- Ranking: el botón "Eliminar" de publicación propia pasa a estilo compacto color sandía y se posiciona en la esquina superior derecha de la card.

## 0.26.2
- Feed (JS): se corrige el encadenado de IIFEs en `app.js` (faltaba `;` de separación), evitando error de ejecución que podía impedir inicializar el módulo de `Cargar más`.

## 0.26.1
- Feed: se corrige `Cargar más` para cargar bloques incrementales de publicaciones del evento activo y ocultar el botón al llegar al final real (`has_more=false`).
- Feed: se elimina el filtro por etiqueta en la pantalla de Publicaciones para mostrar todas las publicaciones del evento activo en una sola lista paginada.
- Ranking (mobile-first): se aumenta de forma perceptible el tamaño del contenedor de miniatura en cards para mejorar legibilidad visual en móviles.

## 0.26.0
- Ranking: miniaturas más grandes en cards (mobile-first) con contenedor dedicado y `object-fit: cover`, manteniendo layout de badge y metadatos.
- Reacciones: resaltado tipo “patita” aplicado solo a la reacción seleccionada del usuario, persistente al cambiar; se refuerza anti-selección táctil iOS en contenedor/botones.
- Publicaciones: nuevo flujo eficiente de “Cargar más” con `offset/per_page` (default 20, máximo 50), append incremental en frontend y mensaje final cuando no hay más items.

## 0.25.9
- Admin Eventos UX: orden vertical optimizado (Creación/edición → Detalle → Listado → Calendario) y mejoras visuales en CTA del panel.
- Admin Eventos reglas: nuevo modo mixto con sección opcional de reglas repetibles (Título, Tipo, Valor condicional, Descripción), soporte para evento sin reglas y edición completa al reabrir.
- Admin Eventos preview/acciones: nueva previsualización (estado, fechas, reglas formateadas, modo Competitivo/Temático) y botón Duplicar evento desde listado (copia reglas, deja inactivo y abre edición de la copia).

## 0.25.8
- Admin eventos: se corrige modo crear real en Gestor de eventos (`mode=create` / `event_id=0`) para evitar autoselección del primer evento y permitir INSERT correcto al crear.
- Admin eventos: se mantiene modo editar desde listado con carga de datos y UPDATE del evento seleccionado.
- UX admin: botones del formulario más claros ("Crear evento"/"Actualizar evento" + "Nuevo evento" siempre visible) y CTA de "Marcar como evento activo" más visible en detalle.

## 0.25.7
- Reacciones (DX/i18n-ready): se centralizan mensajes de toast del módulo de reacciones en un objeto único (`loginRequired`, `saveError`, `rateLimited`) manteniendo el comportamiento actual.

## 0.25.6
- Reacciones (UX): cuando el backend devuelve `retry_after` por rate limit, el toast ahora indica tiempo de espera explícito (ej: "Intenta nuevamente en Xs").

## 0.25.5
- Upload (performance): se revoca explícitamente el `ObjectURL` del preview al cambiar/limpiar archivo para evitar retención innecesaria de memoria en selecciones repetidas.

## 0.25.4
- Upload (DX/performance): limpieza de código JS sin uso en compresión de imagen (variables huérfanas y helper vacío), manteniendo intacto el comportamiento del flujo de subida.

## 0.25.3
- Seguridad reacciones: se agrega rate limit backend de 20 reacciones por usuario por minuto en `add_or_update_reaction`, con respuesta `429` y `retry_after` cuando se excede el límite.
- UX reacciones: frontend ahora muestra el mensaje devuelto por backend (incluyendo límite alcanzado) en lugar de un error genérico.

## 0.25.2
- Documentación: `README.md` se alinea con el estado actual del plugin (reacciones comunitarias, ubicación obligatoria desde Perfil y flujo vigente de subida).
- Documentación: se corrigen descripciones heredadas de estrellas/ciudad-país manual en upload para evitar confusión operativa.

## 0.25.1
- Perfil: se mueve "Cerrar sesión" al extremo superior derecho (botón compacto con ícono+texto), se re-agregan Ciudad/País persistentes en user meta y se exige completar ubicación para poder subir.
- Auth/flujo: login/registro exitoso redirigen a Perfil con aviso de completar ubicación cuando falta; Subir queda bloqueado con CTA a Perfil y el envío también valida ubicación desde Perfil.
- Subir: se eliminan inputs de ciudad/país; ahora muestra "Ubicación: ciudad, país" del perfil, se mantiene título obligatorio, nuevo picker de archivo estilizado, preview visible y sin mostrar tamaños de imagen.
- Subir: checkbox actualizado a "Acepto los términos" con modal "Reglas del juego" accesible desde la pantalla.
- Inicio/Tab bar: se reordena Home (hero + cómo funciona + top 3 + últimas), se elimina evento activo/CTA de subir en Home y se ajusta distribución de la barra inferior para balance móvil con énfasis correcto en Inicio activo.

## 0.25.0
- Reacciones UX: tap/click rápido mantiene voto inmediato; long-press (~450ms) muestra tooltip con nombre, aplica micro-escala y ahora vota recién al soltar.
- Reacciones touch: cancelación de voto en long-press si hay movimiento/fuera de objetivo o `pointercancel`; se usan Pointer Events con fallback a `click`.
- Reacciones visuales: se mantiene vista solo emoji + contador por defecto y se agrega animación ligera de emoji flotante al guardar voto exitoso.
- iOS: mejoras anti-selección/callout/highlight en pills (`user-select`, `-webkit-user-select`, `-webkit-touch-callout`, `touch-action`, `-webkit-tap-highlight-color`).

## 0.24.9
- Fix reacciones (cambio de voto): `add_or_update` ya no intenta actualizar la columna inexistente `updated_at` en la tabla de reacciones, por lo que volver a reaccionar ahora sí cambia correctamente el tipo guardado.
- Backend reacciones: se agrega manejo explícito de errores en `UPDATE/INSERT` para devolver 500 si la operación falla en base de datos.

## 0.24.8
- Fix UI reacciones: se declara correctamente el parámetro `deleted` en el bloque de toasts para evitar un `ReferenceError` que interrumpía `app.js` y bloqueaba la interacción de reacciones.
- Reacciones: vuelve a marcarse la reacción seleccionada al tocar/cambiar porque el script ya no se corta antes de inicializar los widgets.

## 0.24.7
- Fix urgente reacciones: se restaura interacción por tap/click con listener simple de `click` para votar/cambiar reacción inmediatamente.
- Reacciones: actualización optimista + rollback mantienen conteos y selección al votar/cambiar, sin bloquear cuando ya existe reacción previa.
- Reacciones (no logueado): se mantiene modo solo lectura con aviso para iniciar sesión.

## 0.24.6
- Reacciones UX: long-press ajustado a ~400ms con tooltip visible y voto al soltar.
- Reacciones feedback: se asegura emoji flotante ascendente con fade-out en el botón seleccionado (solo emoji).
- Reacciones UI: se mantienen 5 reacciones, una sola fila, emoji grande, contador visible y estado activo resaltado.

## 0.24.5
- Reacciones UX: long-press mantiene tooltip con nombre y ahora sí envía voto al soltar (alineado al comportamiento solicitado).
- Reacciones UX: se corrige bloqueo de interacción removiendo la prevención extra en `pointerdown` que impedía reaccionar en algunos dispositivos.
- Reacciones: tap/click rápido sigue permitiendo votar y cambiar reacción con actualización optimista, conteos y resaltado.

## 0.24.4
- Reacciones UX: tap rápido ahora permite cambiar reacción siempre (sin bloqueo por reacción previa), manteniendo conteos y resaltado seleccionado.
- Reacciones mobile: long-press (~450ms) muestra tooltip de nombre y al soltar no vota; tap/click rápido sí vota/cambia.
- Reacciones feedback: animación de emoji flotante al votar/cambiar (solo emoji), sin activarse por long-press.
- API reacciones: respuesta de `add_or_update` ahora incluye `old_type` y `new_type` además de conteos actualizados.

## 0.24.3
- Reacciones: payload de publicaciones ahora incluye `reaction_counts` (5 keys fijas) y `my_reaction` en feed/ranking/inicio/perfil/detalle.
- Reacciones UI: chips renderizan solo emoji + contador (sin nombre visible), con marca persistente de la reacción del usuario.
- Reacciones UX: tap rápido vota inmediato con actualización optimista; long-press (~400ms) muestra tooltip con nombre y vota al soltar.
- Reacciones mobile: cancelación por movimiento (>10px) y mantenimiento de anti-selección iOS.

## 0.24.2
- Reacciones UI: se vuelve al formato visual solo emoji + contador (sin nombre visible permanente en botones).
- Reacciones UX: nombre de reacción visible únicamente en long-press (~400ms) mediante tooltip; tap/click rápido vota inmediato.
- Reacciones mobile: cancelación de long-press por movimiento (>10px) y mejoras anti-selección iOS (`user-select`, `-webkit-touch-callout`, `touch-action`).

## 0.24.1
- Ranking: se elimina por completo el filtro por etiquetas en UI y en el flujo de query params del frontend.
- Reacciones: para usuarios no logueados se muestran en modo solo lectura (contadores visibles) y, al intentar interactuar, se muestra aviso "Inicia sesión para reaccionar".
- Publicaciones: nuevo borrado definitivo para dueño (`Eliminar mi publicación`) en Perfil, Ranking y vista de detalle, con limpieza de votos/reacciones/reportes asociados y adjunto de imagen.
- UX: todas las confirmaciones de eliminar (etiqueta/publicación) pasan a modal propio del plugin, evitando confirmaciones nativas del navegador.

## 0.24.0
- Upload: título ahora obligatorio (trim, mínimo 2, máximo 40), con preservación de campos en validaciones fallidas y mensaje de error visible.
- Upload: el título también se guarda en post meta del attachment como `catgv_title`.
- UI: en Inicio/Publicaciones/Ranking/Perfil se prioriza título de publicación (fallback "Sin título") y se evita mostrar "Publicación #ID" como título principal.
- Perfil: "Publicación destacada" ahora se calcula solo con publicaciones del evento activo, por mayor total de reacciones y desempate por `first_reaction_at`.
- Perfil/Mis publicaciones: no se muestran estrellas; si una publicación no tiene reacciones se muestra "Sin reacciones".

## 0.23.9
- UX: se elimina el flujo de detalle de publicación; se quita el botón "Ver detalle" y la ruta `/catgame/submission/{id}` ahora redirige al feed.
- Reacciones: long press ajustado a ~400ms con escala `1.35` + tooltip y envío al soltar; tap rápido mantiene envío inmediato.
- Reacciones: nuevo feedback al votar con emoji flotante (`catgvFloat`) y vibración `40ms` solo en móviles compatibles al confirmar voto.
- UI reacciones: una sola fila de reacciones, emoji ~22px, contador visible y estado activo con fondo pastel resaltado.

## 0.23.8
- Ranking: orden actualizado para usar reacciones (`total_reactions DESC`, `first_reaction_at ASC`) en vez de estrellas/votos.
- Inicio: Top 3 ahora refleja el ranking por reacciones.
- Perfil: estadísticas y destacados migrados a métricas de reacciones; "Mis publicaciones" muestra total de reacciones por item.
- Backend: consultas agregadas con `LEFT JOIN` sobre agregados de reacciones para mantener eficiencia y compatibilidad.

## 0.23.7
- Reacciones UI: se elimina el bloque pequeño inferior de conteos duplicados y se mantiene solo el bloque grande interactivo en cards/detalle.
- Reacciones frontend: limpieza JS para no renderizar/actualizar el resumen mini de conteos duplicado.
- CSS: se elimina estilo no usado de la fila mini de conteos (`.cg-reaction-counts`).

## 0.23.6
- Reacciones UI: emojis y conteos en chips más grandes para mejorar legibilidad en móvil.
- Reacciones UX: soporte de long press (>300ms) con escala + tooltip de nombre; el voto se envía al soltar.
- Reacciones UX: tap rápido mantiene voto inmediato sin mostrar tooltip.
- Reacciones frontend: se reutiliza la lógica existente de envío sin cambios de backend.

## 0.23.5
- UI frontend: se reemplaza visualmente el bloque de estrellas por reacciones en Feed/Publicaciones, Detalle, Top 3 de Inicio, Ranking y Mis publicaciones del Perfil.
- Reacciones: nuevos botones tipo chips (`😻 Adorable`, `😂 Me hizo reír`, `🥰 Tierno`, `🤩 Impresionante`, `🔥 Épico`) con estado activo por usuario.
- Reacciones: conteos en tiempo real sin recarga usando los endpoints `add_or_update_reaction` y `get_reaction_counts`.
- Layout/JS: se expone configuración global de nonce/endpoints y se añade controlador frontend para pintar/actualizar reacciones.

## 0.23.4
- Reacciones: nuevo sistema independiente del voto por estrellas con tabla `catgame_reactions` y restricción única por `submission_id + user_id`.
- Backend: nuevo `CatGame_Reactions` con endpoints `add_or_update_reaction` y `get_reaction_counts`, validación por nonce, sanitización y whitelist de tipos (`adorable`, `funny`, `cute`, `wow`, `epic`).
- Reacciones: lógica de upsert (crear o actualizar reacción del usuario) y respuesta con conteos agregados + `user_reaction`.
- Integración: registro del módulo de reacciones en bootstrap del plugin y actualización de esquema DB a versión `5`.

## 0.23.3
- Auth (deslogueado): nueva UI con secciones de Iniciar sesión, Crear cuenta y Olvidé mi contraseña.
- Login: nuevo handler con `wp_signon()` y preservación de usuario/correo en errores (limpiando solo contraseñas).
- Registro: mantiene email/usuario ante validaciones fallidas y limpia contraseñas por seguridad.
- Recuperación: integración con flujo nativo WP (`retrieve_password`) enviando email de restablecimiento con enlace al reset del plugin.
- Reset: nueva pantalla para establecer contraseña (con confirmación + mínimo 8) y actualización vía `reset_password()`.
- UX: botón ver/ocultar contraseña (ojo) en login, registro y reset.

## 0.23.2
- Perfil: se eliminan controles no funcionales de edición (nombre de usuario editable, ciudad/país por defecto e idioma) para dejar una experiencia más clara sin romper funciones existentes.
- Perfil: nuevo botón "Cambiar color" que despliega/oculta el panel de colores del avatar; al guardar cambios el panel vuelve a ocultarse.
- Perfil: la sección de estadísticas se simplifica a "Resumen" con 4 cards compactas (Mejor puntaje, Total votos recibidos, Publicación más votada y Publicación mejor rankeada).
- UI/UX mobile-first: ajustes de espaciado y grid responsive en header de perfil, panel de colores y cards de resumen.

## 0.23.1
- Gestión de eventos (admin): reglas del evento ahora se editan con UI de campos numéricos por criterio, eliminando la edición manual de JSON.
- Reglas: labels y ayudas en español para cada criterio (gato negro, foto nocturna, pose divertida, lugar raro).
- Guardado: normalización segura de reglas (rango 0..10, soporte coma/punto decimal) y persistencia compatible en `rules_json`.
- UI/UX: nuevo bloque visual para reglas con cards responsive en el formulario de creación/edición.

## 0.23.0
- Admin/Eventos: rediseño UI/UX del gestor con paneles de listado, creación/edición, detalle y calendario en una sola vista.
- Eventos: ahora se puede editar un evento existente desde el listado y guardar cambios sin recrearlo.
- Eventos: mejor feedback visual en estados (Activo, Próximo, Finalizado) y avisos de guardado/activación.
- Admin: nuevos estilos dedicados (`assets/admin.css`) para mejorar jerarquía visual, espaciado y consistencia responsive.

## 0.22.3
- Nuevo popup de evento vigente en frontend con botón flotante "Reglas del evento".
- El modal muestra nombre, vigencia y reglas/bonificaciones del evento activo.
- UX móvil: cierre por botón, clic en backdrop o tecla ESC, y autoapertura una vez por sesión/evento.

## 0.22.2
- Fix etiquetas: `normalize_tag()` elimina cualquier prefijo inicial `tag` repetido (`tag_`, `tag-tag-`, etc.) y ya no agrega `tag_` automáticamente.
- Upload/Perfil: se evita re-prefijado de etiquetas personalizadas; persisten y se pueden re-seleccionar correctamente.
- Compatibilidad histórica: filtros de feed/ranking aceptan tags guardados en formatos antiguos (`tag_*`, `tag_tag_*`) y nuevo formato sin prefijo.
- Reglas por defecto (admin/eventos/README) actualizadas al formato de tags sin prefijo.

## 0.22.1
- Fix rutas frontend: fallback de enrutado para `/catgame/*` cuando las reglas de rewrite no están disponibles/actualizadas, evitando el error "No se encontró la página".
- Router: resolución explícita por `REQUEST_URI` para `home`, `upload`, `feed`, `leaderboard`, `profile` y `submission/{id}` con `submission_id` seteado.

## 0.22.0
- Perfil: header con avatar por inicial y color pastel seleccionable, edición de nombre visible y botón de cerrar sesión interno del plugin.
- Perfil: nuevas preferencias guardables (ciudad/país por defecto e idioma), selector de alcance Evento activo/Global y estadísticas ampliadas.
- Perfil: nueva sección "Tu mejor foto", acciones de compartir (copiar/share) e integración de enlace a Instagram.
- App: autor `@username` visible en Inicio/Publicaciones/Ranking/Detalle y destacados visuales para publicaciones propias/Top 3 del evento activo.

## 0.21.0
- Perfil: nuevo bloque de usuario `@username` con botón "Cerrar sesión" del flujo interno del plugin y aviso destacado si el usuario está en Top 3 del evento activo.
- Perfil: mejora en "Mis publicaciones" con imágenes más nítidas (solo en perfil) y puntaje con estrellas + promedio decimal `/5` o "Sin votos".
- Perfil: botón de eliminar etiqueta cambiado a chip compacto `✕` con `aria-label` accesible.
- Inicio/Publicaciones/Ranking/Detalle: se muestra autor como `por @username` y se agregan badges de contexto ("Tu publicación", "Top 3", "Tú") cuando aplica.
- Evento activo Top 3: resaltado transversal en home/feed/ranking/detalle usando posiciones del Top 3 del evento activo.

## 0.20.1
- Home: se elimina el botón duplicado "Subir mi gato" en el bloque de usuario logueado para dejar un único CTA principal en el hero.
- Home Top 3: mejora de nitidez de imágenes usando tamaño `medium_large` y ajuste CSS para evitar pixelación por escalado.

## 0.20.0
- Ranking: reemplazo de tabla por lista tipo cards con puesto, miniatura, título/fallback, ubicación, estrellas 0..5 y votos.
- Ranking: nuevo filtro de etiquetas multiselección (lógica OR) combinado con filtros actuales de alcance/país/ciudad.
- Inicio: estrellas del Top 3 reforzadas en dorado y sección "Cómo funciona" convertida en accesos directos a Subir/Publicaciones/Ranking.
- Inicio: nuevo bloque "Crear cuenta / Iniciar sesión" con auth interna del plugin (ruta perfil) y variante para usuarios logueados.

## 0.19.0
- Inicio: nueva pantalla con hero, evento activo, CTA "Subir mi gato", top 3 del ranking, últimas publicaciones en carrusel horizontal y sección "Cómo funciona".
- Bottom nav: se reordena para dejar "Inicio" centrado y el resaltado tipo burbuja se aplica a la pestaña activa (no fijo en "Subir").
- Navegación móvil: estado activo más visible con burbuja, elevación suave y texto reforzado.

## 0.18.1
- Feed: ajuste de texto de puntaje junto a estrellas a escala entera `/5` (ej: `(4/5)`) y estado `Puntaje: sin votos`.
- Detalle: puntaje mostrado con estrellas y texto entero `/5`, reutilizando la misma lógica visual del feed.
- Detalle: etiquetas confirmadas como chips (`cg-chip-row`, `cg-chip`) sin viñetas, con ajuste mínimo de espaciado.
- Perfil: mejor puntaje y promedio ahora se muestran en escala `/5` (mejor entero, promedio decimal).

## 0.18.0
- Feed: el puntaje ahora se muestra como estrellas (1..5) con fallback "Sin votos" y valor numérico opcional en pequeño.
- Feed y detalle: se muestra el título de la publicación cuando existe, con fallback "Publicación #ID".
- Detalle: las etiquetas se renderizan como chips reutilizando la misma estética visual del feed.
- Upload: nuevo campo "Título (opcional)" y guardado seguro del título sanitizado (máximo 80 caracteres).
- Base de datos: nueva columna nullable `title` en submissions con migración de esquema.

## 0.17.2
- Feed: ajustes visuales app-like en cards con header consistente, badge de ID, ubicación, puntaje y chips con mejor espaciado.
- Feed: CTA "Ver detalle" reforzado como botón táctil con estado activo en móvil.
- Detalle: botón "Enviar voto" ahora usa un tono más intenso para mayor contraste visual.
- Perfil: botón "Eliminar" de etiquetas personalizadas reducido y alineado para no invadir la UI.

## 0.17.1
- UI idioma: en navegación inferior se reemplaza el label "Feed" por "Publicaciones" manteniendo intactas las rutas (`/catgame/feed`) y el label "Ranking".

## 0.17.0
- Feed rediseñado estilo app: tarjetas más limpias con badge de ID, ubicación con ícono, puntaje destacado y CTA "Ver detalle" táctil.
- Se agregan chips de etiquetas en cada card con paleta pastel alternada y ajuste responsive con wrap en móvil.
- Mejoras visuales mobile-first en metadatos de card para lectura más clara y consistente.

## 0.16.1
- Fix feed skeleton: la imagen ahora queda visible por defecto (progressive enhancement) y el placeholder se oculta al cargar.
- Se agrega fallback robusto de error de imagen con mensaje "No se pudo cargar la imagen" para evitar estado de carga infinito.
- Ajuste JS de carga de imágenes para marcar correctamente estados `is-loaded` e `is-error` incluso con imágenes cacheadas.

## 0.16.0
- Feed: se agregan placeholders skeleton para imágenes en tarjetas hasta que cargan, con animación shimmer y fallback si la imagen falla.
- Feed y clasificación: nuevos estados vacíos en español con mensajes claros para ausencia de publicaciones o ranking.
- Mejora de accesibilidad en imágenes del feed con texto alternativo descriptivo.

## 0.15.0
- Se agregan notificaciones toast en frontend standalone (éxito/error/info) con contenedor global en layout y estilos mobile-first sobre la barra inferior.
- Integración en flujo de voto y subida: mensajes "Enviando voto…", "Subiendo foto…", "Gracias por tu voto", "Foto subida correctamente" y error genérico.
- Se limpian parámetros de mensaje en la URL tras mostrar el toast para evitar repeticiones al recargar.

## 0.14.1
- Nueva navegación inferior fija tipo app móvil (Inicio, Subir, Feed, Ranking, Perfil) con pestaña activa resaltada automáticamente.
- Botón "Subir" destacado en el centro y ajustes de espaciado para evitar superposición con el contenido.
- Ajustes responsive para mantener compatibilidad desktop/mobile con paleta pastel.

## 0.14.0
- Refresh visual completo en `assets/app.css` con tema pastel (variables CSS), tarjetas translúcidas, botones suaves, navegación activa en lavanda y mejoras de formularios/tablas.
- Ajustes mobile-first de layout y componentes para mantener legibilidad en pantallas pequeñas.

## 0.13.2
- Fix definitivo en ajustes admin: el botón "Seleccionar desde biblioteca" ahora usa script dedicado encolado (`assets/admin-settings.js`) y abre correctamente la Media Library.
- Se elimina el script inline de la vista de ajustes para evitar problemas de carga/orden de ejecución.

## 0.13.1
- Fix admin ajustes: el botón "Seleccionar desde biblioteca" vuelve a funcionar al asegurar la carga de `wp_enqueue_media()` en la pantalla correcta (`page=catgame-settings`).

## 0.13.0
- Nuevo submenú **Ajustes** en admin de Cat Game con opción para cargar/quitar imagen de fondo.
- Integración con Media Library de WordPress para seleccionar fondo desde la biblioteca.
- Se guarda la configuración en opción del plugin y se aplica en el frontend standalone (`/catgame/*`).

## 0.12.1
- Página de detalle de publicación (`/catgame/submission/{id}`): imagen principal responsive para móvil y desktop.
- Se agrega contenedor con ancho máximo, centrado, `loading="lazy"`, borde redondeado y sombra ligera.

## 0.12.0
- Navegación frontend traducida al español: Inicio, Subir, Publicaciones, Clasificación, Mi perfil.
- Pestaña activa destacada en el menú con estilo visible y navegación adaptada a móvil (wrap/scroll + sticky header).
- Barrido de textos visibles para reducir remanentes en inglés (p.ej. Puntaje/Publicación/Publicaciones).

## 0.11.0
- Submission: si el usuario ya votó, se oculta la UI de votación y se muestra "✅ Ya votaste en esta foto."
- Nueva UI de votación por 5 estrellas clickeables (1 a 5), reemplazando el selector numérico.
- Se agrega comprobación previa al envío para exigir rating válido (1..5) antes de votar.

## 0.10.3
- Fix de etiquetas en detalle: ahora se muestran todas las etiquetas combinando `tags_json` y `tags_text` para compatibilidad histórica.
- Se robusteció el guardado de etiquetas seleccionadas en upload para no perder opciones elegidas por el usuario.

## 0.10.2
- Fix de normalización de etiquetas para evitar duplicación de prefijos (`tag_tag_*`).
- En detalle/feed, etiquetas históricas como `tag_tag_hermosa` ahora se muestran como `Hermosa` (sin "Tag").

## 0.10.1
- UI de etiquetas: se oculta cualquier prefijo visual "Tag" en upload y perfil, mostrando solo nombres legibles (ej: "Tierna").
- Perfil ya no muestra slugs técnicos (`tag_*`) en la lista de etiquetas personalizadas.

## 0.10.0
- Etiquetas sin bonos: el score ahora depende solo de votos (0..10) y se elimina el breakdown de bonos.
- Upload/Detail/Profile usan la terminología y visualización de etiquetas.
- Feed agrega filtro por etiqueta (Todas + catálogo del usuario + predefinidas).
- Profile permite eliminar etiquetas personalizadas del catálogo personal (sin afectar submissions históricas).
- Persistencia: nueva columna `tags_text` para filtrado por etiqueta.

## 0.9.1
- Fix upload submit: se elimina el envío por `fetch` en compresión client-side para evitar rutas de error/404 al redireccionar.
- Ahora, al enviar, se reemplaza el `input[type=file]` con el archivo comprimido usando `DataTransfer` y se mantiene submit HTML nativo.

## 0.9.0
- Compresión client-side en upload: resize máx 1280px, WEBP (fallback JPEG), iteración de calidad hasta objetivo de peso.
- Upload muestra tamaño original, tamaño comprimido, reducción, formato final y estado de compresión; incluye preview.
- Fallback server-side: si el archivo final subido supera 2MB se recomprime en servidor (1280px, calidad 82, preferencia WEBP).
- Se guarda `image_size_bytes` en submissions y se muestra tamaño en detalle (y feed).
- Migración de esquema segura con versionado para agregar `image_size_bytes`.

## 0.8.0
- Upload ahora permite agregar tags personalizados además de los predefinidos.
- Los tags personalizados se guardan por usuario y se reutilizan en futuras subidas del mismo usuario.

## 0.7.0
- Compresión máxima en servidor al subir imágenes (calidad optimizada) y regeneración de metadata.
- UI de upload ahora muestra tamaño del archivo seleccionado antes de enviar.

## 0.6.0
- Profile route `/catgame/profile` ahora soporta registro de usuario con login automático.
- Nuevo handler seguro de registro (nonce, validaciones, sanitización y mensajes de error).
- Si no hay sesión en profile, se muestra formulario de alta; tras registro exitoso se redirige al mismo perfil autenticado.

## 0.5.0
- Fase 5: documentación README completa.

## 0.4.0
- Fase 4: leaderboards con cache y panel admin de eventos/moderación.

## 0.3.0
- Fase 3: sistema de votación con rate limit, deduplicación y scoring.

## 0.2.0
- Fase 2: upload multipart + feed + detalle de submission.

## 0.1.0
- Fase 1: scaffold, rutas standalone, layout completo, tablas y events base.

# Changelog

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

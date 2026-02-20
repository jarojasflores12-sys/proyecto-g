# Changelog

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

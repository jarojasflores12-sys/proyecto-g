# Cat Game Vote Standalone

Plugin WordPress para juego de gatos con frontend standalone, votación comunitaria y moderación manual (sin IA).

## Instalación
1. Copiar carpeta `cat-game-vote-standalone` dentro de `wp-content/plugins/`.
2. Activar el plugin desde WP Admin.
3. El hook de activación crea tablas custom y registra rutas.

## Activar rutas (permalinks)
Si `/catgame/*` no responde:
1. Ir a **Ajustes > Enlaces permanentes**.
2. Presionar **Guardar cambios** (sin necesidad de modificar nada).

## Rutas frontend standalone
- `/catgame/` home
- `/catgame/upload` subir foto
- `/catgame/feed` feed
- `/catgame/submission/{id}` detalle + votar
- `/catgame/leaderboard` rankings
- `/catgame/profile` perfil/progreso

Estas páginas son renderizadas por el plugin en `template_redirect` con layout HTML propio (sin depender del theme).

## Crear evento y rules_json
En admin: **Cat Game > Events**.

Campos:
- name
- starts_at / ends_at
- is_active
- rules_json

Ejemplo `rules_json`:
```json
{
  "tag_black_cat": 1.0,
  "tag_night_photo": 0.5,
  "tag_funny_pose": 0.5,
  "tag_weird_place": 0.5
}
```

Solo un evento activo a la vez.

## Uso
1. Crear y activar evento.
2. Usuarios suben fotos en `/catgame/upload` con ciudad/país manuales, tags y checkbox obligatorio de no personas.
3. Comunidad vota 1-5 estrellas en detalle.
4. El score se recalcula y se refleja en feed/leaderboard.

## Scoring
- `score_base = (votes_sum / votes_count) * 2`
- `score_final = min(10, score_base + suma_bonos_tags)`
- Sin votos: score `0` y texto `sin votos`.

## Seguridad
- Nonces en upload, vote y moderation.
- Sanitización de city/country.
- rating validado 1..5.
- Archivo validado como `image/*` y máximo 3MB.
- Menús admin solo para `manage_options`.
- Anti voto duplicado por lógica + índice único `(submission_id,user_id)`.
- Rate limit: 50 votos/día por usuario.

## Moderación manual
En admin: **Cat Game > Moderation**.
- Acciones: `Disqualify` / `Restore`.
- Los `disqualified` se excluyen de leaderboard/feed.

## Limitaciones
- Sin detección IA de contenido.
- La validación de “no personas” depende del checkbox del usuario + moderación manual.

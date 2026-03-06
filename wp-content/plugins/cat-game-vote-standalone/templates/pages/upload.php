<?php
$event = $data['event'] ?? null;
$user_tags = $data['user_tags'] ?? [];
$upload_defaults = $data['upload_defaults'] ?? ['default_city' => '', 'default_country' => ''];
$upload_state = $data['upload_state'] ?? [];
$upload_error = (string) ($data['upload_error'] ?? '');
$requires_profile_completion = !empty($data['requires_profile_completion']);
$location_text = CatGame_Submissions::visual_label(trim((string) ($upload_defaults['default_city'] ?? ''))) . ', ' . CatGame_Submissions::visual_label(trim((string) ($upload_defaults['default_country'] ?? '')));
$location_text = trim($location_text, ' ,');
$upload_restriction = (array) ($data['upload_restriction'] ?? []);
$upload_is_banned = !empty($upload_restriction['upload_banned']);
$upload_ban_until_iso = (string) ($upload_restriction['upload_banned_until'] ?? '');
$upload_ban_until = '';
if ($upload_ban_until_iso !== '') {
    $upload_ban_until_ts = strtotime($upload_ban_until_iso);
    if ($upload_ban_until_ts) {
        $upload_ban_until = wp_date('d/m/Y H:i', $upload_ban_until_ts);
    }
}
$event_type = sanitize_key((string) ($event['event_type'] ?? 'competitive'));
$is_thematic_event = !empty($event['id']) && $event_type === 'thematic';
$publish_context = $is_thematic_event
    ? 'Tema actual: ' . (string) ($event['name'] ?? 'Evento')
    : ($event
        ? 'Se publicará en: Evento activo — ' . (string) ($event['name'] ?? 'Evento')
        : 'Se publicará en: Modo libre (no competitivo)');
$has_active_event = !empty($event['id']) && !$is_thematic_event;
$selected_publish_mode = (string) ($upload_state['publish_mode'] ?? ($has_active_event ? '' : 'free'));
if (!in_array($selected_publish_mode, ['event', 'free'], true)) {
    $selected_publish_mode = $has_active_event ? '' : 'free';
}
?>
<section>
    <h2>Subir foto</h2>
    <?php if (!is_user_logged_in()): ?>
        <p>Debes iniciar sesión para subir fotos.</p>
    <?php elseif ($requires_profile_completion): ?>
        <p class="cg-alert cg-alert-error">Debes completar tu perfil y aceptar las normas antes de publicar.</p>
        <a class="cg-cta" href="<?php echo esc_url(home_url('/catgame/profile?complete_profile=1')); ?>">Ir a mi perfil</a>
    <?php elseif ($upload_is_banned): ?>
        <article class="cg-card cg-upload-ban-card" role="status" aria-live="polite">
            <p class="cg-upload-ban-card__title">Subida restringida</p>
            <p>No puedes subir publicaciones hasta: <?php echo esc_html($upload_ban_until !== '' ? $upload_ban_until : 'la fecha indicada'); ?>.</p>
            <small>Puedes seguir reaccionando.</small>
        </article>
    <?php else: ?>
        <?php if ($upload_error !== ''): ?><p class="cg-alert cg-alert-error"><?php echo esc_html($upload_error); ?></p><?php endif; ?>
        <p class="cg-upload-context"><?php echo esc_html($publish_context); ?></p>
        <p class="cg-upload-location"><strong>Ubicación:</strong> <?php echo esc_html($location_text); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="cg-form">
            <?php wp_nonce_field('catgame_upload'); ?>
            <input type="hidden" name="action" value="catgame_upload">
            <fieldset class="cg-upload-mode" data-upload-mode="1" data-has-event="<?php echo $has_active_event ? '1' : '0'; ?>">
                <legend>¿Dónde quieres publicar tu foto?</legend>
                <input type="hidden" name="publish_mode" value="<?php echo esc_attr($selected_publish_mode); ?>" data-upload-mode-input="1">
                <div class="cg-upload-mode__options">
                    <?php if ($has_active_event): ?>
                        <button type="button" class="cg-upload-mode__option <?php echo $selected_publish_mode === 'event' ? 'is-active' : ''; ?>" data-upload-mode-option="event">🏆 Participar en el evento</button>
                    <?php endif; ?>
                    <button type="button" class="cg-upload-mode__option <?php echo $selected_publish_mode === 'free' ? 'is-active' : ''; ?>" data-upload-mode-option="free">🐾 Publicar en modo libre</button>
                </div>
                <p class="cg-upload-mode__help" data-upload-mode-help="1">
                    <?php if ($selected_publish_mode === 'event'): ?>
                        Tu foto participará en el evento activo.
                    <?php elseif ($selected_publish_mode === 'free'): ?>
                        Tu foto se publicará sin competir en el ranking.
                    <?php else: ?>
                        Elige un modo para continuar.
                    <?php endif; ?>
                </p>
            </fieldset>
            <label>
                Título
                <input
                    type="text"
                    name="title"
                    required
                    minlength="2"
                    maxlength="40"
                    value="<?php echo esc_attr((string) ($upload_state['title'] ?? '')); ?>"
                    placeholder="Ej: Michi, Pelusa, Tom"
                    data-required-message="El título es obligatorio."
                >
            </label>
            <label>
                Etiquetas (separadas por coma o salto de línea)
                <textarea name="custom_tags" rows="3" id="catgame-upload-tags-input" placeholder="ej: pelusa, siesta, ventana"><?php echo esc_textarea((string) ($upload_state['custom_tags'] ?? '')); ?></textarea>
            </label>
            <?php if (!empty($user_tags)): ?>
                <details class="cg-tag-suggest" id="catgame-tag-suggestions" data-user-tags="<?php echo esc_attr(wp_json_encode(array_values($user_tags))); ?>">
                    <summary class="cg-tag-suggest__summary">Mis etiquetas guardadas</summary>
                    <div class="cg-tag-suggest__list" data-tag-saved-list="1"></div>
                </details>
            <?php endif; ?>

            <div class="cg-upload-picker" data-catgame-upload-picker>
                <p class="cg-upload-picker__title">Selecciona tu foto</p>
                <div class="cg-upload-picker__actions">
                    <button type="button" class="cg-upload-picker__btn" data-catgame-pick-universal="1">Seleccionar foto</button>
                    <button type="button" class="cg-upload-picker__btn" data-catgame-pick-file="1">Subir archivo</button>
                    <button type="button" class="cg-upload-picker__btn" data-catgame-pick-camera="1">Tomar foto</button>
                </div>
                <p class="cg-file-picker-text">JPG, PNG o WEBP</p>
            </div>

            <input type="file" name="cat_image" id="catgame-cat-image" class="cg-file-input" accept="image/*" required>
            <input type="file" id="catgame-cat-image-universal" class="cg-file-input" accept="image/*" tabindex="-1" aria-hidden="true">
            <input type="file" id="catgame-cat-image-file" class="cg-file-input" accept="image/*" tabindex="-1" aria-hidden="true">
            <input type="file" id="catgame-cat-image-camera" class="cg-file-input" accept="image/*" capture="environment" tabindex="-1" aria-hidden="true">

            <p id="catgame-compress-status" class="cg-file-size cg-visually-hidden" aria-live="polite">Estado: esperando archivo</p>
            <img id="catgame-image-preview" class="cg-image-preview" alt="Preview de imagen seleccionada" style="display:none;" />

            <button type="button" class="secondary" data-open-upload-rules="1">Ver normas</button>
            <button type="submit" class="cg-upload-submit">Enviar</button>
        </form>

        <div class="cg-modal" id="catgame-upload-rules-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-upload-rules-title">
            <div class="cg-modal__backdrop" data-upload-rules-close="1"></div>
            <div class="cg-modal__content" role="document">
                <button type="button" class="cg-modal__close" data-upload-rules-close="1" aria-label="Cerrar normas">✕</button>
                <h2 id="catgame-upload-rules-title">Normas y sanciones</h2>
                <div class="cg-modal__rules">
                    <section class="cg-modal__rules-section">
                        <h3>Qué sí está permitido</h3>
                        <ul>
                            <li>Mascotas domésticas.</li>
                            <li>Ejemplos: perro, gato, conejo, gallina, pez, etc.</li>
                        </ul>
                    </section>
                    <section class="cg-modal__rules-section">
                        <h3>Qué no se permite</h3>
                        <ul>
                            <li>Fauna silvestre o exótica.</li>
                            <li>Personas.</li>
                            <li>Contenido sexual, explícito o violento.</li>
                            <li>Maltrato animal.</li>
                            <li>Spam o imágenes que no correspondan.</li>
                        </ul>
                    </section>
                    <section class="cg-modal__rules-section">
                        <h3>Sanciones</h3>
                        <ul>
                            <li><strong>Leve:</strong> se elimina la publicación.</li>
                            <li><strong>Moderada:</strong> se elimina la publicación + no puede subir por 3 días.</li>
                            <li><strong>Grave:</strong> se elimina la publicación + no puede subir ni reaccionar + apelación 24h; si no apela o se rechaza, cuenta eliminada y ban permanente.</li>
                        </ul>
                    </section>
                    <section class="cg-modal__rules-section">
                        <h3>Apelaciones</h3>
                        <ul>
                            <li>Leve y moderada: 72 horas.</li>
                            <li>Grave: 24 horas.</li>
                        </ul>
                    </section>
                </div>
                <div class="cg-confirm-actions">
                    <button type="button" data-upload-rules-close="1">Entendido</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

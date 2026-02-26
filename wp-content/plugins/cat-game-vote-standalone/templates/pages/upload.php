<?php
$event = $data['event'] ?? null;
$user_tags = $data['user_tags'] ?? [];
$upload_defaults = $data['upload_defaults'] ?? ['default_city' => '', 'default_country' => ''];
$upload_state = $data['upload_state'] ?? [];
$upload_error = (string) ($data['upload_error'] ?? '');
$requires_location = !empty($data['requires_location']);
$location_text = trim((string) ($upload_defaults['default_city'] ?? '')) . ', ' . trim((string) ($upload_defaults['default_country'] ?? ''));
$location_text = trim($location_text, ' ,');
?>
<section>
    <h2>Subir foto</h2>
    <?php if (!is_user_logged_in()): ?>
        <p>Debes iniciar sesión para subir fotos.</p>
    <?php elseif (!$event): ?>
        <p>No hay evento activo para recibir publicaciones.</p>
    <?php elseif ($requires_location): ?>
        <p class="cg-alert cg-alert-error">Completa tu ciudad y país en tu perfil para poder subir fotos.</p>
        <a class="cg-cta" href="<?php echo esc_url(home_url('/catgame/profile?complete_profile=1')); ?>">Ir a mi perfil</a>
    <?php else: ?>
        <?php if ($upload_error !== ''): ?><p class="cg-alert cg-alert-error"><?php echo esc_html($upload_error); ?></p><?php endif; ?>
        <p class="cg-upload-location"><strong>Ubicación:</strong> <?php echo esc_html($location_text); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="cg-form">
            <?php wp_nonce_field('catgame_upload'); ?>
            <input type="hidden" name="action" value="catgame_upload">
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
            <fieldset>
                <legend>Etiquetas</legend>
                <?php foreach ($user_tags as $tag): ?>
                    <label>
                        <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag); ?>" <?php checked(in_array($tag, (array) ($upload_state['selected_tags'] ?? []), true)); ?>>
                        <?php echo esc_html(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <label>
                Etiquetas personalizadas (separadas por coma o salto de línea)
                <textarea name="custom_tags" rows="3" placeholder="ej: gato_travieso, siesta_eternal"><?php echo esc_textarea((string) ($upload_state['custom_tags'] ?? '')); ?></textarea>
            </label>

            <div class="cg-upload-picker" data-catgame-upload-picker>
                <p class="cg-upload-picker__title">Selecciona tu foto</p>
                <div class="cg-upload-picker__actions">
                    <button type="button" class="cg-upload-picker__btn" data-catgame-pick-file="1">Subir archivo</button>
                    <button type="button" class="cg-upload-picker__btn" data-catgame-pick-camera="1">Tomar foto</button>
                </div>
                <p class="cg-file-picker-text">JPG, PNG o WEBP</p>
            </div>

            <input type="file" name="cat_image" id="catgame-cat-image" class="cg-file-input" accept="image/*" required>
            <input type="file" id="catgame-cat-image-file" class="cg-file-input" accept=".jpg,.jpeg,.png,.webp" tabindex="-1" aria-hidden="true">
            <input type="file" id="catgame-cat-image-camera" class="cg-file-input" accept="image/*" capture="environment" tabindex="-1" aria-hidden="true">

            <p id="catgame-compress-status" class="cg-file-size cg-visually-hidden" aria-live="polite">Estado: esperando archivo</p>
            <img id="catgame-image-preview" class="cg-image-preview" alt="Preview de imagen seleccionada" style="display:none;" />

            <label><input type="checkbox" name="confirm_no_people" value="1" required <?php checked(!empty($upload_state['confirm_no_people'])); ?>> Acepto los términos</label>
            <button type="button" class="secondary" data-open-upload-rules="1">Ver reglas del juego</button>
            <button type="submit" class="cg-upload-submit">Enviar</button>
        </form>

        <div class="cg-modal" id="catgame-upload-rules-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-upload-rules-title">
            <div class="cg-modal__backdrop" data-upload-rules-close="1"></div>
            <div class="cg-modal__content" role="document">
                <button type="button" class="cg-modal__close" data-upload-rules-close="1" aria-label="Cerrar reglas">✕</button>
                <h2 id="catgame-upload-rules-title">Reglas del juego</h2>
                <ul class="cg-modal__rules">
                    <li>Solo mascotas en la foto.</li>
                    <li>No incluir personas visibles.</li>
                    <li>Sin contenido sexual o explícito.</li>
                    <li>Sin violencia ni maltrato animal.</li>
                    <li>Respeta a la comunidad y evita contenido ofensivo.</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</section>

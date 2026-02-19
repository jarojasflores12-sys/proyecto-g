<?php
$event = $data['event'] ?? null;
$user_tags = $data['user_tags'] ?? [];
$tag_labels = [
    'tag_black_cat' => 'Gato negro (+según regla)',
    'tag_night_photo' => 'Foto nocturna',
    'tag_funny_pose' => 'Pose divertida',
    'tag_weird_place' => 'Lugar raro',
];
?>
<section>
    <h2>Subir foto</h2>
    <?php if (!is_user_logged_in()): ?>
        <p>Debes iniciar sesión para subir fotos.</p>
    <?php elseif (!$event): ?>
        <p>No hay evento activo para recibir submissions.</p>
    <?php else: ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="cg-form">
            <?php wp_nonce_field('catgame_upload'); ?>
            <input type="hidden" name="action" value="catgame_upload">
            <label>Ciudad <input type="text" name="city" required></label>
            <label>País <input type="text" name="country" required></label>
            <fieldset>
                <legend>Tags</legend>
                <?php foreach ($user_tags as $tag): ?>
                    <label>
                        <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag); ?>">
                        <?php echo esc_html($tag_labels[$tag] ?? ucwords(str_replace('_', ' ', str_replace('tag_', '', $tag)))); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <label>
                Tags personalizados (separados por coma o salto de línea)
                <textarea name="custom_tags" rows="3" placeholder="ej: gato_travieso, siesta_eternal"></textarea>
            </label>
            <label>Imagen <input type="file" name="cat_image" id="catgame-cat-image" accept="image/*" required></label>
            <div class="cg-compress-meta" aria-live="polite">
                <p id="catgame-file-size-original" class="cg-file-size">Tamaño original: -</p>
                <p id="catgame-file-size-compressed" class="cg-file-size">Tamaño comprimido: -</p>
                <p id="catgame-file-reduction" class="cg-file-size">Reducción: -</p>
                <p id="catgame-file-format" class="cg-file-size">Formato final: -</p>
                <p id="catgame-compress-status" class="cg-file-size">Estado: esperando archivo</p>
            </div>
            <img id="catgame-image-preview" class="cg-image-preview" alt="Preview de imagen seleccionada" style="display:none;" />
            <label><input type="checkbox" name="confirm_no_people" value="1" required> Confirmo que no hay personas en la foto</label>
            <button type="submit">Enviar</button>
        </form>
    <?php endif; ?>
</section>

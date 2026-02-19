<?php
$event = $data['event'] ?? null;
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
                <label><input type="checkbox" name="tags[]" value="tag_black_cat"> Gato negro (+según regla)</label>
                <label><input type="checkbox" name="tags[]" value="tag_night_photo"> Foto nocturna</label>
                <label><input type="checkbox" name="tags[]" value="tag_funny_pose"> Pose divertida</label>
                <label><input type="checkbox" name="tags[]" value="tag_weird_place"> Lugar raro</label>
            </fieldset>
            <label>Imagen <input type="file" name="cat_image" accept="image/*" required></label>
            <label><input type="checkbox" name="confirm_no_people" value="1" required> Confirmo que no hay personas en la foto</label>
            <button type="submit">Enviar</button>
        </form>
    <?php endif; ?>
</section>

<?php
$defaults = is_array($data['defaults'] ?? null) ? $data['defaults'] : [];
$error_key = sanitize_key((string) ($data['form_error_key'] ?? ''));
$error_message = $error_key !== '' ? CatGame_Submissions::adoption_error_message($error_key) : '';
$requires_login = !empty($data['requires_login']);
$default_gender = (string) ($defaults['pet_gender'] ?? '');
$default_age_value = max(0, (int) ($defaults['pet_age_value'] ?? 0));
$default_age_unit = (string) ($defaults['pet_age_unit'] ?? 'years');
if (!in_array($default_age_unit, ['months', 'years'], true)) {
    $default_age_unit = 'years';
}
?>
<section class="cg-adoptions-page">
    <header class="cg-adoptions-head">
        <h2>Nueva publicación de Adopciones</h2>
        <p>Publica mascotas en adopción o que necesiten hogar temporal.</p>
    </header>

    <?php if ($requires_login): ?>
        <p class="cg-alert cg-alert-error">Debes iniciar sesión para publicar en Adopciones.</p>
    <?php endif; ?>

    <?php if ($error_message !== ''): ?>
        <p class="cg-alert cg-alert-error"><?php echo esc_html($error_message); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="cg-form cg-adoption-form">
        <?php wp_nonce_field('catgame_create_adoption'); ?>
        <input type="hidden" name="action" value="catgame_create_adoption">

        <label>Foto de la mascota</label>
        <div class="cg-adoption-photo-picker">
            <input id="cg-adoption-image" type="file" name="adoption_image" accept="image/*" required>
            <label class="cg-btn cg-adoption-photo-btn" for="cg-adoption-image">📷 Seleccionar foto</label>
            <img id="cg-adoption-preview" class="cg-adoption-preview" alt="Vista previa de la foto de la mascota" hidden>
        </div>

        <label>Nombre de la mascota
            <input type="text" name="pet_name" maxlength="120" value="<?php echo esc_attr((string) ($defaults['pet_name'] ?? '')); ?>" required>
        </label>

        <label>Tipo de mascota
            <input type="text" name="pet_type" maxlength="120" placeholder="Ej: Perro, gato" value="<?php echo esc_attr((string) ($defaults['pet_type'] ?? '')); ?>">
        </label>

        <div class="cg-adoption-inline-fields">
            <fieldset class="cg-adoption-sex-fieldset">
                <legend>Sexo</legend>
                <div class="cg-adoption-sex-buttons">
                    <label class="cg-sex-btn cg-sex-btn--male">
                        <input type="radio" name="pet_gender" value="male" <?php checked($default_gender, 'male'); ?> required>
                        <span>💙 Macho</span>
                    </label>
                    <label class="cg-sex-btn cg-sex-btn--female">
                        <input type="radio" name="pet_gender" value="female" <?php checked($default_gender, 'female'); ?> required>
                        <span>💗 Hembra</span>
                    </label>
                </div>
            </fieldset>
            <fieldset>
                <legend>Edad</legend>
                <div class="cg-adoption-age-row">
                    <input type="number" name="pet_age_value" min="1" step="1" inputmode="numeric" value="<?php echo $default_age_value > 0 ? (int) $default_age_value : ''; ?>" required>
                    <select name="pet_age_unit" required>
                        <option value="months" <?php selected($default_age_unit, 'months'); ?>>Meses</option>
                        <option value="years" <?php selected($default_age_unit, 'years'); ?>>Años</option>
                    </select>
                </div>
            </fieldset>
        </div>

        <div class="cg-adoption-inline-fields">
            <label>Ciudad
                <input type="text" name="city" maxlength="120" value="<?php echo esc_attr((string) ($defaults['city'] ?? '')); ?>" required>
            </label>
            <label>País
                <input type="text" name="country" maxlength="120" value="<?php echo esc_attr((string) ($defaults['country'] ?? '')); ?>" required>
            </label>
        </div>

        <label>Tipo de publicación
            <select name="adoption_type" required>
                <option value="adoption" <?php selected((string) ($defaults['adoption_type'] ?? 'adoption'), 'adoption'); ?>>🏡 En adopción</option>
                <option value="temporary" <?php selected((string) ($defaults['adoption_type'] ?? ''), 'temporary'); ?>>🛏 Hogar temporal</option>
            </select>
        </label>

        <label>Descripción
            <textarea name="description" rows="4" maxlength="1500" required><?php echo esc_textarea((string) ($defaults['description'] ?? '')); ?></textarea>
        </label>

        <label>Contacto
            <textarea name="contact" rows="3" maxlength="600" placeholder="WhatsApp, Instagram o correo" required><?php echo esc_textarea((string) ($defaults['contact'] ?? '')); ?></textarea>
        </label>

        <button type="submit">Publicar en Adopciones</button>
    </form>

    <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url(home_url('/catgame/adoptions')); ?>">← Volver a Adopciones</a>
</section>
<script>
(function () {
  const input = document.getElementById('cg-adoption-image');
  const preview = document.getElementById('cg-adoption-preview');
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    const file = input.files && input.files[0] ? input.files[0] : null;
    if (!file) {
      preview.hidden = true;
      preview.removeAttribute('src');
      return;
    }
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
  });
})();
</script>

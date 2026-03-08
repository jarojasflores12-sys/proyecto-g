<?php
$defaults = is_array($data['defaults'] ?? null) ? $data['defaults'] : [];
$error_key = sanitize_key((string) ($data['form_error_key'] ?? ''));
$error_message = $error_key !== '' ? CatGame_Submissions::adoption_error_message($error_key) : '';
$requires_login = !empty($data['requires_login']);
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

        <label>Foto de la mascota
            <input type="file" name="adoption_image" accept="image/*" required>
        </label>

        <label>Nombre de la mascota
            <input type="text" name="pet_name" maxlength="120" value="<?php echo esc_attr((string) ($defaults['pet_name'] ?? '')); ?>" required>
        </label>

        <label>Tipo de mascota
            <input type="text" name="pet_type" maxlength="120" placeholder="Ej: Perro, gato" value="<?php echo esc_attr((string) ($defaults['pet_type'] ?? '')); ?>">
        </label>

        <div class="cg-adoption-inline-fields">
            <label>Sexo
                <select name="pet_gender" required>
                    <option value="">Selecciona</option>
                    <option value="male" <?php selected((string) ($defaults['pet_gender'] ?? ''), 'male'); ?>>Macho</option>
                    <option value="female" <?php selected((string) ($defaults['pet_gender'] ?? ''), 'female'); ?>>Hembra</option>
                </select>
            </label>
            <label>Edad
                <input type="text" name="pet_age" maxlength="40" placeholder="Ej: 2 años" value="<?php echo esc_attr((string) ($defaults['pet_age'] ?? '')); ?>" required>
            </label>
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

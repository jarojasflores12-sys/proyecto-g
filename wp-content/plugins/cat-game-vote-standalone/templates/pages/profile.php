<?php
$register_error = !empty($data['register_error']) ? CatGame_Auth::registration_error_message((string) $data['register_error']) : '';
$registered = !empty($data['registered']);

if (!empty($data['requires_login'])): ?>
<section>
    <h2>Crear cuenta</h2>
    <p>Regístrate para participar, subir fotos y votar.</p>

    <?php if ($register_error): ?>
        <p class="cg-alert cg-alert-error"><?php echo esc_html($register_error); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form">
        <?php wp_nonce_field('catgame_register'); ?>
        <input type="hidden" name="action" value="catgame_register">

        <label>Usuario
            <input type="text" name="username" required maxlength="60" autocomplete="username">
        </label>
        <label>Email
            <input type="email" name="email" required autocomplete="email">
        </label>
        <label>Contraseña
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Confirmar contraseña
            <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        </label>

        <button type="submit">Crear cuenta y entrar</button>
    </form>
</section>
<?php return; endif;
$stats = $data['stats'] ?? ['total_submissions' => 0, 'best_score' => 0, 'avg_score' => 0];
$items = $data['items'] ?? [];
?>
<section>
    <h2>Mi perfil</h2>

    <?php if ($registered): ?>
        <p class="cg-alert cg-alert-success">¡Cuenta creada! Ya estás dentro.</p>
    <?php endif; ?>

    <ul>
        <li>Total submissions: <?php echo (int) $stats['total_submissions']; ?></li>
        <li>Mejor score: <?php echo esc_html(number_format((float) $stats['best_score'], 2)); ?></li>
        <li>Score promedio: <?php echo esc_html(number_format((float) $stats['avg_score'], 2)); ?></li>
    </ul>

    <h3>Mis submissions</h3>
    <div class="cg-grid">
        <?php if (!$items): ?>
            <p>Aún no tienes submissions.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <article class="cg-card">
                <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'thumbnail'); ?>
                <p>#<?php echo (int) $item['id']; ?> — <?php echo esc_html($item['status']); ?></p>
                <p>Score: <?php echo esc_html(number_format((float) $item['score_cached'], 2)); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

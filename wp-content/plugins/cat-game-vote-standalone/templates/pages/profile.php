<?php
$register_error = !empty($data['register_error']) ? CatGame_Auth::registration_error_message((string) $data['register_error']) : '';
$registered = !empty($data['registered']);
$tag_deleted = !empty($data['tag_deleted']);

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
$custom_tags = $data['custom_tags'] ?? [];
$best_score_5 = max(0, min(5, (int) round(((float) ($stats['best_score'] ?? 0)) / 2)));
$avg_score_5 = max(0, ((float) ($stats['avg_score'] ?? 0)) / 2);
$top_position_for_user = $data['top_position_for_user'] ?? null;
$current_user = wp_get_current_user();
$username = $current_user && !empty($current_user->user_login) ? (string) $current_user->user_login : 'usuario';
?>
<section>
    <h2>Mi perfil</h2>
    <div class="cg-profile-user">
        <p>Usuario: @<?php echo esc_html($username); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('catgame_logout'); ?>
            <input type="hidden" name="action" value="catgame_logout">
            <button type="submit" class="secondary">Cerrar sesión</button>
        </form>
    </div>

    <?php if ($registered): ?>
        <p class="cg-alert cg-alert-success">¡Cuenta creada! Ya estás dentro.</p>
    <?php endif; ?>

    <?php if ($tag_deleted): ?>
        <p class="cg-alert cg-alert-success">Etiqueta eliminada del catálogo personal.</p>
    <?php endif; ?>

    <?php if (!empty($top_position_for_user)): ?>
        <p class="cg-alert cg-alert-success">🏆 Estás en el Top 3 del evento: #<?php echo (int) $top_position_for_user; ?>. <a href="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>">Ver ranking</a></p>
    <?php endif; ?>

    <ul>
        <li>Total publicaciones: <?php echo (int) $stats['total_submissions']; ?></li>
        <li>Mejor puntaje: <?php echo (int) $best_score_5; ?>/5</li>
        <li>Puntaje promedio: <?php echo esc_html(number_format($avg_score_5, 2)); ?>/5</li>
    </ul>

    <h3>Mis etiquetas personalizadas</h3>
    <?php if (empty($custom_tags)): ?>
        <p>No tienes etiquetas personalizadas.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($custom_tags as $tag => $label): ?>
                <li class="cg-tag-item">
                    <strong><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?></strong>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-tag-delete-form" onsubmit="return confirm('¿Eliminar etiqueta?');">
                        <?php wp_nonce_field('catgame_delete_custom_tag'); ?>
                        <input type="hidden" name="action" value="catgame_delete_custom_tag">
                        <input type="hidden" name="tag" value="<?php echo esc_attr($tag); ?>">
                        <button type="submit" class="cg-tag-delete cg-tag-remove" aria-label="Eliminar etiqueta <?php echo esc_attr(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?>" title="Eliminar etiqueta">✕</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Mis publicaciones</h3>
    <div class="cg-grid">
        <?php if (!$items): ?>
            <p>Aún no tienes publicaciones.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php
            $score_5 = ((float) ($item['score_cached'] ?? 0)) / 2;
            $score_5_dec = number_format($score_5, 1);
            $stars = max(0, min(5, (int) round($score_5)));
            $votes_count = (int) ($item['votes_count'] ?? 0);
            ?>
            <article class="cg-card cg-profile-item">
                <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'medium_large', false, ['loading' => 'lazy', 'class' => 'cg-profile-thumb']); ?>
                <p>#<?php echo (int) $item['id']; ?> — <?php echo esc_html($item['status']); ?></p>
                <div class="cg-score-row">
                    <span class="cg-score-label"><?php echo $votes_count > 0 ? 'Puntaje:' : 'Puntaje: Sin votos'; ?></span>
                    <span class="cg-stars" aria-label="Puntaje <?php echo (int) $stars; ?> de 5">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="cg-star <?php echo $i <= $stars ? 'is-filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </span>
                    <?php if ($votes_count > 0): ?><small class="cg-score-value">(<?php echo esc_html($score_5_dec); ?>/5)</small><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

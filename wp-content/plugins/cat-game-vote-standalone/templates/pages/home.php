<?php
$event = $data['event'] ?? null;
$top_items = $data['top_items'] ?? [];
$latest_items = $data['latest_items'] ?? [];
$medals = ['🥇', '🥈', '🥉'];
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
?>
<section class="cg-home-hero">
    <h2>¡Compite con tu gato y gana!</h2>
    <p>Mientras más reacciones consigas, más subes en el ranking.</p>
</section>

<section class="cg-home-section">
    <article class="cg-card cg-home-guide" aria-labelledby="cg-home-guide-title">
        <h3 id="cg-home-guide-title">Cómo funciona el juego</h3>

        <div class="cg-home-guide-cards" role="list" aria-label="Pasos para participar en el juego">
            <article class="cg-home-guide-card" role="listitem">
                <div class="cg-home-guide-card__icon" aria-hidden="true">📸</div>
                <h4>Sube fotos de tu mascota</h4>
                <p>Comparte momentos de tu mascota en la comunidad.</p>
            </article>
            <article class="cg-home-guide-card" role="listitem">
                <div class="cg-home-guide-card__icon" aria-hidden="true">❤️</div>
                <h4>Reacciona por tus favoritas</h4>
                <p>Apoya a las mascotas que más te gusten.</p>
            </article>
            <article class="cg-home-guide-card" role="listitem">
                <div class="cg-home-guide-card__icon" aria-hidden="true">🏆</div>
                <h4>Participa en La Arena</h4>
                <p>Compite en eventos temáticos y sube en el ranking.</p>
            </article>
            <article class="cg-home-guide-card" role="listitem">
                <div class="cg-home-guide-card__icon" aria-hidden="true">🐾</div>
                <h4>Explora adopciones</h4>
                <p>Conoce mascotas que buscan una familia.</p>
            </article>
        </div>

        <p class="cg-home-guide-note">Los administradores pueden moderar publicaciones si no cumplen las normas. Dependiendo de la gravedad pueden aplicarse sanciones o eliminar publicaciones.</p>

        <div class="cg-home-guide-actions">
            <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>" data-open-event-rules="1">📜 Ver reglas completas</a>
            <a class="cg-btn" href="<?php echo esc_url(home_url('/catgame/about')); ?>">ℹ️ Acerca de nosotros</a>
        </div>
    </article>
</section>

<section class="cg-home-section">
    <h3>Top 3 por reacciones</h3>
    <?php if (empty($top_items)): ?>
        <p class="cg-empty-state">Aún no hay ranking disponible.</p>
    <?php else: ?>
        <div class="cg-home-top3">
            <?php foreach ($top_items as $index => $item): ?>
                <?php
                $title_label = CatGame_Submissions::title_label($item);
                $author = get_userdata((int) ($item['user_id'] ?? 0));
                $author_name = $author ? (string) $author->user_login : 'usuario';
                $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
                ?>
                <article class="cg-card cg-home-top3-item <?php echo $is_mine ? 'cg-is-mine' : ''; ?>">
                    <span class="cg-home-medal"><?php echo esc_html($medals[$index] ?? '🏅'); ?></span>
                    <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'medium_large', false, ['class' => 'cg-home-thumb', 'loading' => 'eager']); ?>
                    <strong class="cg-title"><?php echo esc_html($title_label); ?></strong>
                    <small class="cg-author">por @<?php echo esc_html($author_name); ?></small>
                    <?php if ($is_mine): ?><span class="cg-inline-badge">Tu publicación</span><?php endif; ?>
                    <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in(), (array) ($item['reaction_counts'] ?? []) ? ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)] : []); ?>
                    <small class="cg-location">📍 <?php echo esc_html(CatGame_Submissions::visual_label((string) ($item['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($item['country'] ?? ''))); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="cg-home-section">
    <h3>Últimas publicaciones</h3>
    <?php if (empty($latest_items)): ?>
        <p class="cg-empty-state">Aún no hay publicaciones recientes.</p>
    <?php else: ?>
        <div class="cg-home-latest" role="list">
            <?php foreach ($latest_items as $item): ?>
                <?php
                $title_label = CatGame_Submissions::title_label($item);
                $author = get_userdata((int) ($item['user_id'] ?? 0));
                $author_name = $author ? (string) $author->user_login : 'usuario';
                $is_event_publication = !empty($item['event_id']);
                $post_type_badge = $is_event_publication ? '🏆 La Arena' : '🐾 El Parque';
                $submission_url = home_url('/catgame/submission/' . (int) ($item['id'] ?? 0));
                ?>
                <article class="cg-card cg-home-latest-item" role="listitem">
                    <a href="<?php echo esc_url($submission_url); ?>" class="cg-home-latest-image-link" aria-label="Ver detalle de la publicación #<?php echo (int) ($item['id'] ?? 0); ?>">
                        <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'medium', false, ['loading' => 'lazy', 'class' => 'cg-home-latest-image']); ?>
                    </a>
                    <span class="cg-badge"><?php echo esc_html($post_type_badge); ?></span>
                    <strong class="cg-title"><?php echo esc_html($title_label); ?></strong>
                    <small class="cg-author">por @<?php echo esc_html($author_name); ?></small>
                    <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in(), (array) ($item['reaction_counts'] ?? []) ? ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)] : []); ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

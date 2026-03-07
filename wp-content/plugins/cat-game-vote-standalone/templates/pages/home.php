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
    <h3>Cómo funciona</h3>
    <p><strong>La Arena</strong>: espacio competitivo con eventos y ranking. <strong>El Parque</strong>: espacio libre para compartir mascotas.</p>
    <div class="cg-home-steps">
        <a class="cg-card cg-home-step-link" href="<?php echo esc_url(home_url('/catgame/upload')); ?>" aria-label="Ir a Subir"><strong>📷 Sube</strong><p>Publica la mejor foto de tu gato.</p></a>
        <a class="cg-card cg-home-step-link" href="<?php echo esc_url(home_url('/catgame/feed')); ?>" aria-label="Ir a Publicaciones"><strong>😻 Reacciona</strong><p>La comunidad reacciona a las publicaciones.</p></a>
        <a class="cg-card cg-home-step-link" href="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>" aria-label="Ir a Ranking"><strong>🏆 Gana</strong><p>Sube en el ranking y llega al top.</p></a>
    </div>
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

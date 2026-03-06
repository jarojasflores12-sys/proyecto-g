<?php
$item = $template_item ?? [];
$top3_positions = $top3_positions ?? [];
$current_user_id = (int) ($current_user_id ?? 0);
$item_tags = CatGame_Submissions::submission_tags($item);
$title_label = CatGame_Submissions::title_label($item);
$author = get_userdata((int) ($item['user_id'] ?? 0));
$author_name = $author ? (string) $author->user_login : 'usuario';
$author_profile_url = home_url('/catgame/user/' . rawurlencode(sanitize_user($author_name, true)));
$position = isset($top3_positions[(int) $item['id']]) ? (int) $top3_positions[(int) $item['id']] : 0;
$is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
$has_event = !empty($item['event_id']);
$post_type_badge_class = $has_event ? 'cg-post-type-badge cg-badge-event' : 'cg-post-type-badge cg-badge-free';
$post_type_badge_label = $has_event ? '🏆 Evento' : '🐾 Libre';
?>
<article class="cg-card <?php echo ($is_mine || $position > 0) ? 'cg-is-mine' : ''; ?>">
    <div class="cg-img-wrap">
        <div class="<?php echo esc_attr($post_type_badge_class); ?>"><?php echo esc_html($post_type_badge_label); ?></div>
        <?php echo wp_get_attachment_image(
            (int) $item['attachment_id'],
            'medium',
            false,
            [
                'class' => 'cg-img',
                'loading' => 'lazy',
                'alt' => 'Foto enviada al juego',
            ]
        ); ?>
        <div class="cg-skel cg-skel-img" aria-hidden="true"></div>
        <div class="cg-img-error" aria-hidden="true">No se pudo cargar la imagen</div>
    </div>

    <div class="cg-card-meta">
        <div class="catgame-card-head">
            <div class="catgame-card-head-left">
                <span class="cg-id-badge">#<?php echo (int) $item['id']; ?></span>
                <p class="cg-title"><?php echo esc_html($title_label); ?></p>
                <small class="cg-author">por <a href="<?php echo esc_url($author_profile_url); ?>">@<?php echo esc_html($author_name); ?></a></small>
                <?php if ($is_mine): ?><span class="cg-inline-badge">Tu publicación</span><?php endif; ?>
                <?php if ($position > 0): ?><span class="cg-inline-badge">Top 3 #<?php echo (int) $position; ?></span><?php endif; ?>
            </div>
            <div class="catgame-card-head-action">
                <button
                    type="button"
                    class="catgame-mini-action js-share-link"
                    data-url="<?php echo esc_url(home_url('/catgame/submission/' . (int) ($item['id'] ?? 0))); ?>"
                    data-share-title="Cat Game Vote"
                    data-share-text="Mira esta publicación en Cat Game Vote"
                >Compartir</button>
                <?php if ($is_mine && is_user_logged_in()): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-inline-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar publicación" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta publicación?">
                        <?php wp_nonce_field('catgame_delete_submission'); ?>
                        <input type="hidden" name="action" value="catgame_delete_submission">
                        <input type="hidden" name="submission_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                        <button type="submit" class="catgame-mini-action">Eliminar</button>
                    </form>
                <?php elseif (is_user_logged_in()): ?>
                    <?php echo class_exists('CatGame_Reports') ? str_replace('cg-report-btn', 'cg-report-btn catgame-mini-action', CatGame_Reports::report_button_html($item, $current_user_id)) : ''; ?>
                <?php endif; ?>
            </div>
        </div>
        <p class="cg-location">📍 <?php echo esc_html(CatGame_Submissions::visual_label((string) ($item['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($item['country'] ?? ''))); ?></p>
    </div>

    <?php if (!empty($item_tags)): ?>
        <div class="cg-chip-row" aria-label="Etiquetas de la publicación">
            <?php foreach ($item_tags as $tag): ?>
                <span class="cg-chip"><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($item['user_id'] ?? 0))); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php CatGame_Reactions::render_widget((int) $item['id'], is_user_logged_in(), ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)]); ?>
</article>

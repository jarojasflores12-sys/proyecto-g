<?php
$scope = $data['scope'] ?? 'global';
$country = $data['country'] ?? '';
$city = $data['city'] ?? '';
$items = $data['items'] ?? [];
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
?>
<section>
    <h2>Ranking</h2>
    <form method="get" action="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>" class="cg-form-inline">
        <label>Alcance
            <select name="scope">
                <option value="global" <?php selected($scope, 'global'); ?>>Global</option>
                <option value="country" <?php selected($scope, 'country'); ?>>País</option>
                <option value="city" <?php selected($scope, 'city'); ?>>Ciudad</option>
            </select>
        </label>
        <label>País <input type="text" name="country" value="<?php echo esc_attr($country); ?>"></label>
        <label>Ciudad <input type="text" name="city" value="<?php echo esc_attr($city); ?>"></label>
        <button type="submit">Filtrar</button>
    </form>

    <div class="cg-rank-list">
        <?php if (!$items): ?>
            <p class="cg-empty-state">Aún no hay ranking disponible. Cuando existan reacciones, aparecerá aquí.</p>
        <?php endif; ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php
            $title_label = CatGame_Submissions::title_label($item);
            $author = get_userdata((int) ($item['user_id'] ?? 0));
            $author_name = $author ? (string) $author->user_login : 'usuario';
            $author_profile_url = home_url('/catgame/user/' . rawurlencode(sanitize_user($author_name, true)));
            $position = isset($top3_positions[(int) $item['id']]) ? (int) $top3_positions[(int) $item['id']] : 0;
            $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
            ?>
            <article class="cg-card cg-rank-item <?php echo ($is_mine || $position > 0) ? 'cg-is-mine' : ''; ?>">
                <div class="catgame-card-head">
                    <div class="catgame-card-head-left">
                        <span class="cg-rank-badge">#<?php echo (int) $idx + 1; ?></span>
                        <div class="cg-rank-headings">
                            <p class="cg-rank-title"><?php echo esc_html($title_label); ?></p>
                            <small class="cg-author">por <a href="<?php echo esc_url($author_profile_url); ?>">@<?php echo esc_html($author_name); ?></a></small>
                            <div class="cg-rank-tags">
                                <?php if ($is_mine): ?><span class="cg-inline-badge">Tú</span><?php endif; ?>
                                <?php if ($position > 0): ?><span class="cg-inline-badge">Top 3</span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="catgame-card-head-action">
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

                <div class="cg-rank-thumb">
                    <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'medium_large', false, ['loading' => 'lazy']); ?>
                </div>

                <div class="cg-rank-meta">
                    <p class="cg-location">📍 <?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                    <div class="cg-rank-reactions">
                        <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in(), (array) ($item['reaction_counts'] ?? []) ? ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)] : []); ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php
$scope = $data['scope'] ?? 'global';
$country = $data['country'] ?? '';
$city = $data['city'] ?? '';
$items = $data['items'] ?? [];
$available_tags = $data['available_tags'] ?? [];
$selected_tags = $data['selected_tags'] ?? [];
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

        <fieldset class="cg-filter-tags">
            <legend>Etiquetas</legend>
            <?php foreach ($available_tags as $tag): ?>
                <label>
                    <input type="checkbox" name="tags[]" value="<?php echo esc_attr($tag); ?>" <?php checked(in_array($tag, $selected_tags, true)); ?>>
                    <?php echo esc_html(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <button type="submit">Filtrar</button>
    </form>

    <div class="cg-rank-list">
        <?php if (!$items): ?>
            <p class="cg-empty-state">Aún no hay ranking disponible. Cuando existan reacciones, aparecerá aquí.</p>
        <?php endif; ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php
            $title = trim((string) ($item['title'] ?? ''));
            $title_label = $title !== '' ? $title : 'Publicación #' . (int) $item['id'];
            $author = get_userdata((int) ($item['user_id'] ?? 0));
            $author_name = $author ? (string) $author->user_login : 'usuario';
            $position = isset($top3_positions[(int) $item['id']]) ? (int) $top3_positions[(int) $item['id']] : 0;
            $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
            ?>
            <article class="cg-card cg-rank-item <?php echo ($is_mine || $position > 0) ? 'cg-is-mine' : ''; ?>">
                <span class="cg-rank-badge">#<?php echo (int) $idx + 1; ?></span>
                <a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>" class="cg-rank-thumb">
                    <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'thumbnail', false, ['loading' => 'lazy']); ?>
                </a>
                <div class="cg-rank-meta">
                    <a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>" class="cg-rank-title"><?php echo esc_html($title_label); ?></a>
                    <small class="cg-author">por @<?php echo esc_html($author_name); ?></small>
                    <?php if ($is_mine): ?><span class="cg-inline-badge">Tú</span><?php endif; ?>
                    <?php if ($position > 0): ?><span class="cg-inline-badge">Top 3</span><?php endif; ?>
                    <p class="cg-location">📍 <?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                    <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in()); ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

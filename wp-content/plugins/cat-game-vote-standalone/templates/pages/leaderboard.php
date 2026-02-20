<?php
$scope = $data['scope'] ?? 'global';
$country = $data['country'] ?? '';
$city = $data['city'] ?? '';
$items = $data['items'] ?? [];
$available_tags = $data['available_tags'] ?? [];
$selected_tags = $data['selected_tags'] ?? [];
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
            <p class="cg-empty-state">Aún no hay ranking disponible. Cuando existan votos, aparecerá aquí.</p>
        <?php endif; ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php
            $title = trim((string) ($item['title'] ?? ''));
            $title_label = $title !== '' ? $title : 'Publicación #' . (int) $item['id'];
            $score_10 = (float) ($item['score_cached'] ?? 0);
            $stars = max(0, min(5, (int) round($score_10 / 2)));
            ?>
            <article class="cg-card cg-rank-item">
                <span class="cg-rank-badge">#<?php echo (int) $idx + 1; ?></span>
                <a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>" class="cg-rank-thumb">
                    <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'thumbnail', false, ['loading' => 'lazy']); ?>
                </a>
                <div class="cg-rank-meta">
                    <a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>" class="cg-rank-title"><?php echo esc_html($title_label); ?></a>
                    <p class="cg-location">📍 <?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                    <div class="cg-score-row">
                        <span class="cg-stars" aria-label="Puntaje <?php echo (int) $stars; ?> de 5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="cg-star <?php echo $i <= $stars ? 'is-filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </span>
                        <small class="cg-score-value">(<?php echo (int) $stars; ?>/5)</small>
                    </div>
                    <small>Votos: <?php echo (int) ($item['votes_count'] ?? 0); ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

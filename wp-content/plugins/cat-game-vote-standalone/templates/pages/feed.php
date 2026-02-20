<?php
$items = $data['submissions'] ?? [];
$feed_tags = $data['feed_tags'] ?? [];
$selected_tag = $data['selected_tag'] ?? '';
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
?>
<section>
    <h2>Publicaciones</h2>

    <form method="get" action="<?php echo esc_url(home_url('/catgame/feed')); ?>" class="cg-form-inline">
        <label>Etiqueta
            <select name="tag">
                <option value="">Todas</option>
                <?php foreach ($feed_tags as $tag): ?>
                    <option value="<?php echo esc_attr($tag); ?>" <?php selected($selected_tag, $tag); ?>>
                        <?php echo esc_html(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Filtrar</button>
    </form>

    <div class="cg-grid">
        <?php if (!$items): ?>
            <p class="cg-empty-state">Aún no hay publicaciones en este evento. Sé la primera persona en subir una foto.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php
            $item_tags = CatGame_Submissions::submission_tags($item);
            $votes_count = (int) ($item['votes_count'] ?? 0);
            $score_10 = (float) ($item['score_cached'] ?? 0);
            $stars = $votes_count > 0 ? max(0, min(5, (int) round($score_10 / 2))) : 0;
            $title = trim((string) ($item['title'] ?? ''));
            $title_label = $title !== '' ? $title : 'Publicación #' . (int) $item['id'];
            $author = get_userdata((int) ($item['user_id'] ?? 0));
            $author_name = $author ? (string) $author->user_login : 'usuario';
            $position = isset($top3_positions[(int) $item['id']]) ? (int) $top3_positions[(int) $item['id']] : 0;
            $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
            ?>
            <article class="cg-card <?php echo ($is_mine || $position > 0) ? 'cg-is-mine' : ''; ?>">
                <div class="cg-img-wrap">
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
                    <div class="cg-card-header">
                        <span class="cg-badge">#<?php echo (int) $item['id']; ?></span>
                        <p class="cg-title"><?php echo esc_html($title_label); ?></p>
                        <small class="cg-author">por @<?php echo esc_html($author_name); ?></small>
                        <?php if ($is_mine): ?><span class="cg-inline-badge">Tu publicación</span><?php endif; ?>
                        <?php if ($position > 0): ?><span class="cg-inline-badge">Top 3 #<?php echo (int) $position; ?></span><?php endif; ?>
                        <p class="cg-location">📍 <?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                    </div>
                    <div class="cg-score-row">
                        <span class="cg-score-label"><?php echo $votes_count > 0 ? 'Puntaje:' : 'Puntaje: sin votos'; ?></span>
                        <span class="cg-stars" aria-label="Puntaje <?php echo (int) $stars; ?> de 5">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="cg-star <?php echo $i <= $stars ? 'is-filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </span>
                        <?php if ($votes_count > 0): ?>
                            <small class="cg-score-value">(<?php echo (int) $stars; ?>/5)</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($item_tags)): ?>
                    <div class="cg-chip-row" aria-label="Etiquetas de la publicación">
                        <?php foreach ($item_tags as $tag): ?>
                            <span class="cg-chip"><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($item['user_id'] ?? 0))); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <a class="cg-cta" href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>">Ver detalle</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

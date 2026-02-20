<?php
$items = $data['submissions'] ?? [];
$feed_tags = $data['feed_tags'] ?? [];
$selected_tag = $data['selected_tag'] ?? '';
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
            $score_label = (int) $item['votes_count'] > 0
                ? esc_html(number_format((float) $item['score_cached'], 1)) . '/10'
                : 'sin votos';
            ?>
            <article class="cg-card">
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
                    <span class="cg-badge">#<?php echo (int) $item['id']; ?></span>
                    <p class="cg-location">📍 <?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                    <p class="cg-score <?php echo (int) $item['votes_count'] > 0 ? 'is-highlight' : 'is-muted'; ?>">Puntaje: <?php echo $score_label; ?></p>
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

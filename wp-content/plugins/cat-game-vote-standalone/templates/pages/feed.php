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
            $title_label = CatGame_Submissions::title_label($item);
            $author = get_userdata((int) ($item['user_id'] ?? 0));
            $author_name = $author ? (string) $author->user_login : 'usuario';
            $position = isset($top3_positions[(int) $item['id']]) ? (int) $top3_positions[(int) $item['id']] : 0;
            $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
            $is_event_submission = isset($item['event_id']) && (int) $item['event_id'] > 0;
            ?>
            <article class="cg-card <?php echo ($is_mine || $position > 0) ? 'cg-is-mine' : ''; ?>">
                <div class="cg-img-wrap">
                    <div class="cg-badge <?php echo $is_event_submission ? 'cg-badge-event' : 'cg-badge-free'; ?>"><?php echo $is_event_submission ? '🏆 Evento' : '🐾 Libre'; ?></div>
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
                        <span class="cg-id-badge">#<?php echo (int) $item['id']; ?></span>
                        <p class="cg-title"><?php echo esc_html($title_label); ?></p>
                        <small class="cg-author">por @<?php echo esc_html($author_name); ?></small>
                        <?php if ($is_mine): ?><span class="cg-inline-badge">Tu publicación</span><?php endif; ?>
                        <?php if ($position > 0): ?><span class="cg-inline-badge">Top 3 #<?php echo (int) $position; ?></span><?php endif; ?>
                        <p class="cg-location">📍 <?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                    </div>
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
        <?php endforeach; ?>
    </div>
</section>

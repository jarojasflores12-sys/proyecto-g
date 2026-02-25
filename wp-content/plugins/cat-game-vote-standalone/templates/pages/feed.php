<?php
$items = $data['submissions'] ?? [];
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
$feed_per_page = (int) ($data['feed_per_page'] ?? 20);
$feed_next_offset = (int) ($data['feed_next_offset'] ?? count($items));
$feed_has_more = !empty($data['feed_has_more']);
?>
<section>
    <h2>Publicaciones</h2>

    <div class="cg-grid" id="catgame-feed-list">
        <?php if (!$items): ?>
            <p class="cg-empty-state">Aún no hay publicaciones en este evento. Sé la primera persona en subir una foto.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php $template_item = $item; include CATGAME_PLUGIN_DIR . 'templates/partials/feed-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="cg-feed-more" data-feed-more="1" data-per-page="<?php echo (int) $feed_per_page; ?>" data-next-offset="<?php echo (int) $feed_next_offset; ?>" data-has-more="<?php echo $feed_has_more ? '1' : '0'; ?>">
        <button type="button" class="secondary" data-feed-more-btn="1" <?php echo $feed_has_more ? '' : 'hidden'; ?>>Cargar más</button>
        <p class="cg-feed-end" data-feed-end="1" <?php echo $feed_has_more ? 'hidden' : ''; ?>>No hay más publicaciones</p>
    </div>
</section>

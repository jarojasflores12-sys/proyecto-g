<?php
$items = $data['submissions'] ?? [];
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
$feed_per_page = (int) ($data['feed_per_page'] ?? 20);
$feed_next_offset = (int) ($data['feed_next_offset'] ?? count($items));
$feed_has_more = !empty($data['feed_has_more']);
$feed_filter = (string) ($data['feed_filter'] ?? 'all');
$feed_has_active_event = !empty($data['feed_has_active_event']);
$empty_messages = [
    'all' => 'Aún no hay publicaciones disponibles.',
    'event' => $feed_has_active_event ? 'No hay publicaciones de evento disponibles.' : 'No hay evento activo en este momento.',
    'free' => 'Aún no hay publicaciones en modo libre.',
];
$empty_message = $empty_messages[$feed_filter] ?? $empty_messages['all'];
?>
<section>
    <h2>Publicaciones</h2>

    <div class="cg-feed-tabs" data-feed-tabs="1" role="tablist" aria-label="Filtrar publicaciones">
        <button type="button" class="cg-feed-tab <?php echo $feed_filter === 'all' ? 'is-active' : ''; ?>" data-filter="all" role="tab" aria-selected="<?php echo $feed_filter === 'all' ? 'true' : 'false'; ?>">Todo</button>
        <button type="button" class="cg-feed-tab <?php echo $feed_filter === 'event' ? 'is-active' : ''; ?>" data-filter="event" role="tab" aria-selected="<?php echo $feed_filter === 'event' ? 'true' : 'false'; ?>">🏆 Evento</button>
        <button type="button" class="cg-feed-tab <?php echo $feed_filter === 'free' ? 'is-active' : ''; ?>" data-filter="free" role="tab" aria-selected="<?php echo $feed_filter === 'free' ? 'true' : 'false'; ?>">🐾 Libre</button>
    </div>

    <div class="cg-grid" id="catgame-feed-list">
        <?php if (!$items): ?>
            <p class="cg-empty-state" data-feed-empty="1"><?php echo esc_html($empty_message); ?></p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php $template_item = $item; include CATGAME_PLUGIN_DIR . 'templates/partials/feed-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="cg-feed-more" data-feed-more="1" data-per-page="<?php echo (int) $feed_per_page; ?>" data-next-offset="<?php echo (int) $feed_next_offset; ?>" data-has-more="<?php echo $feed_has_more ? '1' : '0'; ?>" data-current-filter="<?php echo esc_attr($feed_filter); ?>" data-has-active-event="<?php echo $feed_has_active_event ? '1' : '0'; ?>" data-empty-all="<?php echo esc_attr($empty_messages['all']); ?>" data-empty-event="<?php echo esc_attr($empty_messages['event']); ?>" data-empty-free="<?php echo esc_attr($empty_messages['free']); ?>">
        <button type="button" class="secondary" data-feed-more-btn="1" <?php echo $feed_has_more ? '' : 'hidden'; ?>>Cargar más</button>
        <p class="cg-feed-end" data-feed-end="1" <?php echo $feed_has_more ? 'hidden' : ''; ?>>No hay más publicaciones</p>
    </div>
</section>

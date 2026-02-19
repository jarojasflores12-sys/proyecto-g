<?php
$items = $data['submissions'] ?? [];
?>
<section>
    <h2>Feed del evento</h2>
    <div class="cg-grid">
        <?php if (!$items): ?>
            <p>No hay submissions todavía.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <article class="cg-card">
                <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'medium'); ?>
                <h3>#<?php echo (int) $item['id']; ?></h3>
                <p><?php echo esc_html($item['city'] . ', ' . $item['country']); ?></p>
                <p>Score: <?php echo (int) $item['votes_count'] > 0 ? esc_html(number_format((float) $item['score_cached'], 2)) : 'sin votos'; ?></p>
                <a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>">Ver detalle</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

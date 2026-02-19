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
                <?php $size_bytes = isset($item['image_size_bytes']) ? (int) $item['image_size_bytes'] : 0; ?>
                <?php if ($size_bytes > 0): ?>
                    <p>Tamaño: <?php echo esc_html(number_format($size_bytes / 1024, 2)); ?> KB</p>
                <?php endif; ?>
                <a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>">Ver detalle</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

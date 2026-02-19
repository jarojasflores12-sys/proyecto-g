<?php
if (!empty($data['requires_login'])): ?>
<p>Debes iniciar sesión para ver tu perfil.</p>
<?php return; endif;
$stats = $data['stats'] ?? ['total_submissions' => 0, 'best_score' => 0, 'avg_score' => 0];
$items = $data['items'] ?? [];
?>
<section>
    <h2>Mi perfil</h2>
    <ul>
        <li>Total submissions: <?php echo (int) $stats['total_submissions']; ?></li>
        <li>Mejor score: <?php echo esc_html(number_format((float) $stats['best_score'], 2)); ?></li>
        <li>Score promedio: <?php echo esc_html(number_format((float) $stats['avg_score'], 2)); ?></li>
    </ul>

    <h3>Mis submissions</h3>
    <div class="cg-grid">
        <?php if (!$items): ?>
            <p>Aún no tienes submissions.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <article class="cg-card">
                <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'thumbnail'); ?>
                <p>#<?php echo (int) $item['id']; ?> — <?php echo esc_html($item['status']); ?></p>
                <p>Score: <?php echo esc_html(number_format((float) $item['score_cached'], 2)); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

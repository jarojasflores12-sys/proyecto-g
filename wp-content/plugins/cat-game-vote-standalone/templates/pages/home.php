<?php
$event = $data['event'] ?? null;
?>
<section>
    <h2>Bienvenido al Cat Game</h2>
    <p>Compite con tu gato y vota en comunidad (sin IA).</p>
    <?php if ($event): ?>
        <p><strong>Evento activo:</strong> <?php echo esc_html($event['name']); ?></p>
    <?php else: ?>
        <p>No hay evento activo en este momento.</p>
    <?php endif; ?>
</section>

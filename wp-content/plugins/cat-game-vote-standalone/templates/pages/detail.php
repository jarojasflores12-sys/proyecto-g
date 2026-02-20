<?php
$submission = $data['submission'] ?? null;
if (!$submission):
?>
<p>Publicación no encontrada.</p>
<?php return; endif;
$tags = CatGame_Submissions::submission_tags($submission);
$title = trim((string) ($submission['title'] ?? ''));
$title_label = $title !== '' ? $title : 'Publicación #' . (int) $submission['id'];
?>
<section>
    <h2><?php echo esc_html($title_label); ?></h2>
    <p><span class="cg-badge">#<?php echo (int) $submission['id']; ?></span></p>
    <div class="cg-detail-image"><?php echo wp_get_attachment_image((int) $submission['attachment_id'], 'large'); ?></div>
    <p>Ubicación: <?php echo esc_html($submission['city'] . ', ' . $submission['country']); ?></p>
    <p>Puntaje: <?php echo (int) $submission['votes_count'] > 0 ? esc_html(number_format((float) $submission['score_cached'], 2)) : 'sin votos'; ?></p>
    <p>Votos: <?php echo (int) $submission['votes_count']; ?> (suma <?php echo (int) $submission['votes_sum']; ?>)</p>
    <?php $size_bytes = isset($submission['image_size_bytes']) ? (int) $submission['image_size_bytes'] : 0; ?>
    <p>Tamaño imagen: <?php echo $size_bytes > 0 ? esc_html(number_format($size_bytes / 1024, 2)) . ' KB' : 'N/D'; ?></p>

    <h3>Etiquetas</h3>
    <?php if (empty($tags)): ?>
        <p>Sin etiquetas.</p>
    <?php else: ?>
        <div class="cg-chip-row" aria-label="Etiquetas de la publicación">
            <?php foreach ($tags as $tag): ?>
                <span class="cg-chip"><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($submission['user_id'] ?? 0))); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (is_user_logged_in()): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form">
            <?php wp_nonce_field('catgame_vote'); ?>
            <input type="hidden" name="action" value="catgame_vote">
            <input type="hidden" name="submission_id" value="<?php echo (int) $submission['id']; ?>">
            <label>Vota (1-5)
                <select name="rating" required>
                    <option value="1">1 estrella</option>
                    <option value="2">2 estrellas</option>
                    <option value="3">3 estrellas</option>
                    <option value="4">4 estrellas</option>
                    <option value="5">5 estrellas</option>
                </select>
            </label>
            <button type="submit" class="cg-vote-submit">Enviar voto</button>
        </form>
    <?php else: ?>
        <p>Inicia sesión para votar.</p>
    <?php endif; ?>
</section>

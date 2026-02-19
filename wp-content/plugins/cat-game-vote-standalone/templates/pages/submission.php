<?php
$submission = $data['submission'] ?? null;
if (!$submission):
?>
<p>Submission no encontrada.</p>
<?php return; endif; ?>
<?php
$tags = CatGame_Submissions::submission_tags($submission);
$already_voted = !empty($data['already_voted']);
?>
<section>
    <h2>Submission #<?php echo (int) $submission['id']; ?></h2>
    <div class="cg-detail-image"><?php echo wp_get_attachment_image((int) $submission['attachment_id'], 'large'); ?></div>
    <p>Ubicación: <?php echo esc_html($submission['city'] . ', ' . $submission['country']); ?></p>
    <p>Score: <?php echo (int) $submission['votes_count'] > 0 ? esc_html(number_format((float) $submission['score_cached'], 2)) : 'sin votos'; ?></p>
    <p>Votos: <span id="catgame-votes-count"><?php echo (int) $submission['votes_count']; ?></span> (suma <span id="catgame-votes-sum"><?php echo (int) $submission['votes_sum']; ?></span>)</p>
    <?php $size_bytes = isset($submission['image_size_bytes']) ? (int) $submission['image_size_bytes'] : 0; ?>
    <p>Tamaño imagen: <?php echo $size_bytes > 0 ? esc_html(number_format($size_bytes / 1024, 2)) . ' KB' : 'N/D'; ?></p>

    <h3>Etiquetas</h3>
    <?php if (empty($tags)): ?>
        <p>Sin etiquetas.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($tags as $tag): ?>
                <li><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($submission['user_id'] ?? 0))); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (is_user_logged_in()): ?>
        <?php if ($already_voted): ?>
            <p id="catgame-already-voted" class="cg-alert cg-alert-success">✅ Ya votaste en esta foto.</p>
        <?php else: ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form" id="catgame-vote-form">
                <?php wp_nonce_field('catgame_vote'); ?>
                <input type="hidden" name="action" value="catgame_vote">
                <input type="hidden" name="submission_id" value="<?php echo (int) $submission['id']; ?>">
                <input type="hidden" name="rating" id="catgame-rating-value" value="0">

                <fieldset class="cg-rating" aria-label="Vota (1 a 5)">
                    <legend>Vota (1 a 5)</legend>
                    <div class="cg-rating-stars" role="radiogroup" aria-label="Selecciona una valoración">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="cg-star" data-rating="<?php echo $i; ?>" aria-label="<?php echo $i; ?> estrella<?php echo $i > 1 ? 's' : ''; ?>">★</button>
                        <?php endfor; ?>
                    </div>
                </fieldset>
                <p id="catgame-vote-error" class="cg-alert cg-alert-error" style="display:none;"></p>
                <button type="submit">Enviar voto</button>
            </form>
            <p id="catgame-already-voted" class="cg-alert cg-alert-success" style="display:none;">✅ Ya votaste en esta foto.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Debes iniciar sesión para votar.</p>
    <?php endif; ?>
</section>

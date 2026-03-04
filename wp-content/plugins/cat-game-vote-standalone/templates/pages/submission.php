<?php
$submission = $data['submission'] ?? null;
if (!$submission):
?>
<p>Publicación no encontrada.</p>
<?php return; endif;
$tags = CatGame_Submissions::submission_tags($submission);
$title_label = CatGame_Submissions::title_label($submission);
$current_user_id = (int) ($data['current_user_id'] ?? 0);
$is_mine = $current_user_id > 0 && (int) ($submission['user_id'] ?? 0) === $current_user_id;
$author = get_userdata((int) ($submission['user_id'] ?? 0));
$author_name = $author ? (string) $author->user_login : 'usuario';
$current_user_id = is_user_logged_in() ? get_current_user_id() : 0;
?>
<section>
    <h2><?php echo esc_html($title_label); ?></h2>
    <p><span class="cg-badge">#<?php echo (int) $submission['id']; ?></span></p>
    <p class="cg-author">Publicado por @<?php echo esc_html($author_name); ?></p>
    <?php if ($is_mine): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-inline-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar publicación" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta publicación?">
            <?php wp_nonce_field('catgame_delete_submission'); ?>
            <input type="hidden" name="action" value="catgame_delete_submission">
            <input type="hidden" name="submission_id" value="<?php echo (int) ($submission['id'] ?? 0); ?>">
            <button type="submit" class="cg-tag-delete">Eliminar mi publicación</button>
        </form>
    <?php endif; ?>
    <div class="cg-detail-image"><?php echo wp_get_attachment_image((int) $submission['attachment_id'], 'large'); ?></div>
    <p>Ubicación: <?php echo esc_html($submission['city'] . ', ' . $submission['country']); ?></p>
    <?php CatGame_Reactions::render_widget((int) ($submission['id'] ?? 0), is_user_logged_in(), ['reaction_counts' => (array) ($submission['reaction_counts'] ?? []), 'my_reaction' => ($submission['my_reaction'] ?? null)]); ?>
    <?php echo class_exists('CatGame_Reports') ? CatGame_Reports::report_button_html($submission, $current_user_id) : ''; ?>
    <?php echo class_exists('CatGame_Reports') ? CatGame_Reports::appeal_button_html($submission, $current_user_id) : ''; ?>

    <?php $size_bytes = isset($submission['image_size_bytes']) ? (int) $submission['image_size_bytes'] : 0; ?>
    <p>Tamaño imagen: <?php echo $size_bytes > 0 ? esc_html(number_format($size_bytes / 1024, 2)) . ' KB' : 'N/D'; ?></p>

    <h3>Etiquetas</h3>
    <?php if (empty($tags)): ?>
        <p>Sin etiquetas.</p>
    <?php else: ?>
        <div class="cg-chip-row cg-chip-row--detail" aria-label="Etiquetas de la publicación">
            <?php foreach ($tags as $tag): ?>
                <span class="cg-chip"><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($submission['user_id'] ?? 0))); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

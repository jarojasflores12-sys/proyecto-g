<?php
$submission = $data['submission'] ?? null;
if (!$submission):
?>
<p>Publicación no encontrada.</p>
<?php return; endif;
$tags = CatGame_Submissions::submission_tags($submission);
$title_label = CatGame_Submissions::title_label($submission);
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
$position = isset($top3_positions[(int) ($submission['id'] ?? 0)]) ? (int) $top3_positions[(int) ($submission['id'] ?? 0)] : 0;
$is_mine = $current_user_id > 0 && (int) ($submission['user_id'] ?? 0) === $current_user_id;
$author = get_userdata((int) ($submission['user_id'] ?? 0));
$author_name = $author ? (string) $author->user_login : 'usuario';
?>
<section>
    <h2><?php echo esc_html($title_label); ?></h2>
    <p><span class="cg-badge">#<?php echo (int) $submission['id']; ?></span></p>
    <p class="cg-author">Publicado por @<?php echo esc_html($author_name); ?></p>
    <?php if ($is_mine): ?><p><span class="cg-inline-badge">Tu publicación</span></p><?php endif; ?>
    <?php if ($position > 0): ?><p><span class="cg-inline-badge">Top 3 #<?php echo (int) $position; ?></span></p><?php endif; ?>
    <div class="cg-detail-image"><?php echo wp_get_attachment_image((int) $submission['attachment_id'], 'large'); ?></div>
    <p>Ubicación: <?php echo esc_html(CatGame_Submissions::visual_label((string) ($submission['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($submission['country'] ?? ''))); ?></p>
    <?php CatGame_Reactions::render_widget((int) ($submission['id'] ?? 0), is_user_logged_in(), ['reaction_counts' => (array) ($submission['reaction_counts'] ?? []), 'my_reaction' => ($submission['my_reaction'] ?? null)]); ?>


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

<?php
$not_found = !empty($data['adoption_not_found']);
$item = is_array($data['adoption'] ?? null) ? $data['adoption'] : null;
$adoption_notice = sanitize_key((string) ($_GET['adoption_notice'] ?? ''));
?>
<section class="cg-adoptions-page">
    <?php if ($not_found || !$item): ?>
        <p class="cg-alert cg-alert-error">Esta publicación de adopción ya no está disponible.</p>
        <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url(home_url('/catgame/adoptions')); ?>">← Volver a Adopciones</a>
    <?php else: ?>
        <?php
        $status = (string) ($item['status'] ?? 'active');
        $is_resolved = $status === 'resolved';
        $badge = $is_resolved ? '✅ Adoptado' : CatGame_Submissions::adoption_type_label((string) ($item['adoption_type'] ?? 'adoption'));
        $pet_name = CatGame_Submissions::visual_label((string) ($item['pet_name'] ?? 'Mascota'));
        $pet_type = CatGame_Submissions::visual_label((string) ($item['pet_type'] ?? ''));
        $gender = CatGame_Submissions::adoption_gender_label((string) ($item['pet_gender'] ?? 'male'));
        $age = CatGame_Submissions::visual_label((string) ($item['pet_age'] ?? ''));
        $city = CatGame_Submissions::visual_label((string) ($item['city'] ?? ''));
        $country = CatGame_Submissions::visual_label((string) ($item['country'] ?? ''));
        $can_mark_resolved = !$is_resolved && CatGame_Submissions::can_manage_adoption($item);
        ?>
        <?php if ($adoption_notice === 'resolved'): ?>
            <p class="cg-alert cg-alert-success">Publicación marcada como adoptada.</p>
        <?php elseif ($adoption_notice === 'invalid'): ?>
            <p class="cg-alert cg-alert-error">No se pudo procesar la acción solicitada.</p>
        <?php endif; ?>
        <article class="cg-card cg-adoption-detail">
            <span class="cg-inline-badge cg-adoption-badge <?php echo $is_resolved ? 'cg-adoption-badge--resolved' : ''; ?>"><?php echo esc_html($badge); ?></span>
            <div class="cg-adoption-detail-image-wrap">
                <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'large', false, ['class' => 'cg-adoption-detail-image', 'loading' => 'lazy']); ?>
            </div>
            <h2><?php echo esc_html($pet_name); ?></h2>
            <p class="cg-adoption-meta"><?php echo esc_html($pet_type !== '' ? $pet_type : 'Tipo no especificado'); ?> · <?php echo esc_html($gender); ?> · <?php echo esc_html($age); ?></p>
            <p class="cg-adoption-location">📍 <?php echo esc_html($city . ', ' . $country); ?></p>
            <h3>Descripción</h3>
            <p><?php echo nl2br(esc_html((string) ($item['description'] ?? ''))); ?></p>
            <?php if (!$is_resolved): ?>
                <h3>Contacto</h3>
                <p><?php echo nl2br(esc_html((string) ($item['contact'] ?? ''))); ?></p>
            <?php endif; ?>

            <?php if ($can_mark_resolved): ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('catgame_mark_adoption_resolved'); ?>
                    <input type="hidden" name="action" value="catgame_mark_adoption_resolved">
                    <input type="hidden" name="adoption_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                    <button class="cg-btn" type="submit">Marcar como adoptado</button>
                </form>
            <?php endif; ?>
        </article>
        <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url(home_url('/catgame/adoptions')); ?>">← Volver a Adopciones</a>
    <?php endif; ?>
</section>

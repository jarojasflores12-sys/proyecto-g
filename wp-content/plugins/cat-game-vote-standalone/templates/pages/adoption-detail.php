<?php
$not_found = !empty($data['adoption_not_found']);
$item = is_array($data['adoption'] ?? null) ? $data['adoption'] : null;
?>
<section class="cg-adoptions-page">
    <?php if ($not_found || !$item): ?>
        <p class="cg-alert cg-alert-error">Esta publicación de adopción ya no está disponible.</p>
        <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url(home_url('/catgame/adoptions')); ?>">← Volver a Adopciones</a>
    <?php else: ?>
        <?php
        $badge = CatGame_Submissions::adoption_type_label((string) ($item['adoption_type'] ?? 'adoption'));
        $pet_name = CatGame_Submissions::visual_label((string) ($item['pet_name'] ?? 'Mascota'));
        $pet_type = CatGame_Submissions::visual_label((string) ($item['pet_type'] ?? ''));
        $gender = CatGame_Submissions::adoption_gender_label((string) ($item['pet_gender'] ?? 'male'));
        $age = CatGame_Submissions::visual_label((string) ($item['pet_age'] ?? ''));
        $city = CatGame_Submissions::visual_label((string) ($item['city'] ?? ''));
        $country = CatGame_Submissions::visual_label((string) ($item['country'] ?? ''));
        ?>
        <article class="cg-card cg-adoption-detail">
            <span class="cg-inline-badge cg-adoption-badge"><?php echo esc_html($badge); ?></span>
            <div class="cg-adoption-detail-image-wrap">
                <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'large', false, ['class' => 'cg-adoption-detail-image', 'loading' => 'lazy']); ?>
            </div>
            <h2><?php echo esc_html($pet_name); ?></h2>
            <p class="cg-adoption-meta"><?php echo esc_html($pet_type !== '' ? $pet_type : 'Tipo no especificado'); ?> · <?php echo esc_html($gender); ?> · <?php echo esc_html($age); ?></p>
            <p class="cg-adoption-location">📍 <?php echo esc_html($city . ', ' . $country); ?></p>
            <h3>Descripción</h3>
            <p><?php echo nl2br(esc_html((string) ($item['description'] ?? ''))); ?></p>
            <h3>Contacto</h3>
            <p><?php echo nl2br(esc_html((string) ($item['contact'] ?? ''))); ?></p>
        </article>
        <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url(home_url('/catgame/adoptions')); ?>">← Volver a Adopciones</a>
    <?php endif; ?>
</section>

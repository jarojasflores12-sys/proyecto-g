<?php
$items = is_array($data['adoptions'] ?? null) ? $data['adoptions'] : [];
$adoption_created = !empty($data['adoption_created']);
$adoption_notice = sanitize_key((string) ($_GET['adoption_notice'] ?? ''));
?>
<section class="cg-adoptions-page">
    <header class="cg-adoptions-head">
        <h2>Adopciones</h2>
        <p>Espacio social para ayudar a mascotas que buscan familia o hogar temporal.</p>
        <a class="cg-btn" href="<?php echo esc_url(home_url('/catgame/adoptions/new')); ?>">+ Publicar en Adopciones</a>
    </header>

    <?php if ($adoption_created): ?>
        <p class="cg-alert cg-alert-success">Publicación de adopción creada correctamente.</p>
    <?php endif; ?>
    <?php if ($adoption_notice === 'resolved'): ?>
        <p class="cg-alert cg-alert-success">Publicación marcada como adoptada.</p>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <p class="cg-empty-state">Aún no hay publicaciones de adopciones.</p>
    <?php else: ?>
        <div class="cg-grid cg-adoptions-grid">
            <?php foreach ($items as $item): ?>
                <?php
                $detail_url = home_url('/catgame/adoptions/' . (int) ($item['id'] ?? 0));
                $is_resolved = (string) ($item['status'] ?? 'active') === 'resolved';
                $badge = $is_resolved ? '✅ Adoptado' : CatGame_Submissions::adoption_type_label((string) ($item['adoption_type'] ?? 'adoption'));
                $pet_name = CatGame_Submissions::visual_label((string) ($item['pet_name'] ?? 'Mascota'));
                $pet_type = CatGame_Submissions::visual_label((string) ($item['pet_type'] ?? ''));
                $gender = CatGame_Submissions::adoption_gender_label((string) ($item['pet_gender'] ?? 'male'));
                $age = CatGame_Submissions::visual_label((string) ($item['pet_age'] ?? ''));
                $city = CatGame_Submissions::visual_label((string) ($item['city'] ?? ''));
                $country = CatGame_Submissions::visual_label((string) ($item['country'] ?? ''));
                $description = wp_trim_words((string) ($item['description'] ?? ''), 20, '…');
                ?>
                <article class="cg-card cg-adoption-card">
                    <span class="cg-inline-badge cg-adoption-badge <?php echo $is_resolved ? 'cg-adoption-badge--resolved' : ''; ?>"><?php echo esc_html($badge); ?></span>
                    <a href="<?php echo esc_url($detail_url); ?>" class="cg-adoption-image-link" aria-label="Ver adopción de <?php echo esc_attr($pet_name); ?>">
                        <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'large', false, ['class' => 'cg-adoption-image', 'loading' => 'lazy']); ?>
                    </a>
                    <h3><?php echo esc_html($pet_name); ?></h3>
                    <p class="cg-adoption-meta"><?php echo esc_html($pet_type !== '' ? $pet_type : 'Tipo no especificado'); ?> · <?php echo esc_html($gender); ?> · <?php echo esc_html($age); ?></p>
                    <p class="cg-adoption-location">📍 <?php echo esc_html($city . ', ' . $country); ?></p>
                    <p class="cg-adoption-desc"><?php echo esc_html($description); ?></p>
                    <a class="cg-btn cg-btn--ghost" href="<?php echo esc_url($detail_url); ?>">Ver detalle</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

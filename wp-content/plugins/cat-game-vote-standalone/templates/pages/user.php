<?php
$user_not_found = !empty($data['user_not_found']);
$username = (string) ($data['public_profile_username'] ?? '');
$location = (array) ($data['public_profile_location'] ?? ['city' => '', 'country' => '']);
$active_items = (array) ($data['active_items'] ?? []);
$recent_items = (array) ($data['recent_items'] ?? []);
$viewer_logged_in = !empty($data['viewer_logged_in']);
$current_user_id = (int) ($data['viewer_user_id'] ?? 0);
$location_text = CatGame_Submissions::visual_label(trim((string) ($location['city'] ?? ''))) . ', ' . CatGame_Submissions::visual_label(trim((string) ($location['country'] ?? '')));
$location_text = trim($location_text, ' ,');
?>
<section>
    <?php if ($user_not_found): ?>
        <h2>Usuario no encontrado</h2>
        <p>No pudimos encontrar ese perfil público.</p>
        <a class="cg-cta" href="<?php echo esc_url(home_url('/catgame/feed')); ?>">Volver a publicaciones</a>
    <?php else: ?>
        <h2>Perfil público</h2>
        <div class="cg-card">
            <p class="cg-title">@<?php echo esc_html($username); ?></p>
            <p class="cg-location">📍 <?php echo esc_html($location_text !== '' ? $location_text : 'Ubicación no disponible'); ?></p>
        </div>

        <h3>Evento activo</h3>
        <div class="cg-grid">
            <?php if (empty($active_items)): ?>
                <p class="cg-empty-state">Aún no ha publicado en el evento activo.</p>
            <?php endif; ?>

            <?php foreach ($active_items as $item): ?>
                <?php
                $item_tags = CatGame_Submissions::submission_tags($item);
                $title_label = CatGame_Submissions::title_label($item);
                $author_profile_url = home_url('/catgame/user/' . rawurlencode(sanitize_user($username, true)));
                $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
                ?>
                <article class="cg-card <?php echo $is_mine ? 'cg-is-mine' : ''; ?>">
                    <div class="cg-img-wrap">
                        <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'medium', false, ['class' => 'cg-img', 'loading' => 'lazy', 'alt' => 'Foto de usuario']); ?>
                    </div>
                    <div class="cg-card-meta">
                        <div class="cg-card-header">
                            <span class="cg-badge">#<?php echo (int) ($item['id'] ?? 0); ?></span>
                            <p class="cg-title"><?php echo esc_html($title_label); ?></p>
                            <small class="cg-author">por <a href="<?php echo esc_url($author_profile_url); ?>">@<?php echo esc_html($username); ?></a></small>
                            <p class="cg-location">📍 <?php echo esc_html(CatGame_Submissions::visual_label((string) ($item['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($item['country'] ?? ''))); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($item_tags)): ?>
                        <div class="cg-chip-row" aria-label="Etiquetas de la publicación">
                            <?php foreach ($item_tags as $tag): ?>
                                <span class="cg-chip"><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($item['user_id'] ?? 0))); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php echo class_exists('CatGame_Reports') ? CatGame_Reports::report_button_html($item, $current_user_id) : ''; ?>
                    <?php
                    CatGame_Reactions::render_widget(
                        (int) ($item['id'] ?? 0),
                        $viewer_logged_in,
                        ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)],
                        $viewer_logged_in ? [] : ['readonly' => true, 'readonly_reason' => 'Inicia sesión para reaccionar']
                    );
                    ?>
                </article>
            <?php endforeach; ?>
        </div>

        <h3>Recientes (30 días)</h3>
        <div class="cg-grid">
            <?php if (empty($recent_items)): ?>
                <p class="cg-empty-state">No hay publicaciones recientes.</p>
            <?php endif; ?>

            <?php foreach ($recent_items as $item): ?>
                <?php
                $item_tags = CatGame_Submissions::submission_tags($item);
                $title_label = CatGame_Submissions::title_label($item);
                $author_profile_url = home_url('/catgame/user/' . rawurlencode(sanitize_user($username, true)));
                ?>
                <article class="cg-card">
                    <div class="cg-img-wrap">
                        <?php echo wp_get_attachment_image((int) ($item['attachment_id'] ?? 0), 'medium', false, ['class' => 'cg-img', 'loading' => 'lazy', 'alt' => 'Foto reciente de usuario']); ?>
                    </div>
                    <div class="cg-card-meta">
                        <div class="cg-card-header">
                            <span class="cg-badge">#<?php echo (int) ($item['id'] ?? 0); ?></span>
                            <p class="cg-title"><?php echo esc_html($title_label); ?></p>
                            <small class="cg-author">por <a href="<?php echo esc_url($author_profile_url); ?>">@<?php echo esc_html($username); ?></a></small>
                            <p class="cg-location">📍 <?php echo esc_html(CatGame_Submissions::visual_label((string) ($item['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($item['country'] ?? ''))); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($item_tags)): ?>
                        <div class="cg-chip-row" aria-label="Etiquetas de la publicación">
                            <?php foreach ($item_tags as $tag): ?>
                                <span class="cg-chip"><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, (int) ($item['user_id'] ?? 0))); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), $viewer_logged_in, ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)], ['readonly' => true, 'readonly_reason' => 'Evento finalizado']); ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

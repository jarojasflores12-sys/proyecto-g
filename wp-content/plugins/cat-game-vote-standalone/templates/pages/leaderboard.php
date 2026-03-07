<?php
$scope = $data['scope'] ?? 'global';
$country = $data['country'] ?? '';
$city = $data['city'] ?? '';
$countries = isset($data['countries']) && is_array($data['countries']) ? $data['countries'] : [];
$cities_by_country = isset($data['cities_by_country']) && is_array($data['cities_by_country']) ? $data['cities_by_country'] : [];
$city_options = ($country !== '' && isset($cities_by_country[$country]) && is_array($cities_by_country[$country])) ? $cities_by_country[$country] : [];
$items = $data['items'] ?? [];
$ranking_event = is_array($data['ranking_event'] ?? null) ? $data['ranking_event'] : null;
$has_competitive_event = !empty($data['has_competitive_event']);
$historical_winners = isset($data['historical_winners']) && is_array($data['historical_winners']) ? $data['historical_winners'] : [];
$top3_positions = $data['top3_positions'] ?? [];
$current_user_id = (int) ($data['current_user_id'] ?? 0);
?>
<section>
    <h2>Ranking</h2>
    <?php if ($has_competitive_event): ?>
        <p class="cg-upload-context"><strong>La Arena:</strong> <?php echo esc_html((string) ($ranking_event['name'] ?? 'La Arena competitiva')); ?></p>
        <p class="cg-file-picker-text"><strong>Vigencia:</strong> <?php echo esc_html((string) ($ranking_event['starts_at'] ?? '') . ' - ' . (string) ($ranking_event['ends_at'] ?? '')); ?></p>
        <form method="get" action="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>" class="cg-form-inline">
            <label>Alcance
                <select name="scope">
                    <option value="global" <?php selected($scope, 'global'); ?>>Global</option>
                    <option value="country" <?php selected($scope, 'country'); ?>>País</option>
                    <option value="city" <?php selected($scope, 'city'); ?>>Ciudad</option>
                </select>
            </label>
            <label>País
                <select name="country">
                    <option value="">Todos</option>
                    <?php foreach ($countries as $country_option): ?>
                        <option value="<?php echo esc_attr($country_option); ?>" <?php selected($country, $country_option); ?>><?php echo esc_html(CatGame_Submissions::visual_label((string) $country_option)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ciudad
                <select name="city" <?php echo $country === '' ? 'disabled' : ''; ?>>
                    <option value="">Todas</option>
                    <?php foreach ($city_options as $city_option): ?>
                        <option value="<?php echo esc_attr($city_option); ?>" <?php selected($city, $city_option); ?>><?php echo esc_html(CatGame_Submissions::visual_label((string) $city_option)); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Filtrar</button>
        </form>
    <?php else: ?>
        <p class="cg-empty-state">No hay una Arena competitiva activa en este momento.</p>
    <?php endif; ?>

    <div class="cg-rank-list">
        <?php if (!$items): ?>
            <p class="cg-empty-state">Aún no hay ranking disponible. Cuando existan reacciones, aparecerá aquí.</p>
        <?php endif; ?>

        <?php foreach ($items as $idx => $item): ?>
            <?php
            $title_label = CatGame_Submissions::title_label($item);
            $author = get_userdata((int) ($item['user_id'] ?? 0));
            $author_name = $author ? (string) $author->user_login : 'usuario';
            $author_profile_url = home_url('/catgame/user/' . rawurlencode(sanitize_user($author_name, true)));
            $position = isset($top3_positions[(int) $item['id']]) ? (int) $top3_positions[(int) $item['id']] : 0;
            $is_mine = $current_user_id > 0 && (int) ($item['user_id'] ?? 0) === $current_user_id;
            ?>
            <article class="cg-card cg-rank-item <?php echo ($is_mine || $position > 0) ? 'cg-is-mine' : ''; ?>">
                <div class="catgame-card-head">
                    <div class="catgame-card-head-left">
                        <span class="cg-rank-badge">#<?php echo (int) $idx + 1; ?></span>
                        <div class="cg-rank-headings">
                            <p class="cg-rank-title"><?php echo esc_html($title_label); ?></p>
                            <small class="cg-author">por <a href="<?php echo esc_url($author_profile_url); ?>">@<?php echo esc_html($author_name); ?></a></small>
                            <div class="cg-rank-tags">
                                <?php if ($is_mine): ?><span class="cg-inline-badge">Tú</span><?php endif; ?>
                                <?php if ($position > 0): ?><span class="cg-inline-badge">Top 3</span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="catgame-card-head-action">
                        <?php if ($is_mine && is_user_logged_in()): ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-inline-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar publicación" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta publicación?">
                                <?php wp_nonce_field('catgame_delete_submission'); ?>
                                <input type="hidden" name="action" value="catgame_delete_submission">
                                <input type="hidden" name="submission_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                                <button type="submit" class="catgame-mini-action">Eliminar</button>
                            </form>
                        <?php elseif (is_user_logged_in()): ?>
                            <?php echo class_exists('CatGame_Reports') ? str_replace('cg-report-btn', 'cg-report-btn catgame-mini-action', CatGame_Reports::report_button_html($item, $current_user_id)) : ''; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cg-rank-thumb">
                    <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'medium_large', false, ['loading' => 'lazy']); ?>
                </div>

                <div class="cg-rank-meta">
                    <p class="cg-location">📍 <?php echo esc_html(CatGame_Submissions::visual_label((string) ($item['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($item['country'] ?? ''))); ?></p>
                    <p class="cg-score">Total reacciones: <?php echo (int) ($item['total_reactions'] ?? 0); ?></p>
                    <div class="cg-rank-reactions">
                        <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in(), (array) ($item['reaction_counts'] ?? []) ? ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)] : []); ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="cg-history-section" aria-label="Ganadores anteriores">
        <h3>Ganadores anteriores</h3>

        <?php if (empty($historical_winners)): ?>
            <p class="cg-empty-state">Aún no hay eventos finalizados con ganadores.</p>
        <?php else: ?>
            <div class="cg-history-list">
                <?php foreach ($historical_winners as $history): ?>
                    <?php
                    $event_name = (string) ($history['event_name'] ?? 'La Arena');
                    $period = trim((string) ($history['starts_at'] ?? '') . ' - ' . (string) ($history['ends_at'] ?? ''));
                    $slots = ['first', 'second', 'third'];
                    $medals = ['first' => '🥇', 'second' => '🥈', 'third' => '🥉'];
                    ?>
                    <article class="cg-card cg-history-card">
                        <header class="cg-history-card__header">
                            <p class="cg-rank-title"><?php echo esc_html($event_name); ?></p>
                            <?php if ($period !== '-'): ?><p class="cg-history-period"><?php echo esc_html($period); ?></p><?php endif; ?>
                        </header>

                        <?php
                        $first_winner = is_array($history['winners']['first'] ?? null) ? $history['winners']['first'] : [];
                        $second_winner = is_array($history['winners']['second'] ?? null) ? $history['winners']['second'] : [];
                        $third_winner = is_array($history['winners']['third'] ?? null) ? $history['winners']['third'] : [];

                        $render_winner = static function (array $winner, string $slot, array $medals): void {
                            $submission_id = (int) ($winner['submission_id'] ?? 0);
                            $raw_title = trim((string) ($winner['title'] ?? ''));
                            $title = $raw_title !== '' ? CatGame_Submissions::visual_label($raw_title) : 'Sin título';
                            $title_class = $raw_title !== '' ? 'cg-history-link' : 'cg-history-link cg-history-link--fallback';
                            $attachment_id = (int) ($winner['attachment_id'] ?? 0);
                            $user_id = (int) ($winner['user_id'] ?? 0);
                            $author = $user_id > 0 ? get_userdata($user_id) : null;
                            $username = $author ? (string) $author->user_login : 'usuario';
                            $submission_url = $submission_id > 0 ? home_url('/catgame/submission/' . $submission_id) : '';
                            ?>
                            <article class="cg-history-slot cg-history-slot--<?php echo esc_attr($slot); ?>">
                                <p class="cg-history-medal"><?php echo esc_html($medals[$slot] ?? '🏅'); ?></p>
                                <?php if ($attachment_id > 0): ?>
                                    <?php if ($submission_url !== ''): ?>
                                        <a class="cg-history-thumb" href="<?php echo esc_url($submission_url); ?>">
                                            <?php echo wp_get_attachment_image($attachment_id, 'medium', false, ['loading' => 'lazy']); ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="cg-history-thumb">
                                            <?php echo wp_get_attachment_image($attachment_id, 'medium', false, ['loading' => 'lazy']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($submission_url !== ''): ?>
                                    <a class="<?php echo esc_attr($title_class); ?>" href="<?php echo esc_url($submission_url); ?>"><?php echo esc_html($title); ?></a>
                                <?php else: ?>
                                    <p class="<?php echo esc_attr($title_class); ?>"><?php echo esc_html($title); ?></p>
                                <?php endif; ?>
                                <small class="cg-author">@<?php echo esc_html($username); ?></small>
                            </article>
                            <?php
                        };
                        ?>

                        <div class="cg-history-podium">
                            <?php $render_winner($first_winner, 'first', $medals); ?>
                            <div class="cg-history-podium-secondary">
                                <?php $render_winner($second_winner, 'second', $medals); ?>
                                <?php $render_winner($third_winner, 'third', $medals); ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

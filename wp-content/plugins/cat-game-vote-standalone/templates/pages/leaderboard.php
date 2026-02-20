<?php
$scope = $data['scope'] ?? 'global';
$country = $data['country'] ?? '';
$city = $data['city'] ?? '';
$items = $data['items'] ?? [];
?>
<section>
    <h2>Clasificación</h2>
    <form method="get" action="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>" class="cg-form-inline">
        <label>Alcance
            <select name="scope">
                <option value="global" <?php selected($scope, 'global'); ?>>Global</option>
                <option value="country" <?php selected($scope, 'country'); ?>>País</option>
                <option value="city" <?php selected($scope, 'city'); ?>>Ciudad</option>
            </select>
        </label>
        <label>País <input type="text" name="country" value="<?php echo esc_attr($country); ?>"></label>
        <label>Ciudad <input type="text" name="city" value="<?php echo esc_attr($city); ?>"></label>
        <button type="submit">Filtrar</button>
    </form>

    <table class="cg-table">
        <thead><tr><th>#</th><th>Publicación</th><th>Lugar</th><th>Puntaje</th><th>Votos</th></tr></thead>
        <tbody>
        <?php if (!$items): ?>
            <tr><td colspan="5" class="cg-empty-state">Aún no hay ranking disponible. Cuando existan votos, aparecerá aquí.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $idx => $item): ?>
            <tr>
                <td><?php echo (int) $idx + 1; ?></td>
                <td><a href="<?php echo esc_url(home_url('/catgame/submission/' . (int) $item['id'])); ?>">#<?php echo (int) $item['id']; ?></a></td>
                <td><?php echo esc_html($item['city'] . ', ' . $item['country']); ?></td>
                <td><?php echo esc_html(number_format((float) $item['score_cached'], 2)); ?></td>
                <td><?php echo (int) $item['votes_count']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

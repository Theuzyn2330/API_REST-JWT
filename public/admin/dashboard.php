<?php

require __DIR__ . '/bootstrap.php';
adminRequireAuthentication();

$profileResponse = adminApiRequest('/api/profile', 'GET', null, $_SESSION['access_token']);
if ($profileResponse['status'] === 401) {
    adminDestroySession();
    header('Location: /admin/');
    exit;
}

$statistics = null;
$monitoring = [];
try {
    $dashboardController = new App\Controllers\Admin\DashboardController();
    $statistics = $dashboardController->statistics();
    $monitoring = $dashboardController->monitoring()['statistics'];
} catch (\Throwable $exception) {
    $statisticsError = 'Não foi possível consultar as estatísticas.';
}

$metrics = [
    'users' => ['Usuários', 'USER BASE'],
    'products' => ['Produtos', 'PRODUCT CATALOG'],
    'categories' => ['Categorias', 'CLASSIFICATION'],
    'prices' => ['Registros de preços', 'PRICE RECORDS'],
    'sources' => ['Fontes', 'DATA SOURCES'],
];
$apiResponded = $profileResponse['status'] > 0;
$pageTitle = 'Painel';
$activePage = 'dashboard';
require __DIR__ . '/views/layout_start.php';
?>
<section class="dashboard-status" aria-label="Status dos serviços">
    <div><span class="status-indicator<?= $apiResponded ? '' : ' is-offline' ?>"></span><span>API</span><strong><?= $apiResponded ? 'RESPONDENDO' : 'INDISPONÍVEL' ?></strong></div>
    <div><span class="status-indicator<?= $statistics !== null ? '' : ' is-offline' ?>"></span><span>DATABASE</span><strong><?= $statistics !== null ? 'CONECTADO' : 'INDISPONÍVEL' ?></strong></div>
    <span class="dashboard-updated">ATUALIZADO <?= date('H:i') ?></span>
</section>
<?php if (isset($statisticsError)): ?>
    <p class="dashboard-alert" role="status"><?= htmlspecialchars($statisticsError, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<section class="dashboard-metrics" aria-label="Estatísticas da aplicação">
    <?php foreach ($metrics as $key => [$label, $code]): ?>
        <?php $value = $statistics[$key] ?? null; ?>
        <article class="metric-item">
            <div class="metric-topline"><span><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></span><span><?= $statistics === null ? 'OFFLINE' : ($value === null ? 'PENDENTE' : 'TOTAL') ?></span></div>
            <h2><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="metric-bottomline">
                <strong class="metric-value"><?= $statistics === null ? 'N/D' : ($value === null ? '—' : number_format($value, 0, ',', '.')) ?></strong>
                <span><?= $statistics === null ? 'Banco indisponível' : ($value === null ? 'Estrutura ainda não criada' : 'registros') ?></span>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<section class="market-monitor" aria-labelledby="market-monitor-title">
    <div class="monitor-heading">
        <div>
            <p class="eyebrow">MARKET / LATEST 100 RECORDS</p>
            <h2 id="market-monitor-title">Monitoramento</h2>
        </div>
        <span class="monitor-unit-note">Valores por unidade normalizada</span>
    </div>
    <?php if ($statistics === null): ?>
        <p class="monitor-empty">Os preços não estão disponíveis enquanto o banco estiver desconectado.</p>
    <?php elseif ($monitoring === []): ?>
        <p class="monitor-empty">Ainda não há registros de preço para monitorar.</p>
    <?php else: ?>
        <div class="category-table-scroll">
            <table class="category-table monitor-table">
                <thead><tr><th>Produto</th><th>Local</th><th>Atual</th><th>Média</th><th>Mín / Máx</th><th>Variação</th><th>Fontes</th><th>Atualizado</th></tr></thead>
                <tbody>
                    <?php foreach ($monitoring as $item): ?>
                        <?php $variation = $item['variation']; ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) ($item['product_name'] ?? 'Produto ' . $item['product_id']), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars((string) ($item['location'] ?? 'Sem local'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong>R$ <?= number_format((float) $item['current_price'], 2, ',', '.') ?></strong><code> / <?= htmlspecialchars((string) $item['unit'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td>R$ <?= number_format((float) $item['average'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format((float) $item['minimum'], 2, ',', '.') ?> / <?= number_format((float) $item['maximum'], 2, ',', '.') ?></td>
                            <td class="variation-cell<?= $variation === null ? '' : ($variation > 0 ? ' is-up' : ($variation < 0 ? ' is-down' : '')) ?>">
                                <?= $variation === null ? '—' : (($variation > 0 ? '+' : '') . number_format((float) $variation, 2, ',', '.')) ?>
                                <?php if ($item['variation_percent'] !== null): ?><small><?= number_format((float) $item['variation_percent'], 2, ',', '.') ?>%</small><?php endif; ?>
                            </td>
                            <td><?= (int) $item['source_count'] ?></td>
                            <td><?= htmlspecialchars((string) $item['last_updated'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/views/layout_end.php'; ?>
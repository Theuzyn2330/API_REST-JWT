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
try {
    $statistics = (new App\Controllers\Admin\DashboardController())->statistics();
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
<?php require __DIR__ . '/views/layout_end.php'; ?>
<?php

require __DIR__ . '/bootstrap.php';
adminRequireAuthentication();

$readSetting = static function (string $key): ?string {
    $value = getenv($key);
    if (!is_string($value) || trim($value) === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
};

$projectRoot = dirname(__DIR__, 2);
$databaseStatus = 'Não verificado';
try {
    App\Database\Connection::getConnection();
    $databaseStatus = 'Conectado';
} catch (Throwable $exception) {
    $databaseStatus = 'Indisponível';
}

$jwtExpiration = 'Inválido';
try {
    $jwtConfiguration = require $projectRoot . '/config/jwt.php';
    $jwtExpiration = number_format((int) $jwtConfiguration['expiration'], 0, ',', '.') . ' segundos';
} catch (Throwable $exception) {
}

$settings = [
    'Ambiente' => $readSetting('APP_ENV') ?? 'Não definido',
    'URL da API' => $readSetting('APP_URL') ?? 'Não definida',
    'Arquivo .env' => is_file($projectRoot . '/.env') ? 'Configurado' : 'Não encontrado',
    'Banco de dados' => $databaseStatus,
    'Servidor MySQL' => $readSetting('DB_HOST') ?? 'Não definido',
    'Base de dados' => $readSetting('DB_DATABASE') ?? 'Não definida',
    'Expiração do token' => $jwtExpiration,
];

$pageTitle = 'Configurações';
$activePage = 'settings';
require __DIR__ . '/views/layout_start.php';
?>
<section class="category-list-section" aria-labelledby="settings-title">
    <div class="section-heading category-list-heading">
        <div><p class="eyebrow">RUNTIME / CONFIGURATION</p><h2 id="settings-title">Estado da aplicação</h2></div>
        <span class="product-status<?= $databaseStatus === 'Conectado' ? ' is-active' : ' is-inactive' ?>"><?= htmlspecialchars(strtoupper($databaseStatus), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="category-table-scroll">
        <table class="category-table">
            <thead><tr><th>Parâmetro</th><th>Valor atual</th></tr></thead>
            <tbody>
                <?php foreach ($settings as $label => $value): ?>
                    <tr><td><strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong></td><td><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="monitor-empty">Os valores são carregados do arquivo .env na raiz do projeto. Credenciais e segredos não são exibidos.</p>
</section>
<?php require __DIR__ . '/views/layout_end.php'; ?>

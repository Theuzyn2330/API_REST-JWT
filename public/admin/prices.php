<?php

require __DIR__ . '/bootstrap.php';
adminRequireAuthentication();

$message = null;
$messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals(adminCsrfToken(), $submittedToken)) {
        http_response_code(400);
        $message = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $payload = [
            'product_id' => is_string($_POST['product_id'] ?? null) ? $_POST['product_id'] : '',
            'source_id' => is_string($_POST['source_id'] ?? null) ? $_POST['source_id'] : '',
            'price' => is_string($_POST['price'] ?? null) ? str_replace(',', '.', $_POST['price']) : '',
            'unit' => is_string($_POST['unit'] ?? null) ? trim($_POST['unit']) : '',
            'location' => is_string($_POST['location'] ?? null) ? trim($_POST['location']) : '',
            'collected_at' => is_string($_POST['collected_at'] ?? null) ? trim(str_replace('T', ' ', $_POST['collected_at'])) : '',
        ];
        if ($payload['location'] === '') {
            $payload['location'] = null;
        }
        if ($payload['collected_at'] === '') {
            $payload['collected_at'] = null;
        } elseif (strlen($payload['collected_at']) === 16) {
            $payload['collected_at'] .= ':00';
        }

        $response = adminApiRequest('/api/prices', 'POST', $payload, $_SESSION['access_token']);
        if ($response['status'] === 201) {
            header('Location: /admin/prices.php?saved=1');
            exit;
        }
        if ($response['status'] === 401) {
            adminDestroySession();
            header('Location: /admin/');
            exit;
        }
        $message = $response['status'] === 422
            ? 'Revise os dados do preço e confira se produto e fonte existem.'
            : 'Não foi possível registrar o preço.';
    }
}
if (isset($_GET['saved'])) {
    $message = 'Preço registrado.';
    $messageType = 'success';
}

$productsResponse = adminApiRequest('/api/products?active=true', 'GET', null, $_SESSION['access_token']);
$sourcesResponse = adminApiRequest('/api/sources', 'GET', null, $_SESSION['access_token']);
$pricesResponse = adminApiRequest('/api/prices', 'GET', null, $_SESSION['access_token']);
foreach ([$productsResponse, $sourcesResponse, $pricesResponse] as $response) {
    if ($response['status'] === 401) {
        adminDestroySession();
        header('Location: /admin/');
        exit;
    }
}
$products = is_array($productsResponse['data']['data'] ?? null) ? $productsResponse['data']['data'] : [];
$sources = is_array($sourcesResponse['data']['data'] ?? null) ? array_values(array_filter(
    $sourcesResponse['data']['data'],
    static fn (array $source): bool => (bool) ($source['active'] ?? false)
)) : [];
$prices = is_array($pricesResponse['data']['data'] ?? null) ? $pricesResponse['data']['data'] : [];
if (in_array(500, [$productsResponse['status'], $sourcesResponse['status'], $pricesResponse['status']], true)) {
    $message = $message ?? 'Não foi possível carregar os dados. Verifique a conexão com a API e o banco.';
}

$pageTitle = 'Preços';
$activePage = 'prices';
require __DIR__ . '/views/layout_start.php';
?>
<div class="category-workspace">
    <section class="category-form-section" aria-labelledby="price-form-title">
        <div class="section-heading">
            <p class="eyebrow">PRICE RECORDS / NEW ENTRY</p>
            <h2 id="price-form-title">Registrar preço</h2>
        </div>
        <?php if ($products === [] || $sources === []): ?>
            <p class="product-prerequisite">Cadastre pelo menos um produto ativo e uma fonte ativa antes de registrar preços.</p>
        <?php endif; ?>
        <form class="category-form" action="/admin/prices.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <label for="price-product">Produto</label>
            <select id="price-product" name="product_id" required>
                <option value="">Selecionar</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $product['unit'], ENT_QUOTES, 'UTF-8') ?>)</option>
                <?php endforeach; ?>
            </select>
            <label for="price-source">Fonte</label>
            <select id="price-source" name="source_id" required>
                <option value="">Selecionar</option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?= (int) $source['id'] ?>"><?= htmlspecialchars((string) $source['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <label for="price-amount">Preço (R$)</label>
            <input id="price-amount" name="price" type="number" min="0.0001" step="0.0001" required>
            <label for="price-unit">Unidade</label>
            <input id="price-unit" name="unit" type="text" maxlength="50" placeholder="kg, litro, unidade" required>
            <label for="price-location">Local <span>OPCIONAL</span></label>
            <input id="price-location" name="location" type="text" maxlength="191">
            <label for="price-collected-at">Data da coleta <span>OPCIONAL</span></label>
            <input id="price-collected-at" name="collected_at" type="datetime-local">
            <button class="primary-action" type="submit"<?= $products === [] || $sources === [] ? ' disabled' : '' ?>>Registrar preço <span aria-hidden="true">&#8594;</span></button>
        </form>
    </section>

    <section class="category-list-section" aria-labelledby="price-list-title">
        <div class="section-heading category-list-heading">
            <div><p class="eyebrow">LATEST RECORDS / <?= str_pad((string) count($prices), 2, '0', STR_PAD_LEFT) ?></p><h2 id="price-list-title">Últimos preços</h2></div>
            <span class="category-count"><?= count($prices) ?> registros</span>
        </div>
        <?php if ($message !== null): ?>
            <p class="category-message is-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($prices === []): ?>
            <div class="category-empty">Nenhum preço registrado.</div>
        <?php else: ?>
            <div class="category-table-scroll">
                <table class="category-table">
                    <thead><tr><th>Produto</th><th>Fonte</th><th>Preço</th><th>Local</th><th>Coletado em</th></tr></thead>
                    <tbody>
                        <?php foreach ($prices as $price): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $price['product_name'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars((string) $price['unit'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string) $price['source_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><strong>R$ <?= number_format((float) $price['price'], 4, ',', '.') ?></strong></td>
                                <td><?= htmlspecialchars((string) ($price['location'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $price['collected_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/views/layout_end.php'; ?>

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
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'save';
        $productId = is_string($_POST['product_id'] ?? null) ? $_POST['product_id'] : '';
        if ($action === 'deactivate') {
            if (ctype_digit($productId) !== true || (int) $productId < 1) {
                http_response_code(422);
                $message = 'Produto inválido.';
            } else {
                $response = adminApiRequest('/api/products/' . $productId, 'DELETE', null, $_SESSION['access_token']);
                if ($response['status'] === 200) {
                    header('Location: /admin/products.php?deactivated=1');
                    exit;
                }
                if ($response['status'] === 401) {
                    adminDestroySession();
                    header('Location: /admin/');
                    exit;
                }
                $message = 'Não foi possível desativar o produto.';
            }
        } else {
            $payload = [
                'category_id' => is_string($_POST['category_id'] ?? null) ? $_POST['category_id'] : '',
                'name' => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '',
                'slug' => is_string($_POST['slug'] ?? null) ? trim($_POST['slug']) : '',
                'description' => is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '',
                'unit' => is_string($_POST['unit'] ?? null) ? trim($_POST['unit']) : '',
                'active' => is_string($_POST['active'] ?? null) ? $_POST['active'] : '0',
            ];
            $isUpdate = ctype_digit($productId) && (int) $productId > 0;
            $response = adminApiRequest(
                $isUpdate ? '/api/products/' . $productId : '/api/products',
                $isUpdate ? 'PUT' : 'POST',
                $payload,
                $_SESSION['access_token']
            );
            if (in_array($response['status'], [200, 201], true)) {
                header('Location: /admin/products.php?saved=1');
                exit;
            }
            if ($response['status'] === 401) {
                adminDestroySession();
                header('Location: /admin/');
                exit;
            }
            $message = $response['status'] === 409
                ? 'O slug já está em uso ou a categoria não existe.'
                : ($response['status'] === 422 ? 'Revise os campos do produto.' : 'Não foi possível salvar o produto.');
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'Produto salvo.';
    $messageType = 'success';
} elseif (isset($_GET['deactivated'])) {
    $message = 'Produto desativado.';
    $messageType = 'success';
}

$categoryResponse = adminApiRequest('/api/categories', 'GET', null, $_SESSION['access_token']);
$productResponse = adminApiRequest('/api/products', 'GET', null, $_SESSION['access_token']);
foreach ([$categoryResponse, $productResponse] as $apiResponse) {
    if ($apiResponse['status'] === 401) {
        adminDestroySession();
        header('Location: /admin/');
        exit;
    }
}
$categories = is_array($categoryResponse['data']['data'] ?? null) ? $categoryResponse['data']['data'] : [];
$products = is_array($productResponse['data']['data'] ?? null) ? $productResponse['data']['data'] : [];
if (($categoryResponse['status'] !== 200 || $productResponse['status'] !== 200) && $message === null) {
    $message = 'Não foi possível carregar os dados do catálogo.';
}

$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if ($search !== '') {
    $products = array_values(array_filter($products, static function (array $product) use ($search): bool {
        foreach (['name', 'slug', 'category_name'] as $field) {
            if (is_string($product[$field] ?? null) && mb_stripos($product[$field], $search) !== false) {
                return true;
            }
        }

        return false;
    }));
}

$editingId = is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '';
$editingProduct = null;
if ($editingId !== '' && ctype_digit($editingId)) {
    foreach ($products as $product) {
        if ((string) ($product['id'] ?? '') === $editingId) {
            $editingProduct = $product;
            break;
        }
    }
}

$pageTitle = 'Produtos';
$activePage = 'products';
require __DIR__ . '/views/layout_start.php';
?>
<div class="product-workspace">
    <section class="product-form-section" aria-labelledby="product-form-title">
        <div class="section-heading">
            <p class="eyebrow">CATÁLOGO / <?= $editingProduct === null ? 'NEW ITEM' : 'EDIT ITEM' ?></p>
            <h2 id="product-form-title"><?= $editingProduct === null ? 'Novo produto' : 'Editar produto' ?></h2>
        </div>
        <?php if ($categories === []): ?>
            <p class="product-prerequisite">Cadastre uma categoria antes de adicionar produtos.</p>
        <?php endif; ?>
        <form class="product-form" action="/admin/products.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="product_id" value="<?= (int) ($editingProduct['id'] ?? 0) ?: '' ?>">
            <label for="product-name">Nome</label>
            <input id="product-name" name="name" type="text" maxlength="191" value="<?= htmlspecialchars((string) ($editingProduct['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="product-category">Categoria</label>
            <select id="product-category" name="category_id" required>
                <option value="">Selecionar</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"<?= (int) ($editingProduct['category_id'] ?? 0) === (int) $category['id'] ? ' selected' : '' ?>><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <label for="product-slug">Slug <span>OPCIONAL</span></label>
            <input id="product-slug" name="slug" type="text" maxlength="191" value="<?= htmlspecialchars((string) ($editingProduct['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="gerado-a-partir-do-nome">
            <label for="product-unit">Unidade</label>
            <input id="product-unit" name="unit" type="text" maxlength="50" value="<?= htmlspecialchars((string) ($editingProduct['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="kg, litro, unidade" required>
            <label for="product-description">Descrição <span>OPCIONAL</span></label>
            <textarea id="product-description" name="description" rows="3" maxlength="5000"><?= htmlspecialchars((string) ($editingProduct['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <label class="product-active-toggle" for="product-active"><input id="product-active" name="active" type="checkbox" value="1"<?= !isset($editingProduct['active']) || (bool) $editingProduct['active'] ? ' checked' : '' ?>><span>Produto ativo</span></label>
            <button class="primary-action" type="submit" name="action" value="save"<?= $categories === [] ? ' disabled' : '' ?>><?= $editingProduct === null ? 'Adicionar produto' : 'Salvar alterações' ?><span aria-hidden="true">&#8594;</span></button>
        </form>
    </section>

    <section class="product-list-section" aria-labelledby="product-list-title">
        <div class="section-heading product-list-heading">
            <div>
                <p class="eyebrow">INVENTÁRIO / <?= str_pad((string) count($products), 2, '0', STR_PAD_LEFT) ?></p>
                <h2 id="product-list-title">Produtos</h2>
            </div>
            <span class="category-count"><?= count($products) ?> itens</span>
        </div>
        <form class="product-search" action="/admin/products.php" method="get">
            <label class="visually-hidden" for="product-search">Buscar produtos</label>
            <input id="product-search" name="q" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome, slug ou categoria">
            <button type="submit">Buscar</button>
        </form>
        <?php if ($message !== null): ?>
            <p class="category-message is-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($products === []): ?>
            <div class="category-empty">Nenhum produto cadastrado.</div>
        <?php else: ?>
            <div class="category-table-scroll">
                <table class="category-table product-table">
                    <thead><tr><th>Produto</th><th>Categoria</th><th>Unidade</th><th>Status</th><th><span class="visually-hidden">Ações</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string) ($product['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $product['unit'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="product-status<?= (bool) $product['active'] ? ' is-active' : ' is-inactive' ?>"><?= (bool) $product['active'] ? 'ATIVO' : 'INATIVO' ?></span></td>
                                <td>
                                    <div class="product-row-actions">
                                        <a class="product-edit-link" href="/admin/products.php?edit=<?= (int) $product['id'] ?>" aria-label="Editar <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" title="Editar">Editar</a>
                                        <?php if ((bool) $product['active']): ?>
                                            <form action="/admin/products.php" method="post" onsubmit="return confirm('Desativar este produto?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                                <button class="icon-action" type="submit" name="action" value="deactivate" aria-label="Desativar <?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" title="Desativar">&#215;</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/views/layout_end.php'; ?>
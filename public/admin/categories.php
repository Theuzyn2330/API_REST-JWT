<?php

require __DIR__ . '/bootstrap.php';
adminRequireAuthentication();

$message = null;
$messageType = 'error';
$submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(adminCsrfToken(), $submittedToken)) {
        http_response_code(400);
        $message = 'A sessão expirou. Atualize a página e tente novamente.';
    } elseif (($_POST['action'] ?? 'create') === 'delete') {
        $categoryId = is_string($_POST['category_id'] ?? null) ? $_POST['category_id'] : '';
        if (ctype_digit($categoryId) !== true || (int) $categoryId < 1) {
            http_response_code(422);
            $message = 'Categoria inválida.';
        } else {
            $apiResponse = adminApiRequest('/api/categories/' . $categoryId, 'DELETE', null, $_SESSION['access_token']);
            if ($apiResponse['status'] === 200) {
                header('Location: /admin/categories.php?deleted=1');
                exit;
            }
            if ($apiResponse['status'] === 401) {
                adminDestroySession();
                header('Location: /admin/');
                exit;
            }
            $message = $apiResponse['data']['error'] ?? 'Não foi possível remover a categoria.';
        }
    } else {
        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
        $apiResponse = adminApiRequest('/api/categories', 'POST', [
            'name' => $name,
            'description' => $description !== '' ? $description : null,
        ], $_SESSION['access_token']);

        if ($apiResponse['status'] === 201) {
            header('Location: /admin/categories.php?created=1');
            exit;
        }
        if ($apiResponse['status'] === 401) {
            adminDestroySession();
            header('Location: /admin/');
            exit;
        }
        http_response_code($apiResponse['status'] >= 400 ? $apiResponse['status'] : 503);
        $message = $apiResponse['data']['error'] ?? 'Não foi possível salvar a categoria.';
    }
}

if (isset($_GET['created'])) {
    $message = 'Categoria adicionada.';
    $messageType = 'success';
} elseif (isset($_GET['deleted'])) {
    $message = 'Categoria removida.';
    $messageType = 'success';
}

$listResponse = adminApiRequest('/api/categories', 'GET', null, $_SESSION['access_token']);
if ($listResponse['status'] === 401) {
    adminDestroySession();
    header('Location: /admin/');
    exit;
}
$categories = $listResponse['status'] === 200 && is_array($listResponse['data']['data'] ?? null)
    ? $listResponse['data']['data']
    : [];
if ($listResponse['status'] !== 200 && $message === null) {
    $message = 'Não foi possível carregar as categorias.';
}

$pageTitle = 'Categorias';
$activePage = 'categories';
require __DIR__ . '/views/layout_start.php';
?>
<div class="category-workspace">
    <section class="category-form-section" aria-labelledby="category-form-title">
        <div class="section-heading">
            <p class="eyebrow">CATÁLOGO / NEW ITEM</p>
            <h2 id="category-form-title">Nova categoria</h2>
        </div>
        <form class="category-form" action="/admin/categories.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <label for="category-name">Nome</label>
            <input id="category-name" name="name" type="text" maxlength="150" required>
            <label for="category-description">Descrição <span>OPCIONAL</span></label>
            <textarea id="category-description" name="description" rows="3" maxlength="5000"></textarea>
            <button class="primary-action" type="submit">Adicionar categoria <span aria-hidden="true">&#8594;</span></button>
        </form>
    </section>

    <section class="category-list-section" aria-labelledby="category-list-title">
        <div class="section-heading category-list-heading">
            <div>
                <p class="eyebrow">TAXONOMIA / <?= str_pad((string) count($categories), 2, '0', STR_PAD_LEFT) ?></p>
                <h2 id="category-list-title">Categorias</h2>
            </div>
            <span class="category-count"><?= count($categories) ?> itens</span>
        </div>

        <?php if ($message !== null): ?>
            <p class="category-message is-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($categories === []): ?>
            <div class="category-empty">Nenhuma categoria cadastrada.</div>
        <?php else: ?>
            <div class="category-table-scroll">
                <table class="category-table">
                    <thead><tr><th>Categoria</th><th>Slug</th><th>Atualizada</th><th><span class="visually-hidden">Ações</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($category['description'])): ?><span><?= htmlspecialchars((string) $category['description'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars((string) ($category['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars((string) ($category['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form action="/admin/categories.php" method="post" onsubmit="return confirm('Remover esta categoria?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="category_id" value="<?= (int) ($category['id'] ?? 0) ?>">
                                        <button class="icon-action" type="submit" name="action" value="delete" aria-label="Remover <?= htmlspecialchars((string) ($category['name'] ?? 'categoria'), ENT_QUOTES, 'UTF-8') ?>" title="Remover categoria">&#215;</button>
                                    </form>
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
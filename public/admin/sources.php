<?php

require __DIR__ . '/bootstrap.php';
adminRequireAuthentication();

$sourceTypes = [
    'api' => 'API',
    'manual' => 'Manual',
    'automated_collection' => 'Coleta automatizada',
    'external_database' => 'Banco externo',
];
$message = null;
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals(adminCsrfToken(), $submittedToken)) {
        http_response_code(400);
        $message = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : 'save';
        $sourceId = is_string($_POST['source_id'] ?? null) ? $_POST['source_id'] : '';
        if ($action === 'deactivate') {
            if (ctype_digit($sourceId) !== true || (int) $sourceId < 1) {
                http_response_code(422);
                $message = 'Fonte inválida.';
            } else {
                $response = adminApiRequest('/api/sources/' . $sourceId, 'DELETE', null, $_SESSION['access_token']);
                if ($response['status'] === 200) {
                    header('Location: /admin/sources.php?deactivated=1');
                    exit;
                }
                if ($response['status'] === 401) {
                    adminDestroySession();
                    header('Location: /admin/');
                    exit;
                }
                $message = 'Não foi possível desativar a fonte.';
            }
        } else {
            $payload = [
                'name' => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '',
                'type' => is_string($_POST['type'] ?? null) ? $_POST['type'] : '',
                'url' => is_string($_POST['url'] ?? null) ? trim($_POST['url']) : '',
                'active' => is_string($_POST['active'] ?? null) ? $_POST['active'] : '0',
            ];
            $isUpdate = ctype_digit($sourceId) && (int) $sourceId > 0;
            $response = adminApiRequest(
                $isUpdate ? '/api/sources/' . $sourceId : '/api/sources',
                $isUpdate ? 'PUT' : 'POST',
                $payload,
                $_SESSION['access_token']
            );
            if (in_array($response['status'], [200, 201], true)) {
                header('Location: /admin/sources.php?saved=1');
                exit;
            }
            if ($response['status'] === 401) {
                adminDestroySession();
                header('Location: /admin/');
                exit;
            }
            $message = $response['status'] === 422
                ? 'Revise os campos da fonte e a URL.'
                : ($response['status'] === 409 ? 'Já existe uma fonte com esse nome.' : 'Não foi possível salvar a fonte.');
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'Fonte salva.';
    $messageType = 'success';
} elseif (isset($_GET['deactivated'])) {
    $message = 'Fonte desativada.';
    $messageType = 'success';
}

$listResponse = adminApiRequest('/api/sources', 'GET', null, $_SESSION['access_token']);
if ($listResponse['status'] === 401) {
    adminDestroySession();
    header('Location: /admin/');
    exit;
}
$sources = is_array($listResponse['data']['data'] ?? null) ? $listResponse['data']['data'] : [];
if ($listResponse['status'] !== 200 && $message === null) {
    $message = 'Não foi possível carregar as fontes.';
}

$editingId = is_string($_GET['edit'] ?? null) ? $_GET['edit'] : '';
$editingSource = null;
foreach ($sources as $source) {
    if ($editingId !== '' && (string) ($source['id'] ?? '') === $editingId) {
        $editingSource = $source;
        break;
    }
}

$pageTitle = 'Fontes';
$activePage = 'sources';
require __DIR__ . '/views/layout_start.php';
?>
<div class="category-workspace">
    <section class="category-form-section" aria-labelledby="source-form-title">
        <div class="section-heading">
            <p class="eyebrow">DATA INPUT / <?= $editingSource === null ? 'NEW SOURCE' : 'EDIT SOURCE' ?></p>
            <h2 id="source-form-title"><?= $editingSource === null ? 'Nova fonte' : 'Editar fonte' ?></h2>
        </div>
        <form class="category-form" action="/admin/sources.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="source_id" value="<?= (int) ($editingSource['id'] ?? 0) ?: '' ?>">
            <label for="source-name">Nome</label>
            <input id="source-name" name="name" type="text" maxlength="191" value="<?= htmlspecialchars((string) ($editingSource['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="source-type">Tipo</label>
            <select id="source-type" name="type" required>
                <?php foreach ($sourceTypes as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= ($editingSource['type'] ?? 'api') === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <label for="source-url">URL <span>OPCIONAL</span></label>
            <input id="source-url" name="url" type="url" maxlength="2048" value="<?= htmlspecialchars((string) ($editingSource['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://">
            <label class="product-active-toggle" for="source-active"><input id="source-active" name="active" type="checkbox" value="1"<?= !isset($editingSource['active']) || (bool) $editingSource['active'] ? ' checked' : '' ?>><span>Fonte ativa</span></label>
            <button class="primary-action" type="submit" name="action" value="save"><?= $editingSource === null ? 'Adicionar fonte' : 'Salvar alterações' ?><span aria-hidden="true">&#8594;</span></button>
        </form>
    </section>

    <section class="category-list-section" aria-labelledby="source-list-title">
        <div class="section-heading category-list-heading">
            <div><p class="eyebrow">REGISTRY / <?= str_pad((string) count($sources), 2, '0', STR_PAD_LEFT) ?></p><h2 id="source-list-title">Fontes</h2></div>
            <span class="category-count"><?= count($sources) ?> itens</span>
        </div>
        <?php if ($message !== null): ?>
            <p class="category-message is-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($sources === []): ?>
            <div class="category-empty">Nenhuma fonte cadastrada.</div>
        <?php else: ?>
            <div class="category-table-scroll">
                <table class="category-table">
                    <thead><tr><th>Fonte</th><th>Tipo</th><th>Status</th><th>URL</th><th><span class="visually-hidden">Ações</span></th></tr></thead>
                    <tbody>
                        <?php foreach ($sources as $source): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $source['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($sourceTypes[$source['type']] ?? (string) $source['type'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="product-status<?= (bool) $source['active'] ? ' is-active' : ' is-inactive' ?>"><?= (bool) $source['active'] ? 'ATIVA' : 'INATIVA' ?></span></td>
                                <td><?= htmlspecialchars((string) ($source['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="product-row-actions">
                                        <a class="product-edit-link" href="/admin/sources.php?edit=<?= (int) $source['id'] ?>">Editar</a>
                                        <?php if ((bool) $source['active']): ?>
                                            <form action="/admin/sources.php" method="post" onsubmit="return confirm('Desativar esta fonte?')">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="source_id" value="<?= (int) $source['id'] ?>">
                                                <button class="icon-action" type="submit" name="action" value="deactivate" aria-label="Desativar <?= htmlspecialchars((string) $source['name'], ENT_QUOTES, 'UTF-8') ?>" title="Desativar">&#215;</button>
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
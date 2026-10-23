<?php

require __DIR__ . '/bootstrap.php';
adminRequireAuthentication();

if (($_SESSION['user']['role'] ?? null) !== 'admin') {
    http_response_code(403);
    exit('Acesso restrito a administradores.');
}

$message = null;
$messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals(adminCsrfToken(), $submittedToken)) {
        http_response_code(400);
        $message = 'A sessão expirou. Atualize a página e tente novamente.';
    } else {
        $response = adminApiRequest('/api/users', 'POST', [
            'name' => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '',
            'email' => is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '',
            'password' => is_string($_POST['password'] ?? null) ? $_POST['password'] : '',
            'role' => 'client',
        ], $_SESSION['access_token']);
        if ($response['status'] === 201) {
            header('Location: /admin/users.php?created=1');
            exit;
        }
        if ($response['status'] === 401) {
            adminDestroySession();
            header('Location: /admin/');
            exit;
        }
        $message = match ($response['status']) {
            409 => 'Já existe uma conta com este e-mail.',
            422 => 'Confira nome, e-mail e senha (mínimo de 8 caracteres).',
            default => 'Não foi possível criar o usuário.',
        };
    }
}
if (isset($_GET['created'])) {
    $message = 'Usuário criado com perfil de cliente.';
    $messageType = 'success';
}

$response = adminApiRequest('/api/users', 'GET', null, $_SESSION['access_token']);
if ($response['status'] === 401) {
    adminDestroySession();
    header('Location: /admin/');
    exit;
}
$users = is_array($response['data']['data'] ?? null) ? $response['data']['data'] : [];
if ($response['status'] !== 200 && $message === null) {
    $message = $response['status'] === 403
        ? 'Sua conta não tem permissão para listar usuários.'
        : 'Não foi possível carregar os usuários.';
}

$pageTitle = 'Usuários';
$activePage = 'users';
require __DIR__ . '/views/layout_start.php';
?>
<div class="category-workspace">
    <section class="category-form-section" aria-labelledby="user-form-title">
        <div class="section-heading">
            <p class="eyebrow">ACCESS CONTROL / NEW ACCOUNT</p>
            <h2 id="user-form-title">Novo usuário</h2>
        </div>
        <form class="category-form" action="/admin/users.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <label for="user-name">Nome</label>
            <input id="user-name" name="name" type="text" maxlength="150" autocomplete="name" required>
            <label for="user-email">E-mail</label>
            <input id="user-email" name="email" type="email" maxlength="191" autocomplete="email" required>
            <label for="user-password">Senha</label>
            <input id="user-password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <button class="primary-action" type="submit">Criar usuário <span aria-hidden="true">&#8594;</span></button>
        </form>
    </section>

    <section class="category-list-section" aria-labelledby="user-list-title">
        <div class="section-heading category-list-heading">
            <div><p class="eyebrow">REGISTERED ACCOUNTS / <?= str_pad((string) count($users), 2, '0', STR_PAD_LEFT) ?></p><h2 id="user-list-title">Usuários</h2></div>
            <span class="category-count"><?= count($users) ?> contas</span>
        </div>
        <?php if ($message !== null): ?>
            <p class="category-message is-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($users === []): ?>
            <div class="category-empty">Nenhum usuário encontrado.</div>
        <?php else: ?>
            <div class="category-table-scroll">
                <table class="category-table">
                    <thead><tr><th>Usuário</th><th>E-mail</th><th>Perfil</th><th>Criado em</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/views/layout_end.php'; ?>

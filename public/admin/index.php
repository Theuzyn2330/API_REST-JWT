<?php

require __DIR__ . '/bootstrap.php';

$loginError = null;
$email = '';
if (isset($_SESSION['access_token'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';

    if (!hash_equals(adminCsrfToken(), $submittedToken)) {
        http_response_code(400);
        $loginError = 'A sessão expirou. Atualize a página e tente novamente.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $password === '') {
        http_response_code(422);
        $loginError = 'Informe um e-mail e uma senha válidos.';
    } else {
        $apiResponse = adminApiRequest('/api/auth/login', 'POST', [
            'email' => $email,
            'password' => $password,
        ]);
        $data = $apiResponse['data'];
        if ($apiResponse['status'] === 200 && is_string($data['access_token'] ?? null)) {
            session_regenerate_id(true);
            $_SESSION['access_token'] = $data['access_token'];
            $_SESSION['token_expires_at'] = time() + max(1, (int) ($data['expires_in'] ?? 3600));
            $user = is_array($data['user'] ?? null) ? $data['user'] : [];
            $_SESSION['user'] = [
                'id' => (int) ($user['id'] ?? 0),
                'name' => is_string($user['name'] ?? null) ? $user['name'] : $email,
                'email' => is_string($user['email'] ?? null) ? $user['email'] : $email,
            ];
            header('Location: /admin/dashboard.php');
            exit;
        }

        $loginError = in_array($apiResponse['status'], [401, 422], true)
            ? 'E-mail ou senha inválidos.'
            : 'Não foi possível autenticar agora. Tente novamente mais tarde.';
        http_response_code($apiResponse['status'] === 401 ? 401 : 503);
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Entrar | Economy API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body class="login-page">
    <main class="login-layout">
        <aside class="login-context" aria-label="Economy API">
            <a class="brand-lockup" href="/admin/" aria-label="Economy API, início">
                <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
                <span>economy<span class="brand-period">.</span></span>
            </a>

            <div class="context-content">
                <p class="eyebrow">MARKET OPERATIONS <span>01 / 01</span></p>
                <h1>Economy<br>API</h1>
                <p class="context-caption">Admin console</p>

                <div class="market-graphic" aria-hidden="true">
                    <div class="graphic-heading"><span>MARKET SIGNAL</span><span>LIVE</span></div>
                    <div class="graphic-grid">
                        <span style="--bar-height: 34%"></span>
                        <span style="--bar-height: 52%"></span>
                        <span style="--bar-height: 43%"></span>
                        <span style="--bar-height: 68%"></span>
                        <span style="--bar-height: 57%"></span>
                        <span style="--bar-height: 83%"></span>
                        <span style="--bar-height: 73%"></span>
                        <span style="--bar-height: 100%"></span>
                    </div>
                    <div class="graphic-footer"><span>PRICE INDEX</span><span>ECONOMY / DATA</span></div>
                </div>
            </div>

            <p class="context-foot">DATA INFRASTRUCTURE <span>V 1.0</span></p>
        </aside>

        <section class="login-main" aria-labelledby="login-title">
            <div class="login-topline"><span class="status-indicator"></span> SYSTEM ACCESS <span class="topline-rule"></span> SECURE AREA</div>
            <div class="login-form-wrap">
                <p class="eyebrow form-eyebrow">ADMINISTRATION / SIGN IN</p>
                <h2 id="login-title">Acesse sua conta</h2>
                <p class="form-intro">Entre com suas credenciais administrativas.</p>

                <?php if ($loginError !== null): ?>
                    <p class="login-error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <form class="login-form" action="/admin/" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="username" placeholder="nome@empresa.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>

                    <div class="password-label-row">
                        <label for="password">Senha</label>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Sua senha" required>

                    <button type="submit">Entrar <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="login-note"><span aria-hidden="true">&#9679;</span> Acesso restrito a pessoas autorizadas</p>
            </div>
            <footer class="login-footer"><span>ECONOMY API</span><span>ADMIN CONSOLE</span></footer>
        </section>
    </main>
</body>
</html>
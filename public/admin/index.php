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

                <form class="login-form" action="/admin/" method="post">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" autocomplete="username" placeholder="nome@empresa.com" required>

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
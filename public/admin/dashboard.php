<?php

$pageTitle = 'Painel';
$activePage = 'dashboard';
require __DIR__ . '/views/layout_start.php';
?>
<section class="dashboard-empty" aria-label="Dados do painel">
    <div class="empty-state-mark" aria-hidden="true"><span></span><span></span><span></span></div>
    <p class="eyebrow">VISÃO GERAL <span>/ 01</span></p>
    <h2>Sem dados econômicos</h2>
    <p>Os indicadores aparecerão aqui quando os primeiros registros forem adicionados.</p>
</section>
<?php require __DIR__ . '/views/layout_end.php'; ?>
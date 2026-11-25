<?php
// menu dinamico: cada nivel tem seus itens [texto, link, icone]
// por enquanto so a identificacao da tela; as funcionalidades entram depois
$menus = [
    'ADM' => [
        ['Agendamentos', 'agendamentos_adm.php', 'fa-solid fa-calendar-days'],
        ['Relatórios', 'relatorios.php', 'fa-solid fa-chart-column'],
        ['Usuários', 'usuarios.php', 'fa-users'],
        ['Serviços', 'servicos.php', 'fa-solid fa-gear'],
        ['Sobre', 'sobre.php', "fa-solid fa-circle-info"],
    ],
    'FUNCIONARIO' => [
        ['Painel', 'painel_funcionario.php', 'fa-solid fa-gauge'],
        ['Agendamentos', 'agendamentos_funcionario.php', 'fa-solid fa-calendar-days'],
        ['Checklist', 'checklist_funcionario.php', 'fa-solid fa-list-check'],
        ['Histórico', 'historico_funcionario.php', 'fa-solid fa-clock-rotate-left'],
        ['Sobre', 'sobre.php', "fa-solid fa-circle-info"],
    ],
    'CLIENTE' => [
        ['Início',  'home.php',     'fa-user'],
        ['Serviços', 'servicos_cliente.php', 'fa-solid fa-wrench'],
        ['Meus Veículos', 'meus_veiculos.php', 'fa-solid fa-car'],
        ['Agendar', 'agendar.php', 'fa-solid fa-calendar-plus'],
        ['Histórico', 'historico.php', 'fa-solid fa-clock-rotate-left'],
        ['Sobre', 'sobre.php', "fa-solid fa-circle-info"],
    ],
];

$nivelMenu = $_SESSION['nivel'] ?? 'CLIENTE';
$itensMenu = $menus[$nivelMenu] ?? $menus['CLIENTE'];
?>
<nav class="nav-principal">
    <?php foreach ($itensMenu as [$texto, $link, $icone]): ?>
        <a href="<?= $link ?>"<?= basename($_SERVER['PHP_SELF']) === $link ? ' class="ativo"' : '' ?>><i class="fa-solid <?= $icone ?>"></i> <?= $texto ?></a>
    <?php endforeach; ?>
</nav>

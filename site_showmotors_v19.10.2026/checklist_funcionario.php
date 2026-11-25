<?php
// checklist de tarefas por atendimento - area do funcionario
// lista e salvamento feitos por js/funcionario_checklist.js via api_funcionario.php
include("verifica_acesso.php");
exigirNivel(['ADM', 'FUNCIONARIO']);

include("conexao.php");
include("funcoes_funcionario.php");

$temChecklist = checklistDisponivel($conn);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= tokenCsrf() ?>">
    <title>Checklist - Oficina Show Motors</title>

    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link rel="stylesheet" href="css/funcionario.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <header class="topo">

        <div class="lado-esquerdo">

            <h1 class="titulo">ShowMotors</h1>

            <?php include("menu.php"); ?>

        </div>

        <?php include("menu_usuario.php"); ?>

    </header>

    <main class="conteudo">

        <div class="cabecalho-secao">

            <h2>
                <i class="fa-solid fa-list-check"></i>
                Checklist dos Atendimentos
            </h2>

        </div>

        <?php if (!$temChecklist): ?>

            <div class="aviso-vazio">
                <i class="fa-solid fa-database"></i>
                <p>O checklist ainda não está disponível. Execute o script <strong>conexao/checklist_funcionario.sql</strong> no banco de dados.</p>
            </div>

        <?php else: ?>

            <div class="layout-checklist">

                <aside>
                    <div class="campo-pesquisa" style="width:100%; margin-bottom:14px;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="busca-atendimento" placeholder="Cliente, placa ou nº">
                    </div>
                    <p class="resumo-lista" id="resumo-atendimentos"></p>
                    <div class="lista-atendimentos-checklist" id="lista-atendimentos"></div>
                    <div id="paginacao"></div>
                </aside>

                <section class="card-historico" id="painel-checklist">
                    <div class="aviso-vazio" style="box-shadow:none;">
                        <i class="fa-solid fa-hand-pointer"></i>
                        <p>Selecione um atendimento para ver e marcar as tarefas.</p>
                    </div>
                </section>

            </div>

            <script src="js/funcionario_comum.js"></script>
            <script src="js/funcionario_checklist.js"></script>

        <?php endif; ?>

    </main>

</body>

</html>

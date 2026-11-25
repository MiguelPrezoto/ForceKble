<?php
// historico de atendimentos - area do funcionario
// a lista e carregada por js/funcionario_historico.js via api_funcionario.php
include("verifica_acesso.php");
exigirNivel(['ADM', 'FUNCIONARIO']);

include("funcoes_funcionario.php");
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= tokenCsrf() ?>">
    <title>Histórico de Atendimentos - Oficina Show Motors</title>

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
                <i class="fa-solid fa-clock-rotate-left"></i>
                Histórico de Atendimentos
            </h2>

        </div>

        <form class="barra-filtros-func" id="form-filtros" autocomplete="off">

            <div class="campo-pesquisa">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="filtro-busca" placeholder="Cliente, placa, veículo ou nº do agendamento">
            </div>

            <label>De
                <input type="date" id="filtro-inicio">
            </label>

            <label>Até
                <input type="date" id="filtro-fim">
            </label>

            <button type="reset" class="btn-limpar">
                <i class="fa-solid fa-xmark"></i> Limpar
            </button>

        </form>

        <div class="filtros-status" id="filtros-status">
            <button type="button" class="filtro-status ativo" data-filtro="">Todos</button>
            <?php foreach ($ROTULOS_STATUS as $chave => $rotulo): ?>
                <button type="button" class="filtro-status" data-filtro="<?= $chave ?>"><?= $rotulo ?></button>
            <?php endforeach; ?>
        </div>

        <p class="resumo-lista" id="resumo-lista"></p>

        <div class="lista-historico" id="lista-historico"></div>

        <div id="paginacao"></div>

    </main>

    <script src="js/funcionario_comum.js"></script>
    <script src="js/funcionario_historico.js"></script>

</body>

</html>

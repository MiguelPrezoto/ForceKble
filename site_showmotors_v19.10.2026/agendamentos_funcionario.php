<?php
// agendamentos - area do funcionario
// a lista e as acoes sao carregadas por js/funcionario_agendamentos.js via api_funcionario.php
include("verifica_acesso.php");
exigirNivel(['ADM', 'FUNCIONARIO']);

include("regras_agendamento.php");
include("funcoes_funcionario.php");

date_default_timezone_set('America/Sao_Paulo');

$hoje   = date('Y-m-d');
$limite = date('Y-m-d', strtotime("+$DIAS_ANTECEDENCIA_MAX days"));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= tokenCsrf() ?>">
    <title>Agendamentos - Oficina Show Motors</title>

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
                <i class="fa-solid fa-calendar-days"></i>
                Agendamentos
            </h2>

            <button type="button" class="botao-padrao" id="btn-novo-agendamento">
                <i class="fa-solid fa-calendar-plus"></i>
                Novo Agendamento
            </button>

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

            <button type="button" class="btn-acao" id="btn-hoje">
                <i class="fa-solid fa-calendar-day"></i> Hoje
            </button>

            <button type="button" class="btn-acao" id="btn-semana">
                <i class="fa-solid fa-calendar-week"></i> Próximos 7 dias
            </button>

            <button type="reset" class="btn-limpar">
                <i class="fa-solid fa-xmark"></i> Limpar
            </button>

        </form>

        <div class="filtros-status" id="filtros-status">
            <button type="button" class="filtro-status ativo" data-filtro="ATIVOS">Em aberto</button>
            <button type="button" class="filtro-status" data-filtro="">Todos</button>
            <?php foreach ($ROTULOS_STATUS as $chave => $rotulo): ?>
                <button type="button" class="filtro-status" data-filtro="<?= $chave ?>"><?= $rotulo ?></button>
            <?php endforeach; ?>
        </div>

        <p class="resumo-lista" id="resumo-lista"></p>

        <div class="lista-historico" id="lista-agendamentos"></div>

        <div id="paginacao"></div>

    </main>

    <!-- modal: mesmo formulario para cadastrar e editar -->
    <div class="modal-overlay" id="modal-agendamento">
        <div class="modal-caixa larga">
            <div class="modal-cabecalho">
                <h2 id="titulo-modal-agendamento"><i class="fa-solid fa-calendar-plus"></i> Novo Agendamento</h2>
                <button type="button" class="modal-fechar" id="btn-fechar-modal" aria-label="Fechar">&times;</button>
            </div>

            <form id="form-agendamento" autocomplete="off">
                <input type="hidden" id="ag-id" value="">

                <p class="aviso-modal" id="aviso-em-andamento" hidden>
                    <i class="fa-solid fa-lock"></i>
                    Atendimento em andamento: cliente, veículo, data e horário não podem ser alterados.
                </p>

                <div class="linha-dupla">
                    <div>
                        <label for="ag-cliente">Cliente</label>
                        <select id="ag-cliente" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div>
                        <label for="ag-carro">Veículo</label>
                        <select id="ag-carro" required disabled>
                            <option value="">Selecione o cliente</option>
                        </select>
                    </div>
                </div>

                <label>Serviços</label>
                <div class="lista-servicos-escolha" id="ag-servicos"></div>

                <div class="linha-dupla">
                    <div>
                        <label for="ag-data">Data</label>
                        <input type="date" id="ag-data" min="<?= $hoje ?>" max="<?= $limite ?>" required>
                    </div>
                    <div>
                        <label for="ag-entrega">Entrega prevista (opcional)</label>
                        <input type="datetime-local" id="ag-entrega">
                    </div>
                </div>

                <label>Horário</label>
                <p class="dica" id="aviso-horarios" style="margin: 0 0 10px;">Escolha a data para ver os horários livres.</p>
                <div class="grade-horarios" id="ag-horarios"></div>

                <label for="ag-descricao">Observações</label>
                <textarea id="ag-descricao" maxlength="256" rows="3" placeholder="Ex.: barulho ao frear, luz do painel acesa..."></textarea>

                <div class="resumo-agendamento">
                    <div class="total">
                        Valor estimado
                        <strong id="ag-total">R$ 0,00</strong>
                    </div>

                    <button type="submit" class="botao-padrao" id="btn-salvar-agendamento">
                        <i class="fa-solid fa-check"></i>
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/funcionario_comum.js"></script>
    <script src="js/funcionario_agendamentos.js"></script>

</body>

</html>

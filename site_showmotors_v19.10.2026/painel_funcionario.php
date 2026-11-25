<?php
// painel principal da area do funcionario
include("verifica_acesso.php");
exigirNivel(['ADM', 'FUNCIONARIO']);

include("conexao.php");
include("regras_agendamento.php");
include("funcoes_funcionario.php");

date_default_timezone_set('America/Sao_Paulo');

$hoje  = date('Y-m-d');
$agora = date('Y-m-d H:i:s');

// indicadores
$stmt = $conn->prepare(
    "SELECT
        SUM(DATE(data_hora_servico) = ? AND status <> 'CANCELADO')        AS hoje,
        SUM(status = 'EM_ANDAMENTO')                                     AS em_andamento,
        SUM(status = 'AGENDADO' AND data_hora_servico >= ?)              AS proximos,
        SUM(status = 'AGENDADO' AND data_hora_servico < ?)               AS aguardando
     FROM agendamento"
);
$stmt->bind_param("sss", $hoje, $agora, $agora);
$stmt->execute();
$ind = array_map('intval', $stmt->get_result()->fetch_assoc());
$stmt->close();

$temChecklist = checklistDisponivel($conn);
$tarefasPendentes = [];
$qtdTarefasPendentes = 0;

if ($temChecklist) {
    $qtdTarefasPendentes = (int) $conn->query(
        "SELECT COUNT(*) AS qtd FROM checklist_item ci
         JOIN agendamento a ON a.id_agendamento = ci.id_agendamento
         WHERE ci.concluido = 0 AND a.status IN ('AGENDADO', 'EM_ANDAMENTO')"
    )->fetch_assoc()['qtd'];

    $tarefasPendentes = $conn->query(
        "SELECT ci.descricao, a.id_agendamento, a.data_hora_servico, a.status,
                c.marca, c.modelo, c.placa
         FROM checklist_item ci
         JOIN agendamento a ON a.id_agendamento = ci.id_agendamento
         LEFT JOIN carro c ON c.id_carro = a.id_carro
         WHERE ci.concluido = 0 AND a.status IN ('AGENDADO', 'EM_ANDAMENTO')
         ORDER BY a.status = 'EM_ANDAMENTO' DESC, a.data_hora_servico, ci.ordem, ci.id_item
         LIMIT 8"
    )->fetch_all(MYSQLI_ASSOC);
}

$agendaHoje = buscarAgendamentos($conn, [
    'inicio' => $hoje,
    'fim'    => $hoje,
    'status' => ['AGENDADO', 'EM_ANDAMENTO', 'CONCLUIDO'],
    'ordem'  => 'asc',
])['lista'];

$emAndamento = buscarAgendamentos($conn, ['status' => 'EM_ANDAMENTO', 'ordem' => 'asc'], 8)['lista'];

function veiculoTexto($a)
{
    return $a['marca'] !== null
        ? htmlspecialchars($a['marca'] . ' ' . $a['modelo'] . ' (' . $a['placa'] . ')')
        : 'Veículo removido';
}

function progressoHtml($feitos, $total)
{
    if (!$total) {
        return '<div class="progresso"><i class="fa-solid fa-list-check"></i> Checklist sem tarefas</div>';
    }
    $pct = (int) round($feitos / $total * 100);
    return '<div class="progresso' . ($feitos === $total ? ' completo' : '') . '">'
        . '<i class="fa-solid fa-list-check"></i>'
        . '<div class="progresso-barra"><span style="width:' . $pct . '%"></span></div>'
        . "<span>$feitos/$total tarefas</span></div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Oficina Show Motors</title>

    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link rel="stylesheet" href="css/funcionario.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
                <i class="fa-solid fa-gauge"></i>
                Olá, <?= htmlspecialchars($_SESSION['usuario']) ?>!
            </h2>

            <a href="agendamentos_funcionario.php?novo=1" class="botao-padrao">
                <i class="fa-solid fa-calendar-plus"></i>
                Novo Agendamento
            </a>

        </div>

        <?php if (!$temChecklist): ?>
            <div class="aviso-modal">
                <i class="fa-solid fa-triangle-exclamation"></i>
                O checklist ainda não está disponível: execute o script <strong>conexao/checklist_funcionario.sql</strong> no banco de dados.
            </div>
        <?php endif; ?>

        <?php if ($ind['aguardando'] > 0): ?>
            <a class="proximo-agendamento" href="agendamentos_funcionario.php?status=AGENDADO&ate=hoje" style="text-decoration:none; color:inherit;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong><?= $ind['aguardando'] ?> agendamento(s) com horário vencido aguardando início</strong>
                    <p>Inicie o atendimento ou cancele os que não compareceram.</p>
                </div>
            </a>
        <?php endif; ?>

        <div class="grade-indicadores">

            <a class="card-indicador" href="agendamentos_funcionario.php?periodo=hoje">
                <div class="icone-indicador"><i class="fa-solid fa-calendar-day"></i></div>
                <div>
                    <p class="titulo-indicador">Agendamentos hoje</p>
                    <h3><?= $ind['hoje'] ?></h3>
                </div>
            </a>

            <a class="card-indicador" href="agendamentos_funcionario.php?status=EM_ANDAMENTO">
                <div class="icone-indicador"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <div>
                    <p class="titulo-indicador">Em andamento</p>
                    <h3><?= $ind['em_andamento'] ?></h3>
                </div>
            </a>

            <a class="card-indicador" href="agendamentos_funcionario.php?status=AGENDADO">
                <div class="icone-indicador"><i class="fa-solid fa-calendar-check"></i></div>
                <div>
                    <p class="titulo-indicador">Próximos agendados</p>
                    <h3><?= $ind['proximos'] ?></h3>
                </div>
            </a>

            <a class="card-indicador<?= $qtdTarefasPendentes > 0 ? ' alerta' : '' ?>" href="checklist_funcionario.php">
                <div class="icone-indicador"><i class="fa-solid fa-list-check"></i></div>
                <div>
                    <p class="titulo-indicador">Tarefas pendentes</p>
                    <h3><?= $qtdTarefasPendentes ?></h3>
                </div>
            </a>

        </div>

        <div class="grade-painel">

            <section class="bloco-relatorio">
                <h3 class="titulo-bloco"><i class="fa-solid fa-calendar-day"></i> Agenda de hoje</h3>

                <?php if (!$agendaHoje): ?>
                    <p class="vazio-painel">Nenhum atendimento marcado para hoje.</p>
                <?php else: ?>
                    <ul class="lista-painel">
                        <?php foreach ($agendaHoje as $a): ?>
                            <li>
                                <strong class="hora"><?= date('H:i', strtotime($a['data_hora_servico'])) ?></strong>
                                <div class="info">
                                    <strong><?= htmlspecialchars($a['nome_cliente'] ?: ($a['nome_usuario'] ?? 'Cliente removido')) ?></strong>
                                    <span><?= veiculoTexto($a) ?> &middot; <?= htmlspecialchars(implode(', ', array_column($a['servicos'], 'nome'))) ?></span>
                                    <?php if ($a['status'] === 'EM_ANDAMENTO'): ?>
                                        <?= progressoHtml($a['checklist_feitos'], $a['checklist_total']) ?>
                                    <?php endif; ?>
                                </div>
                                <span class="badge-status <?= strtolower($a['status']) ?>"><?= $ROTULOS_STATUS[$a['status']] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div>
                <section class="bloco-relatorio">
                    <h3 class="titulo-bloco"><i class="fa-solid fa-screwdriver-wrench"></i> Em andamento</h3>

                    <?php if (!$emAndamento): ?>
                        <p class="vazio-painel">Nenhum atendimento em andamento.</p>
                    <?php else: ?>
                        <ul class="lista-painel">
                            <?php foreach ($emAndamento as $a): ?>
                                <li>
                                    <a class="info link-tarefa" href="checklist_funcionario.php?id=<?= (int) $a['id_agendamento'] ?>">
                                        <strong><?= veiculoTexto($a) ?></strong>
                                        <span><?= htmlspecialchars($a['nome_cliente'] ?: ($a['nome_usuario'] ?? '')) ?> &middot; desde <?= formatarDataHoraFunc($a['data_hora_servico']) ?></span>
                                        <?= progressoHtml($a['checklist_feitos'], $a['checklist_total']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>

                <section class="bloco-relatorio">
                    <h3 class="titulo-bloco"><i class="fa-solid fa-list-check"></i> Tarefas pendentes</h3>

                    <?php if (!$tarefasPendentes): ?>
                        <p class="vazio-painel">Nenhuma tarefa pendente.</p>
                    <?php else: ?>
                        <ul class="lista-painel">
                            <?php foreach ($tarefasPendentes as $t): ?>
                                <li>
                                    <a class="info link-tarefa" href="checklist_funcionario.php?id=<?= (int) $t['id_agendamento'] ?>">
                                        <strong><i class="fa-regular fa-square"></i> <?= htmlspecialchars($t['descricao']) ?></strong>
                                        <span><?= veiculoTexto($t) ?> &middot; <?= formatarDataHoraFunc($t['data_hora_servico']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($qtdTarefasPendentes > count($tarefasPendentes)): ?>
                            <p class="dica"><a href="checklist_funcionario.php">Ver todas as <?= $qtdTarefasPendentes ?> tarefas</a></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>

        </div>

        <div class="atalhos-cliente">

            <a class="card-atalho" href="agendamentos_funcionario.php">
                <i class="fa-solid fa-calendar-days"></i>
                <h3>Agendamentos</h3>
                <p>Pesquise, cadastre, edite e atualize o status dos atendimentos.</p>
            </a>

            <a class="card-atalho" href="checklist_funcionario.php">
                <i class="fa-solid fa-list-check"></i>
                <h3>Checklist</h3>
                <p>Marque as tarefas de cada atendimento e acompanhe o progresso.</p>
            </a>

            <a class="card-atalho" href="historico_funcionario.php">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <h3>Histórico</h3>
                <p>Consulte atendimentos anteriores por cliente, veículo e período.</p>
            </a>

        </div>

    </main>

</body>

</html>

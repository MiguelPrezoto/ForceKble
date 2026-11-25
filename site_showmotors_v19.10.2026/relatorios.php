<?php
include("verifica_acesso.php");
exigirNivel(['ADM']);
include("conexao.php");

date_default_timezone_set('America/Sao_Paulo');

// ── filtros de período ──────────────────────────────────────────────
$dataInicio = $_GET['inicio'] ?? date('Y-m-01');
$dataFim    = $_GET['fim']    ?? date('Y-m-t');
$mesesSemRetorno = (int)($_GET['meses'] ?? 6);
$placa      = strtoupper(trim($_GET['placa'] ?? ''));

// ── 1. Agendamentos por período ─────────────────────────────────────
$stmtAg = $conn->prepare(
    "SELECT a.id_agendamento, a.data_hora_servico, a.status,
            u.nome_cliente, u.nome_usuario,
            c.marca, c.modelo, c.placa
     FROM agendamento a
     LEFT JOIN usuario u ON u.id_usuario = a.id_usuario
     LEFT JOIN carro   c ON c.id_carro   = a.id_carro
     WHERE DATE(a.data_hora_servico) BETWEEN ? AND ?
     ORDER BY a.data_hora_servico DESC"
);
$stmtAg->bind_param("ss", $dataInicio, $dataFim);
$stmtAg->execute();
$agendamentos = $stmtAg->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtAg->close();

// ── 2. Serviços mais solicitados ────────────────────────────────────
$stmtSv = $conn->prepare(
    "SELECT s.nome, COUNT(*) AS total, SUM(COALESCE(asv.preco_unitario, s.preco, 0)) AS receita
     FROM agendamento_servicos asv
     LEFT JOIN servicos s ON s.id_servico = asv.id_servico
     JOIN agendamento a ON a.id_agendamento = asv.id_agendamento
     WHERE DATE(a.data_hora_servico) BETWEEN ? AND ?
     GROUP BY asv.id_servico
     ORDER BY total DESC
     LIMIT 10"
);
$stmtSv->bind_param("ss", $dataInicio, $dataFim);
$stmtSv->execute();
$servicos = $stmtSv->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtSv->close();

// ── 3. Faturamento por período ──────────────────────────────────────
$stmtFat = $conn->prepare(
    "SELECT SUM(COALESCE(asv.preco_unitario, s.preco, 0)) AS total_geral,
            COUNT(DISTINCT a.id_agendamento) AS qtd_concluidos
     FROM agendamento_servicos asv
     LEFT JOIN servicos s ON s.id_servico = asv.id_servico
     JOIN agendamento a ON a.id_agendamento = asv.id_agendamento
     WHERE a.status = 'CONCLUIDO'
       AND DATE(a.data_hora_servico) BETWEEN ? AND ?"
);
$stmtFat->bind_param("ss", $dataInicio, $dataFim);
$stmtFat->execute();
$fat = $stmtFat->get_result()->fetch_assoc();
$stmtFat->close();
$faturamento  = (float)($fat['total_geral']    ?? 0);
$qtdConcluidos = (int)($fat['qtd_concluidos'] ?? 0);
$ticketMedio  = $qtdConcluidos > 0 ? $faturamento / $qtdConcluidos : 0;

// ── 4. Clientes com mais agendamentos ───────────────────────────────
$stmtCli = $conn->prepare(
    "SELECT u.nome_cliente, u.nome_usuario, COUNT(*) AS total
     FROM agendamento a
     JOIN usuario u ON u.id_usuario = a.id_usuario
     WHERE DATE(a.data_hora_servico) BETWEEN ? AND ?
     GROUP BY a.id_usuario
     ORDER BY total DESC
     LIMIT 10"
);
$stmtCli->bind_param("ss", $dataInicio, $dataFim);
$stmtCli->execute();
$clientes = $stmtCli->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCli->close();

// ── 5. Clientes sem retorno ─────────────────────────────────────────
$corte = date('Y-m-d', strtotime("-$mesesSemRetorno months"));
$stmtSem = $conn->query(
    "SELECT u.nome_cliente, u.nome_usuario, MAX(a.data_hora_servico) AS ultimo
     FROM usuario u
     JOIN agendamento a ON a.id_usuario = u.id_usuario
     WHERE u.nivel_acesso = 'CLIENTE'
     GROUP BY u.id_usuario
     HAVING DATE(ultimo) < '$corte'
     ORDER BY ultimo ASC"
);
$semRetorno = $stmtSem->fetch_all(MYSQLI_ASSOC);

// ── 6. Marcas/modelos mais atendidos ────────────────────────────────
$stmtMarca = $conn->prepare(
    "SELECT c.marca, c.modelo, COUNT(*) AS total
     FROM agendamento a
     JOIN carro c ON c.id_carro = a.id_carro
     WHERE DATE(a.data_hora_servico) BETWEEN ? AND ?
     GROUP BY c.marca, c.modelo
     ORDER BY total DESC
     LIMIT 10"
);
$stmtMarca->bind_param("ss", $dataInicio, $dataFim);
$stmtMarca->execute();
$marcas = $stmtMarca->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMarca->close();

// ── 7. Histórico por placa ──────────────────────────────────────────
$histPlaca = [];
if ($placa !== '') {
    $stmtPlaca = $conn->prepare(
        "SELECT a.data_hora_servico, a.status, u.nome_cliente,
                COALESCE(s.nome, 'Serviço removido') AS servico,
                COALESCE(asv.preco_unitario, s.preco, 0) AS preco
         FROM carro c
         JOIN agendamento a ON a.id_carro = c.id_carro
         LEFT JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN agendamento_servicos asv ON asv.id_agendamento = a.id_agendamento
         LEFT JOIN servicos s ON s.id_servico = asv.id_servico
         WHERE c.placa = ?
         ORDER BY a.data_hora_servico DESC"
    );
    $stmtPlaca->bind_param("s", $placa);
    $stmtPlaca->execute();
    $histPlaca = $stmtPlaca->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtPlaca->close();
}

$rotulos = [
    'AGENDADO'     => 'Agendado',
    'EM_ANDAMENTO' => 'Em andamento',
    'CONCLUIDO'    => 'Concluído',
    'CANCELADO'    => 'Cancelado',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - ShowMotors</title>
    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* tabs */
        .abas { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:28px; }
        .aba {
            padding: 9px 20px; border-radius: 999px; font-weight:600; font-size:0.88rem;
            border: 2px solid #e5e7eb; background: white; color: #1f2937; cursor: pointer;
        }
        .aba.ativa { background: linear-gradient(135deg,#eb2525,#b30000); border-color:#b30000; color:white; }

        /* painel */
        .painel { display:none; }
        .painel.ativo { display:block; }

        /* filtros inline */
        .filtro-periodo {
            display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:24px;
            background:white; border-radius:12px; padding:16px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .filtro-periodo label { font-size:0.78rem; color:#6b7280; display:flex; flex-direction:column; gap:4px; }
        .filtro-periodo input, .filtro-periodo select {
            padding:7px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:0.88rem;
        }
        .filtro-periodo button {
            padding:8px 18px; border-radius:8px; border:none;
            background:linear-gradient(135deg,#eb2525,#b30000); color:white; font-weight:600; cursor:pointer;
        }

        /* kpi cards */
        .kpi-row { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:24px; }
        .kpi-card {
            background:white; border-radius:14px; padding:22px 24px;
            box-shadow:0 2px 10px rgba(0,0,0,0.08); text-align:center;
        }
        .kpi-card i { font-size:1.6rem; color:#eb0404; margin-bottom:8px; }
        .kpi-card .valor { font-size:1.8rem; font-weight:800; color:#1f2937; }
        .kpi-card .legenda { font-size:0.8rem; color:#6b7280; margin-top:4px; }

        /* tabela ranking */
        .tabela-rel { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        .tabela-rel th { background:#f9fafb; color:#6b7280; font-size:0.78rem; text-transform:uppercase; letter-spacing:.5px; padding:12px 16px; text-align:left; }
        .tabela-rel td { padding:12px 16px; border-top:1px solid #f3f4f6; color:#1f2937; font-size:0.9rem; }
        .tabela-rel tr:hover td { background:#fef9f9; }
        .barra { background:#fecaca; border-radius:4px; height:8px; }
        .barra-inner { background:linear-gradient(90deg,#eb2525,#b30000); border-radius:4px; height:8px; }

        /* secao titulo */
        .secao-titulo-rel { font-size:1rem; font-weight:700; color:#1f2937; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
        .secao-titulo-rel i { color:#eb0404; }

        /* placa busca */
        .busca-placa { display:flex; gap:10px; margin-bottom:20px; }
        .busca-placa input { flex:1; max-width:200px; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem; text-transform:uppercase; }
        .busca-placa button { padding:8px 18px; border-radius:8px; border:none; background:linear-gradient(135deg,#eb2525,#b30000); color:white; font-weight:600; cursor:pointer; }
    </style>
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
        <h2><i class="fa-solid fa-chart-column"></i> Relatórios</h2>
    </div>

    <!-- filtro de período global -->
    <form method="GET" class="filtro-periodo" id="form-periodo">
        <label>De <input type="date" name="inicio" value="<?= $dataInicio ?>"></label>
        <label>Até <input type="date" name="fim" value="<?= $dataFim ?>"></label>
        <input type="hidden" name="meses" value="<?= $mesesSemRetorno ?>">
        <input type="hidden" name="placa" value="<?= htmlspecialchars($placa) ?>">
        <button type="submit"><i class="fa-solid fa-filter"></i> Filtrar período</button>
    </form>

    <!-- abas -->
    <div class="abas">
        <button class="aba ativa" onclick="trocarAba(0)"><i class="fa-solid fa-calendar-days"></i> Agendamentos</button>
        <button class="aba"       onclick="trocarAba(1)"><i class="fa-solid fa-money-bill-wave"></i> Faturamento</button>
        <button class="aba"       onclick="trocarAba(2)"><i class="fa-solid fa-wrench"></i> Serviços</button>
        <button class="aba"       onclick="trocarAba(3)"><i class="fa-solid fa-users"></i> Clientes</button>
        <button class="aba"       onclick="trocarAba(4)"><i class="fa-solid fa-car"></i> Veículos</button>
        <button class="aba"       onclick="trocarAba(5)"><i class="fa-solid fa-magnifying-glass"></i> Por Placa</button>
    </div>

    <!-- ── PAINEL 0: AGENDAMENTOS ── -->
    <div class="painel ativo" id="painel-0">
        <?php
        $totAg   = count($agendamentos);
        $totAg_s = count(array_filter($agendamentos, fn($a) => $a['status'] === 'AGENDADO'));
        $totAg_c = count(array_filter($agendamentos, fn($a) => $a['status'] === 'CONCLUIDO'));
        $totAg_x = count(array_filter($agendamentos, fn($a) => $a['status'] === 'CANCELADO'));
        ?>
        <div class="kpi-row">
            <div class="kpi-card"><i class="fa-solid fa-calendar-days"></i><div class="valor"><?= $totAg ?></div><div class="legenda">Total no período</div></div>
            <div class="kpi-card"><i class="fa-solid fa-calendar-check"></i><div class="valor"><?= $totAg_c ?></div><div class="legenda">Concluídos</div></div>
            <div class="kpi-card"><i class="fa-solid fa-ban"></i><div class="valor"><?= $totAg_x ?></div><div class="legenda">Cancelados</div></div>
        </div>

        <?php if (empty($agendamentos)): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-calendar-xmark"></i><p>Nenhum agendamento neste período.</p></div>
        <?php else: ?>
            <div class="filtros-status">
                <button type="button" class="filtro-status ativo" data-filtro="todos">Todos</button>
                <?php foreach ($rotulos as $k => $r): ?>
                    <button type="button" class="filtro-status" data-filtro="<?= $k ?>"><?= $r ?></button>
                <?php endforeach; ?>
            </div>
            <div class="lista-historico">
                <?php foreach ($agendamentos as $a):
                    $t = strtotime($a['data_hora_servico']);
                    $nome = $a['nome_cliente'] ?: $a['nome_usuario'];
                ?>
                <div class="card-historico" data-status="<?= htmlspecialchars($a['status'], ENT_QUOTES) ?>">
                    <div class="topo-historico">
                        <div>
                            <p style="font-size:0.8rem;color:#888;margin-bottom:2px;"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($nome) ?></p>
                            <h3><i class="fa-solid fa-calendar-day"></i> <?= date('d/m/Y', $t) ?> às <?= date('H:i', $t) ?></h3>
                            <p><i class="fa-solid fa-car"></i>
                                <?= $a['marca'] ? htmlspecialchars($a['marca'].' '.$a['modelo'].' ('.$a['placa'].')') : 'Veículo removido' ?>
                            </p>
                        </div>
                        <span class="badge-status <?= strtolower($a['status']) ?>">
                            <?= $rotulos[$a['status']] ?? $a['status'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── PAINEL 1: FATURAMENTO ── -->
    <div class="painel" id="painel-1">
        <div class="kpi-row">
            <div class="kpi-card"><i class="fa-solid fa-money-bill-wave"></i><div class="valor">R$ <?= number_format($faturamento, 2, ',', '.') ?></div><div class="legenda">Faturamento total (concluídos)</div></div>
            <div class="kpi-card"><i class="fa-solid fa-receipt"></i><div class="valor"><?= $qtdConcluidos ?></div><div class="legenda">Atendimentos concluídos</div></div>
            <div class="kpi-card"><i class="fa-solid fa-chart-line"></i><div class="valor">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></div><div class="legenda">Ticket médio</div></div>
        </div>
    </div>

    <!-- ── PAINEL 2: SERVIÇOS ── -->
    <div class="painel" id="painel-2">
        <p class="secao-titulo-rel"><i class="fa-solid fa-ranking-star"></i> Serviços mais solicitados</p>
        <?php if (empty($servicos)): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-wrench"></i><p>Nenhum serviço no período.</p></div>
        <?php else:
            $maxSv = max(array_column($servicos, 'total'));
        ?>
        <table class="tabela-rel">
            <thead><tr><th>#</th><th>Serviço</th><th>Solicitações</th><th>Receita</th><th style="width:160px"></th></tr></thead>
            <tbody>
            <?php foreach ($servicos as $i => $sv): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($sv['nome'] ?? 'Removido') ?></td>
                    <td><?= $sv['total'] ?></td>
                    <td>R$ <?= number_format($sv['receita'], 2, ',', '.') ?></td>
                    <td><div class="barra"><div class="barra-inner" style="width:<?= round($sv['total']/$maxSv*100) ?>%"></div></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── PAINEL 3: CLIENTES ── -->
    <div class="painel" id="painel-3">
        <p class="secao-titulo-rel"><i class="fa-solid fa-trophy"></i> Clientes com mais agendamentos</p>
        <?php if (empty($clientes)): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-users"></i><p>Nenhum dado no período.</p></div>
        <?php else:
            $maxCli = max(array_column($clientes, 'total'));
        ?>
        <table class="tabela-rel" style="margin-bottom:32px;">
            <thead><tr><th>#</th><th>Cliente</th><th>Usuário</th><th>Agendamentos</th><th style="width:160px"></th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $i => $cli): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($cli['nome_cliente']) ?></td>
                    <td><?= htmlspecialchars($cli['nome_usuario']) ?></td>
                    <td><?= $cli['total'] ?></td>
                    <td><div class="barra"><div class="barra-inner" style="width:<?= round($cli['total']/$maxCli*100) ?>%"></div></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <p class="secao-titulo-rel"><i class="fa-solid fa-user-clock"></i> Clientes sem retorno</p>
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:16px;">
            <input type="hidden" name="inicio" value="<?= $dataInicio ?>">
            <input type="hidden" name="fim"    value="<?= $dataFim ?>">
            <input type="hidden" name="placa"  value="<?= htmlspecialchars($placa) ?>">
            <label style="font-size:0.8rem;color:#6b7280;display:flex;flex-direction:column;gap:4px;">
                Sem visita há mais de
                <select name="meses" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:8px;">
                    <?php foreach ([3,6,12] as $m): ?>
                        <option value="<?= $m ?>" <?= $mesesSemRetorno == $m ? 'selected' : '' ?>><?= $m ?> meses</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" style="padding:8px 16px;border-radius:8px;border:none;background:linear-gradient(135deg,#eb2525,#b30000);color:white;font-weight:600;cursor:pointer;">Atualizar</button>
        </form>

        <?php if (empty($semRetorno)): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-check-circle"></i><p>Nenhum cliente sem retorno nesse período.</p></div>
        <?php else: ?>
        <table class="tabela-rel">
            <thead><tr><th>Cliente</th><th>Usuário</th><th>Último agendamento</th></tr></thead>
            <tbody>
            <?php foreach ($semRetorno as $sr): ?>
                <tr>
                    <td><?= htmlspecialchars($sr['nome_cliente']) ?></td>
                    <td><?= htmlspecialchars($sr['nome_usuario']) ?></td>
                    <td><?= date('d/m/Y', strtotime($sr['ultimo'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── PAINEL 4: VEÍCULOS/MARCAS ── -->
    <div class="painel" id="painel-4">
        <p class="secao-titulo-rel"><i class="fa-solid fa-car"></i> Marcas e modelos mais atendidos</p>
        <?php if (empty($marcas)): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-car"></i><p>Nenhum dado no período.</p></div>
        <?php else:
            $maxMarca = max(array_column($marcas, 'total'));
        ?>
        <table class="tabela-rel">
            <thead><tr><th>#</th><th>Marca</th><th>Modelo</th><th>Atendimentos</th><th style="width:160px"></th></tr></thead>
            <tbody>
            <?php foreach ($marcas as $i => $m): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($m['marca']) ?></td>
                    <td><?= htmlspecialchars($m['modelo']) ?></td>
                    <td><?= $m['total'] ?></td>
                    <td><div class="barra"><div class="barra-inner" style="width:<?= round($m['total']/$maxMarca*100) ?>%"></div></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── PAINEL 5: POR PLACA ── -->
    <div class="painel" id="painel-5">
        <p class="secao-titulo-rel"><i class="fa-solid fa-magnifying-glass"></i> Histórico por placa</p>
        <form method="GET" class="busca-placa">
            <input type="hidden" name="inicio" value="<?= $dataInicio ?>">
            <input type="hidden" name="fim"    value="<?= $dataFim ?>">
            <input type="hidden" name="meses"  value="<?= $mesesSemRetorno ?>">
            <input type="text" name="placa" placeholder="Ex: ABC1D23" value="<?= htmlspecialchars($placa) ?>" maxlength="8">
            <button type="submit"><i class="fa-solid fa-search"></i> Buscar</button>
        </form>

        <?php if ($placa === ''): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-car-side"></i><p>Digite uma placa para ver o histórico completo do veículo.</p></div>
        <?php elseif (empty($histPlaca)): ?>
            <div class="aviso-vazio"><i class="fa-solid fa-car-side"></i><p>Nenhum registro encontrado para a placa <strong><?= htmlspecialchars($placa) ?></strong>.</p></div>
        <?php else:
            // agrupa por agendamento
            $agPlaca = [];
            foreach ($histPlaca as $row) {
                $k = $row['data_hora_servico'] . '|' . $row['status'] . '|' . $row['nome_cliente'];
                $agPlaca[$k]['info']    = $row;
                $agPlaca[$k]['servicos'][] = ['nome' => $row['servico'], 'preco' => $row['preco']];
            }
        ?>
        <div class="lista-historico">
            <?php foreach ($agPlaca as $item):
                $info  = $item['info'];
                $svs   = $item['servicos'];
                $total = array_sum(array_column($svs, 'preco'));
                $t     = strtotime($info['data_hora_servico']);
            ?>
            <div class="card-historico">
                <div class="topo-historico">
                    <div>
                        <p style="font-size:0.8rem;color:#888;margin-bottom:2px;"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($info['nome_cliente']) ?></p>
                        <h3><i class="fa-solid fa-calendar-day"></i> <?= date('d/m/Y', $t) ?> às <?= date('H:i', $t) ?></h3>
                    </div>
                    <span class="badge-status <?= strtolower($info['status']) ?>">
                        <?= $rotulos[$info['status']] ?? $info['status'] ?>
                    </span>
                </div>
                <ul class="itens-historico">
                    <?php foreach ($svs as $sv): ?>
                        <li>
                            <span><?= htmlspecialchars($sv['nome']) ?></span>
                            <span>R$ <?= number_format($sv['preco'], 2, ',', '.') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="rodape-historico">
                    <span class="total">Total: <strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</main>

<script>
    function trocarAba(idx) {
        document.querySelectorAll('.aba').forEach((a, i) => a.classList.toggle('ativa', i === idx));
        document.querySelectorAll('.painel').forEach((p, i) => p.classList.toggle('ativo', i === idx));
    }

    // filtro de status (painel agendamentos)
    document.querySelectorAll('.filtro-status').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filtro-status').forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');
            const f = btn.dataset.filtro;
            document.querySelectorAll('.card-historico').forEach(c => {
                c.style.display = (f === 'todos' || c.dataset.status === f) ? '' : 'none';
            });
        });
    });
</script>

</body>
</html>

<?php
include("verifica_acesso.php");
exigirNivel(['ADM']);
include("conexao.php");

date_default_timezone_set('America/Sao_Paulo');

function formatarDataHora($dataHora)
{
    $t = strtotime($dataHora);
    return date('d/m/Y', $t) . ' às ' . date('H:i', $t);
}

// filtro de data via GET
$filtroData = $_GET['data'] ?? '';
$where  = $filtroData ? "WHERE DATE(a.data_hora_servico) = ?" : "";
$types  = $filtroData ? "s" : "";
$params = $filtroData ? [$filtroData] : [];

$sql = "SELECT a.id_agendamento, a.data_hora_servico, a.data_hora_entrega, a.descricao, a.status,
               u.nome_cliente, u.nome_usuario,
               c.marca, c.modelo, c.placa
        FROM agendamento a
        LEFT JOIN usuario u ON u.id_usuario = a.id_usuario
        LEFT JOIN carro   c ON c.id_carro   = a.id_carro
        $where
        ORDER BY a.data_hora_servico DESC";

$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$agendamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$ids   = array_column($agendamentos, 'id_agendamento');
$itens = [];
if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmtIt = $conn->prepare(
        "SELECT asv.id_agendamento,
                COALESCE(s.nome, 'Serviço removido') AS nome,
                COALESCE(asv.preco_unitario, s.preco, 0) AS preco
         FROM agendamento_servicos asv
         LEFT JOIN servicos s ON s.id_servico = asv.id_servico
         WHERE asv.id_agendamento IN ($ph)
         ORDER BY nome"
    );
    $stmtIt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmtIt->execute();
    foreach ($stmtIt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
        $itens[(int)$item['id_agendamento']][] = $item;
    }
    $stmtIt->close();
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
    <title>Agendamentos - ShowMotors</title>
    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .filtro-data {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filtro-data input[type="date"] {
            padding: 6px 10px;
            border: 1px solid #333;
            border-radius: 6px;
            background: #1a1a1a;
            color: #eee;
            font-size: 0.9rem;
        }
        .filtro-data button {
            padding: 6px 16px;
            border-radius: 6px;
            border: none;
            background: #eb2525;
            color: #fff;
            cursor: pointer;
            font-size: 0.88rem;
        }
        .filtro-data a {
            font-size: 0.85rem;
            color: #888;
            text-decoration: none;
        }
        .cliente-label {
            font-size: 0.78rem;
            color: #888;
            margin-bottom: 2px;
        }
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
        <h2><i class="fa-solid fa-calendar-days"></i> Todos os Agendamentos</h2>
    </div>

    <form method="GET" class="filtro-data">
        <input type="date" name="data" value="<?= htmlspecialchars($filtroData) ?>">
        <button type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
        <?php if ($filtroData): ?>
            <a href="agendamentos_adm.php"><i class="fa-solid fa-xmark"></i> Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($agendamentos)): ?>

        <div class="aviso-vazio">
            <i class="fa-solid fa-calendar-xmark"></i>
            <p>Nenhum agendamento encontrado.</p>
        </div>

    <?php else: ?>

        <div class="filtros-status">
            <button type="button" class="filtro-status ativo" data-filtro="todos">Todos</button>
            <?php foreach ($rotulos as $chave => $rotulo): ?>
                <button type="button" class="filtro-status" data-filtro="<?= $chave ?>"><?= $rotulo ?></button>
            <?php endforeach; ?>
        </div>

        <div class="lista-historico">

            <?php foreach ($agendamentos as $a):
                $id    = (int) $a['id_agendamento'];
                $lista = $itens[$id] ?? [];
                $total = array_sum(array_column($lista, 'preco'));
                $nome  = $a['nome_cliente'] ?: $a['nome_usuario'];
            ?>

            <div class="card-historico" data-status="<?= htmlspecialchars($a['status'], ENT_QUOTES) ?>">

                <div class="topo-historico">
                    <div>
                        <p class="cliente-label"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($nome) ?></p>
                        <h3>
                            <i class="fa-solid fa-calendar-day"></i>
                            <?= formatarDataHora($a['data_hora_servico']) ?>
                        </h3>
                        <p>
                            <i class="fa-solid fa-car"></i>
                            <?php if ($a['marca'] !== null): ?>
                                <?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?>
                                (<?= htmlspecialchars($a['placa']) ?>)
                            <?php else: ?>
                                Veículo removido
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="badge-status <?= strtolower($a['status']) ?>">
                        <?= $rotulos[$a['status']] ?? htmlspecialchars($a['status']) ?>
                    </span>
                </div>

                <ul class="itens-historico">
                    <?php foreach ($lista as $item): ?>
                        <li>
                            <span><?= htmlspecialchars($item['nome']) ?></span>
                            <span>R$ <?= number_format($item['preco'], 2, ',', '.') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (!empty($a['descricao'])): ?>
                    <p class="obs-historico">
                        <i class="fa-solid fa-comment"></i>
                        <?= htmlspecialchars($a['descricao']) ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($a['data_hora_entrega'])): ?>
                    <p class="obs-historico">
                        <i class="fa-solid fa-flag-checkered"></i>
                        Entrega prevista: <?= formatarDataHora($a['data_hora_entrega']) ?>
                    </p>
                <?php endif; ?>

                <div class="rodape-historico">
                    <span class="total">Total: <strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></span>
                </div>

            </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<script>
    document.querySelectorAll('.filtro-status').forEach((botao) => {
        botao.addEventListener('click', () => {
            document.querySelectorAll('.filtro-status').forEach((b) => b.classList.remove('ativo'));
            botao.classList.add('ativo');
            const filtro = botao.dataset.filtro;
            document.querySelectorAll('.card-historico').forEach((card) => {
                card.style.display = (filtro === 'todos' || card.dataset.status === filtro) ? '' : 'none';
            });
        });
    });
</script>

</body>
</html>

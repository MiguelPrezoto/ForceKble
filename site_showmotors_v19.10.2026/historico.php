<?php
include("verifica_acesso.php");
exigirNivel(['CLIENTE']);

include("conexao.php");

date_default_timezone_set('America/Sao_Paulo');

$idUsuario = (int) $_SESSION['id_usuario'];

$mensagem = "";
$iconeMensagem = "info";


function formatarDataHora($dataHora)
{
    $t = strtotime($dataHora);
    return date('d/m/Y', $t) . ' às ' . date('H:i', $t);
}


if (isset($_GET['agendado'])) {
    $mensagem = "Agendamento realizado! Você pode acompanhá-lo aqui.";
    $iconeMensagem = "success";
}


if (isset($_POST['cancelar_agendamento'])) {
    $idAgendamento = (int) $_POST['id_agendamento'];

    $stmt = $conn->prepare(
        "UPDATE agendamento SET status = 'CANCELADO'
         WHERE id_agendamento = ? AND id_usuario = ? AND status = 'AGENDADO'"
    );
    $stmt->bind_param("ii", $idAgendamento, $idUsuario);
    $stmt->execute();
    $cancelou = $stmt->affected_rows > 0;
    $stmt->close();

    if ($cancelou) {
        $mensagem = "Agendamento cancelado.";
        $iconeMensagem = "success";
    } else {
        $mensagem = "Não foi possível cancelar esse agendamento.";
        $iconeMensagem = "error";
    }
}


$stmt = $conn->prepare(
    "SELECT a.id_agendamento, a.data_hora_servico, a.data_hora_entrega, a.descricao, a.status,
            c.marca, c.modelo, c.placa
     FROM agendamento a
     LEFT JOIN carro c ON c.id_carro = a.id_carro
     WHERE a.id_usuario = ?
     ORDER BY a.data_hora_servico DESC"
);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$agendamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$itens = [];
$stmt = $conn->prepare(
    "SELECT asv.id_agendamento,
            COALESCE(s.nome, 'Serviço removido') AS nome,
            COALESCE(asv.preco_unitario, s.preco, 0) AS preco
     FROM agendamento_servicos asv
     JOIN agendamento a ON a.id_agendamento = asv.id_agendamento
     LEFT JOIN servicos s ON s.id_servico = asv.id_servico
     WHERE a.id_usuario = ?
     ORDER BY nome"
);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$res = $stmt->get_result();
while ($linha = $res->fetch_assoc()) {
    $itens[(int) $linha['id_agendamento']][] = $linha;
}
$stmt->close();

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
    <title>Histórico - Oficina Show Motors</title>

    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="css/cliente.css">

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
                Histórico de Serviços
            </h2>

            <a href="agendar.php" class="botao-padrao">
                <i class="fa-solid fa-calendar-plus"></i>
                Novo Agendamento
            </a>

        </div>

        <?php if (count($agendamentos) === 0): ?>

            <div class="aviso-vazio">
                <i class="fa-solid fa-clipboard-list"></i>
                <p>Você ainda não tem agendamentos.</p>
                <a href="agendar.php" class="botao-padrao">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Agendar agora
                </a>
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
                    $id = (int) $a['id_agendamento'];
                    $lista = $itens[$id] ?? [];
                    $total = 0;
                    foreach ($lista as $item) {
                        $total += (float) $item['preco'];
                    }
                ?>

                    <div class="card-historico" data-status="<?= htmlspecialchars($a['status'], ENT_QUOTES) ?>">

                        <div class="topo-historico">
                            <div>
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

                            <?php if ($a['status'] === 'AGENDADO'): ?>
                                <form method="POST" class="form-cancelar">
                                    <input type="hidden" name="id_agendamento" value="<?= $id ?>">
                                    <input type="hidden" name="cancelar_agendamento" value="1">
                                    <button type="submit" class="btn-cancelar">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </button>
                                </form>
                            <?php endif; ?>
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

        // confirmacao antes de cancelar
        document.querySelectorAll('.form-cancelar').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Cancelar agendamento?',
                    text: 'O horário ficará livre para outros clientes.',
                    showCancelButton: true,
                    confirmButtonText: 'Cancelar agendamento',
                    cancelButtonText: 'Voltar',
                    confirmButtonColor: '#eb0404'
                }).then((resultado) => {
                    if (resultado.isConfirmed) form.submit();
                });
            });
        });
    </script>

    <?php if ($mensagem): ?>
    <script>
        Swal.fire({ icon: '<?= $iconeMensagem ?>', text: <?= json_encode($mensagem) ?> });
    </script>
    <?php endif; ?>

</body>

</html>
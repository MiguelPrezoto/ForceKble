<?php
ob_start();
include("verifica_acesso.php");
exigirNivel(['CLIENTE']);

include("conexao.php");

date_default_timezone_set('America/Sao_Paulo');

$idUsuario = (int) $_SESSION['id_usuario'];


// horarios, dias uteis e funcoes de vaga ficam em regras_agendamento.php
// (compartilhado com a area do funcionario)
include("regras_agendamento.php");


if (isset($_GET['horarios'])) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $data = $_GET['horarios'];

    if (!diaPermitido($data, $DIAS_UTEIS, $DIAS_ANTECEDENCIA_MAX)) {
        echo json_encode([
            'horarios' => [],
            'erro' => 'Escolha um dia útil (segunda a sexta), a partir de amanhã.'
        ]);
        exit();
    }

    $ocupados = vagasOcupadas($conn, $data);
    $livres = [];
    foreach ($HORARIOS as $h) {
        if (($ocupados[$h] ?? 0) < $VAGAS_POR_HORARIO) {
            $livres[] = $h;
        }
    }

    echo json_encode(['horarios' => $livres]);
    exit();
}


$stmt = $conn->prepare("SELECT id_carro, marca, modelo, placa FROM carro WHERE id_usuario = ? ORDER BY marca, modelo");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$carros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$servicos = $conn->query("SELECT id_servico, nome, preco, descricao FROM servicos ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

$precoPorId = [];
foreach ($servicos as $s) {
    $precoPorId[(int) $s['id_servico']] = (float) $s['preco'];
}


$valCarro    = (int) ($_POST['id_carro'] ?? $_GET['carro'] ?? 0);
$valServicos = array_map('intval', (array) ($_POST['servicos'] ?? (isset($_GET['servico']) ? [$_GET['servico']] : [])));
$valData     = $_POST['data'] ?? '';
$valHora     = $_POST['hora'] ?? '';
$valObs      = trim($_POST['observacoes'] ?? '');

$mensagem = "";


if (isset($_POST['agendar'])) {

    $ids = array_values(array_unique(array_filter($valServicos, fn($i) => $i > 0)));

    
    $carroOk = false;
    foreach ($carros as $c) {
        if ((int) $c['id_carro'] === $valCarro) {
            $carroOk = true;
        }
    }

    
    $servicosOk = count($ids) > 0;
    foreach ($ids as $i) {
        if (!isset($precoPorId[$i])) {
            $servicosOk = false;
        }
    }

    if (!$carroOk) {
        $mensagem = "Escolha um dos seus veículos.";
    } elseif (!$servicosOk) {
        $mensagem = "Escolha pelo menos um serviço.";
    } elseif (!diaPermitido($valData, $DIAS_UTEIS, $DIAS_ANTECEDENCIA_MAX)) {
        $mensagem = "Escolha um dia útil (segunda a sexta), a partir de amanhã.";
    } elseif (!in_array($valHora, $HORARIOS, true)) {
        $mensagem = "Escolha um horário.";
    } else {
        $dataHora = $valData . ' ' . $valHora . ':00';

        
        $ocupados = vagasOcupadas($conn, $valData);

        
        $stmt = $conn->prepare(
            "SELECT id_agendamento FROM agendamento
             WHERE id_carro = ? AND DATE(data_hora_servico) = ? AND status <> 'CANCELADO'"
        );
        $stmt->bind_param("is", $valCarro, $valData);
        $stmt->execute();
        $carroJaAgendado = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (($ocupados[$valHora] ?? 0) >= $VAGAS_POR_HORARIO) {
            $mensagem = "Esse horário acabou de ser preenchido. Escolha outro.";
        } elseif ($carroJaAgendado) {
            $mensagem = "Esse veículo já tem um agendamento nesse dia.";
        } else {
            $obs = mb_substr($valObs, 0, 256);
            $idAgendamento = 0;

            try {
                $stmt = $conn->prepare(
                    "INSERT INTO agendamento (id_usuario, id_carro, data_hora_servico, descricao)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt->bind_param("iiss", $idUsuario, $valCarro, $dataHora, $obs);
                $stmt->execute();
                $idAgendamento = $conn->insert_id;
                $stmt->close();

                
                $stmt = $conn->prepare(
                    "INSERT INTO agendamento_servicos (id_agendamento, id_servico, preco_unitario) VALUES (?, ?, ?)"
                );
                foreach ($ids as $idServico) {
                    $preco = $precoPorId[$idServico];
                    $stmt->bind_param("iid", $idAgendamento, $idServico, $preco);
                    $stmt->execute();
                }
                $stmt->close();

                header("Location: historico.php?agendado=1");
                exit();

            } catch (mysqli_sql_exception $e) {
               
                if ($idAgendamento > 0) {
                    $conn->query("DELETE FROM agendamento WHERE id_agendamento = " . (int) $idAgendamento);
                    $conn->query("DELETE FROM agendamento_servicos WHERE id_agendamento = " . (int) $idAgendamento);
                }
                $mensagem = "Não foi possível salvar o agendamento. Tente novamente.";
            }
        }
    }
}

$amanha = date('Y-m-d', strtotime('+1 day'));
$limite = date('Y-m-d', strtotime("+$DIAS_ANTECEDENCIA_MAX days"));
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar - Oficina Show Motors</title>

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
                <i class="fa-solid fa-calendar-plus"></i>
                Agendar Serviço
            </h2>

        </div>

        <?php if (count($carros) === 0): ?>

           
            <div class="aviso-vazio">
                <i class="fa-solid fa-car-side"></i>
                <p>Para agendar, primeiro cadastre o veículo que será atendido.</p>
                <a href="meus_veiculos.php" class="botao-padrao">
                    <i class="fa-solid fa-plus"></i>
                    Cadastrar veículo
                </a>
            </div>

        <?php elseif (count($servicos) === 0): ?>

            <div class="aviso-vazio">
                <i class="fa-solid fa-wrench"></i>
                <p>Nenhum serviço disponível para agendamento no momento.</p>
            </div>

        <?php else: ?>

            <form method="POST" class="form-agendamento" id="form-agendamento">

                <div class="bloco-form">
                    <h3>1. Veículo</h3>
                    <select name="id_carro" id="id-carro" required>
                        <option value="">Selecione o veículo</option>
                        <?php foreach ($carros as $c): ?>
                            <option value="<?= (int) $c['id_carro'] ?>" <?= $valCarro === (int) $c['id_carro'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['marca'] . ' ' . $c['modelo'] . ' - ' . $c['placa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="dica">Não é este veículo? <a href="meus_veiculos.php">Cadastre outro</a>.</p>
                </div>

                <div class="bloco-form">
                    <h3>2. Serviços</h3>
                    <div class="lista-servicos-escolha">
                        <?php foreach ($servicos as $s): ?>
                            <label class="opcao-servico">
                                <input type="checkbox" name="servicos[]" value="<?= (int) $s['id_servico'] ?>"
                                    data-preco="<?= htmlspecialchars($s['preco'], ENT_QUOTES) ?>"
                                    <?= in_array((int) $s['id_servico'], $valServicos, true) ? 'checked' : '' ?>>
                                <span>
                                    <span class="nome"><?= htmlspecialchars($s['nome']) ?></span>
                                    <span class="desc"><?= htmlspecialchars($s['descricao'] ?? '') ?></span>
                                </span>
                                <span class="preco">R$ <?= number_format($s['preco'], 2, ',', '.') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bloco-form">
                    <h3>3. Data e horário</h3>
                    <input type="date" name="data" id="data" min="<?= $amanha ?>" max="<?= $limite ?>"
                        value="<?= htmlspecialchars($valData, ENT_QUOTES) ?>" required>
                    <p class="dica">Atendemos de segunda a sexta. Agendamentos a partir de amanhã.</p>

                    <div class="grade-horarios" id="grade-horarios"></div>
                    <p class="dica" id="aviso-horarios"></p>
                </div>

                <div class="bloco-form">
                    <h3>4. Observações (opcional)</h3>
                    <textarea name="observacoes" maxlength="256" rows="3"
                        placeholder="Ex.: barulho ao frear, luz do painel acesa..."><?= htmlspecialchars($valObs) ?></textarea>
                </div>

                <div class="resumo-agendamento">
                    <div class="total">
                        Valor estimado
                        <strong id="total-estimado">R$ 0,00</strong>
                    </div>

                    <button type="submit" name="agendar" class="botao-padrao">
                        <i class="fa-solid fa-check"></i>
                        Confirmar agendamento
                    </button>
                </div>

            </form>

        <?php endif; ?>

    </main>

    <?php if (count($carros) > 0 && count($servicos) > 0): ?>
    <script>
        const campoData = document.getElementById('data');
        const gradeHorarios = document.getElementById('grade-horarios');
        const avisoHorarios = document.getElementById('aviso-horarios');
        const horaInicial = <?= json_encode($valHora) ?>;

        
        function atualizarTotal() {
            let total = 0;
            document.querySelectorAll('input[name="servicos[]"]:checked').forEach((c) => {
                total += parseFloat(c.dataset.preco);
            });
            document.getElementById('total-estimado').textContent =
                total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        document.querySelectorAll('input[name="servicos[]"]').forEach((c) => {
            c.addEventListener('change', atualizarTotal);
        });
        atualizarTotal();

        
        function carregarHorarios(data, horaMarcada) {
            gradeHorarios.innerHTML = '';
            avisoHorarios.textContent = '';
            if (!data) return;

            avisoHorarios.textContent = 'Carregando horários...';

            fetch('agendar.php?horarios=' + encodeURIComponent(data))
                .then((r) => r.json())
                .then((dados) => {
                    avisoHorarios.textContent = '';

                    if (dados.erro) {
                        avisoHorarios.textContent = dados.erro;
                        return;
                    }
                    if (dados.horarios.length === 0) {
                        avisoHorarios.textContent = 'Não há horários livres nesse dia. Escolha outra data.';
                        return;
                    }

                    dados.horarios.forEach((h) => {
                        const label = document.createElement('label');
                        label.className = 'horario-opcao';
                        label.innerHTML = '<input type="radio" name="hora" value="' + h + '" required><span>' + h + '</span>';
                        if (h === horaMarcada) label.querySelector('input').checked = true;
                        gradeHorarios.appendChild(label);
                    });
                })
                .catch(() => {
                    avisoHorarios.textContent = 'Não foi possível carregar os horários. Tente novamente.';
                });
        }

        campoData.addEventListener('change', () => carregarHorarios(campoData.value, ''));
        if (campoData.value) carregarHorarios(campoData.value, horaInicial);

        // precisa de pelo menos um servico marcado
        document.getElementById('form-agendamento').addEventListener('submit', (e) => {
            if (document.querySelectorAll('input[name="servicos[]"]:checked').length === 0) {
                e.preventDefault();
                Swal.fire({ icon: 'error', text: 'Escolha pelo menos um serviço.' });
            }
        });
    </script>
    <?php endif; ?>

    <?php if ($mensagem): ?>
    <script>
        Swal.fire({ icon: 'error', text: <?= json_encode($mensagem) ?> });
    </script>
    <?php endif; ?>

</body>

</html>
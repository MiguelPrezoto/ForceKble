<?php
include("verifica_acesso.php");
exigirNivel(['CLIENTE']);

include("conexao.php");

$idUsuario = (int) $_SESSION['id_usuario'];

$mensagem = "";
$iconeMensagem = "info";
$abrirModalComErro = false;


$marca = $modelo = $placa = $cor = $combustivel = $chassi = $km = "";
$anoFab = $anoMod = "";


if (isset($_POST['excluir_veiculo'])) {
    $idCarro = (int) $_POST['id_carro'];

    $stmt = $conn->prepare("SELECT COUNT(*) AS qtd FROM agendamento WHERE id_carro = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $idCarro, $idUsuario);
    $stmt->execute();
    $qtd = $stmt->get_result()->fetch_assoc()['qtd'];
    $stmt->close();

    if ($qtd > 0) {
        // o historico depende do carro, entao nao deixa apagar
        $mensagem = "Este veículo tem agendamentos no histórico e não pode ser excluído.";
        $iconeMensagem = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM carro WHERE id_carro = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $idCarro, $idUsuario);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Veículo excluído.";
        $iconeMensagem = "success";
    }
}

// cadastrar um veiculo
if (isset($_POST['criar_veiculo'])) {
    $marca       = trim($_POST['marca'] ?? '');
    $modelo      = trim($_POST['modelo'] ?? '');
    $placa       = strtoupper(preg_replace('/[\s-]/', '', $_POST['placa'] ?? ''));
    $anoFab      = trim($_POST['ano_fabricacao'] ?? '');
    $anoMod      = trim($_POST['ano_modelo'] ?? '');
    $cor         = trim($_POST['cor'] ?? '');
    $combustivel = trim($_POST['tipo_combustivel'] ?? '');
    $chassi      = strtoupper(trim($_POST['num_chassi'] ?? ''));
    $km          = trim($_POST['km_rodados'] ?? '');

    $anoMax = (int) date('Y') + 1;

    if ($marca === '' || $modelo === '') {
        $mensagem = "Informe a marca e o modelo.";
    } elseif (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa)) {
        // aceita o formato antigo (ABC1234) e o Mercosul (ABC1D23)
        $mensagem = "Informe uma placa válida (ex.: ABC1234 ou ABC1D23).";
    } elseif (!ctype_digit($anoFab) || (int) $anoFab < 1950 || (int) $anoFab > $anoMax) {
        $mensagem = "Informe um ano de fabricação válido.";
    } elseif (!ctype_digit($anoMod) || (int) $anoMod < (int) $anoFab || (int) $anoMod > (int) $anoFab + 1) {
        $mensagem = "O ano do modelo deve ser o mesmo da fabricação ou o seguinte.";
    } elseif ($chassi !== '' && !preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $chassi)) {
        $mensagem = "O chassi deve ter 17 caracteres (não usa as letras I, O e Q).";
    } elseif ($km !== '' && !ctype_digit($km)) {
        $mensagem = "Informe a quilometragem só com números.";
    } else {
        // nao deixa cadastrar a mesma placa duas vezes para o mesmo usuario
        $stmt = $conn->prepare("SELECT id_carro FROM carro WHERE id_usuario = ? AND placa = ?");
        $stmt->bind_param("is", $idUsuario, $placa);
        $stmt->execute();
        $jaExiste = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($jaExiste) {
            $mensagem = "Você já cadastrou um veículo com essa placa.";
        } else {
            $anoFabInt = (int) $anoFab;
            $anoModInt = (int) $anoMod;
            $chassiBd  = $chassi === '' ? null : $chassi;
            $corBd     = $cor === '' ? null : $cor;
            $combBd    = $combustivel === '' ? null : $combustivel;
            $kmBd      = $km === '' ? null : (int) $km;

            $stmt = $conn->prepare(
                "INSERT INTO carro (id_usuario, marca, modelo, num_chassi, ano_fabricacao, ano_modelo, cor, placa, tipo_combustivel, km_rodados)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "isssiisssi",
                $idUsuario, $marca, $modelo, $chassiBd, $anoFabInt, $anoModInt, $corBd, $placa, $combBd, $kmBd
            );
            $stmt->execute();
            $stmt->close();

            $mensagem = "Veículo cadastrado.";
            $iconeMensagem = "success";
            $marca = $modelo = $placa = $cor = $combustivel = $chassi = $km = $anoFab = $anoMod = "";
        }
    }

    if ($iconeMensagem === "info") {
        // deu erro: reabre o modal com o que a pessoa digitou
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    }
}

$stmt = $conn->prepare(
    "SELECT id_carro, marca, modelo, placa, ano_fabricacao, ano_modelo, cor, tipo_combustivel, km_rodados
     FROM carro WHERE id_usuario = ? ORDER BY marca, modelo"
);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$veiculos = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Veículos - Oficina Show Motors</title>

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
                <i class="fa-solid fa-car"></i>
                Meus Veículos
            </h2>

            <button type="button" class="botao-padrao" id="btn-abrir-modal-veiculo">
                <i class="fa-solid fa-plus"></i>
                Novo Veículo
            </button>

        </div>

        <?php if ($veiculos->num_rows == 0): ?>

            <div class="aviso-vazio">
                <i class="fa-solid fa-car-side"></i>
                <p>Você ainda não cadastrou nenhum veículo. Cadastre um para poder agendar serviços.</p>
            </div>

        <?php else: ?>

            <div class="grade-veiculos">

                <?php while ($v = $veiculos->fetch_assoc()): ?>

                    <div class="card-veiculo">

                        <h3>
                            <i class="fa-solid fa-car"></i>
                            <?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?>
                        </h3>

                        <span class="placa-veiculo"><?= htmlspecialchars($v['placa']) ?></span>

                        <ul class="detalhes-veiculo">
                            <li><strong>Ano:</strong> <?= (int) $v['ano_fabricacao'] ?>/<?= (int) $v['ano_modelo'] ?></li>
                            <?php if (!empty($v['cor'])): ?>
                                <li><strong>Cor:</strong> <?= htmlspecialchars($v['cor']) ?></li>
                            <?php endif; ?>
                            <?php if (!empty($v['tipo_combustivel'])): ?>
                                <li><strong>Combustível:</strong> <?= htmlspecialchars($v['tipo_combustivel']) ?></li>
                            <?php endif; ?>
                            <?php if ($v['km_rodados'] !== null): ?>
                                <li><strong>Km:</strong> <?= number_format($v['km_rodados'], 0, ',', '.') ?></li>
                            <?php endif; ?>
                        </ul>

                        <div class="acoes-servico">

                            <a class="botao-padrao" href="agendar.php?carro=<?= (int) $v['id_carro'] ?>">
                                <i class="fa-solid fa-calendar-plus"></i>
                                Agendar
                            </a>

                            <form method="POST" class="form-excluir-veiculo">
                                <input type="hidden" name="id_carro" value="<?= (int) $v['id_carro'] ?>">
                                <input type="hidden" name="excluir_veiculo" value="1">
                                <button type="submit" class="btn-excluir"
                                    data-nome="<?= htmlspecialchars($v['marca'] . ' ' . $v['modelo'] . ' (' . $v['placa'] . ')', ENT_QUOTES) ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php endif; ?>

    </main>

    <div class="modal-overlay" id="modal-veiculo">
        <div class="modal-caixa">
            <div class="modal-cabecalho">
                <h2><i class="fa-solid fa-car"></i> Novo Veículo</h2>
                <button type="button" class="modal-fechar" id="btn-fechar-modal-veiculo">&times;</button>
            </div>

            <form method="POST" id="form-veiculo">

                <div class="linha-dupla">
                    <div>
                        <label for="marca">Marca *</label>
                        <input type="text" id="marca" name="marca" maxlength="15" required
                            value="<?= htmlspecialchars($marca, ENT_QUOTES) ?>">
                    </div>
                    <div>
                        <label for="modelo">Modelo *</label>
                        <input type="text" id="modelo" name="modelo" maxlength="50" required
                            value="<?= htmlspecialchars($modelo, ENT_QUOTES) ?>">
                    </div>
                </div>

                <label for="placa">Placa *</label>
                <input type="text" id="placa" name="placa" maxlength="8" placeholder="ABC1D23" required
                    value="<?= htmlspecialchars($placa, ENT_QUOTES) ?>">

                <div class="linha-dupla">
                    <div>
                        <label for="ano_fabricacao">Ano de fabricação *</label>
                        <input type="number" id="ano_fabricacao" name="ano_fabricacao" min="1950" required
                            value="<?= htmlspecialchars($anoFab, ENT_QUOTES) ?>">
                    </div>
                    <div>
                        <label for="ano_modelo">Ano do modelo *</label>
                        <input type="number" id="ano_modelo" name="ano_modelo" min="1950" required
                            value="<?= htmlspecialchars($anoMod, ENT_QUOTES) ?>">
                    </div>
                </div>

                <div class="linha-dupla">
                    <div>
                        <label for="cor">Cor</label>
                        <input type="text" id="cor" name="cor" maxlength="10"
                            value="<?= htmlspecialchars($cor, ENT_QUOTES) ?>">
                    </div>
                    <div>
                        <label for="tipo_combustivel">Combustível</label>
                        <select id="tipo_combustivel" name="tipo_combustivel">
                            <option value="">Selecione</option>
                            <?php foreach (['Flex', 'Gasolina', 'Etanol', 'Diesel', 'Híbrido', 'Elétrico', 'GNV'] as $c): ?>
                                <option value="<?= $c ?>" <?= $combustivel === $c ? 'selected' : '' ?>><?= $c ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label for="km_rodados">Quilometragem atual</label>
                <input type="number" id="km_rodados" name="km_rodados" min="0"
                    value="<?= htmlspecialchars($km, ENT_QUOTES) ?>">

                <label for="num_chassi">Chassi (opcional)</label>
                <input type="text" id="num_chassi" name="num_chassi" maxlength="17"
                    value="<?= htmlspecialchars($chassi, ENT_QUOTES) ?>">

                <button type="submit" class="botao-padrao" name="criar_veiculo">Cadastrar</button>

            </form>
        </div>
    </div>

    <script>
        // modal de veiculo
        const modalVeiculo = document.getElementById('modal-veiculo');

        document.getElementById('btn-abrir-modal-veiculo').addEventListener('click', () => {
            document.getElementById('form-veiculo').reset();
            modalVeiculo.classList.add('aberto');
        });
        document.getElementById('btn-fechar-modal-veiculo').addEventListener('click', () => {
            modalVeiculo.classList.remove('aberto');
        });
        modalVeiculo.addEventListener('click', (e) => {
            if (e.target === modalVeiculo) modalVeiculo.classList.remove('aberto');
        });

        // confirmacao antes de excluir um veiculo
        document.querySelectorAll('.form-excluir-veiculo').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const btn = form.querySelector('.btn-excluir');

                Swal.fire({
                    icon: 'warning',
                    title: 'Excluir veículo?',
                    text: `O veículo "${btn.dataset.nome}" será removido da sua conta.`,
                    showCancelButton: true,
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
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
        <?php if ($abrirModalComErro): ?>
        // reabre o modal; os campos ja voltam preenchidos pelo PHP (value="...")
        modalVeiculo.classList.add('aberto');
        <?php endif; ?>
    </script>
    <?php endif; ?>

</body>

</html>
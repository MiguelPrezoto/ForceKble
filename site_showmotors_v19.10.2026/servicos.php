<?php
include("verifica_acesso.php");
exigirNivel(['ADM']);

include("conexao.php");

$mensagem = "";
$iconeMensagem = "info";
$abrirModalComErro = false;

// excluir um servico
if (isset($_POST['excluir_servico'])) {
    $id = (int) $_POST['id_servico'];

    $stmt = $conn->prepare("DELETE FROM servicos WHERE id_servico = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $mensagem = "Serviço excluído.";
    $iconeMensagem = "success";
}

// criar novo servico ou salvar edicao de um servico existente
if (isset($_POST['criar_servico']) || isset($_POST['salvar_servico'])) {
    $id        = (int) ($_POST['id_servico'] ?? 0);
    $nome      = trim($_POST['nome']);
    $preco     = $_POST['preco'] ?? '';
    $descricao = trim($_POST['descricao']);

    if ($nome === '') {
        $mensagem = "Informe o nome do serviço.";
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    } elseif (!is_numeric($preco) || (float) $preco <= 0) {
        $mensagem = "Informe um preço válido.";
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    } elseif (isset($_POST['salvar_servico'])) {
        $precoFloat = (float) $preco;
        $stmt = $conn->prepare("UPDATE servicos SET nome = ?, preco = ?, descricao = ? WHERE id_servico = ?");
        $stmt->bind_param("sdsi", $nome, $precoFloat, $descricao, $id);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Serviço atualizado.";
        $iconeMensagem = "success";
    } else {
        $precoFloat = (float) $preco;
        $stmt = $conn->prepare("INSERT INTO servicos (nome, preco, descricao) VALUES (?, ?, ?)");
        $stmt->bind_param("sds", $nome, $precoFloat, $descricao);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Serviço criado.";
        $iconeMensagem = "success";
    }
}

$servicos = $conn->query("SELECT id_servico, nome, preco, descricao FROM servicos ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços - Oficina Show Motors</title>

    <link rel="stylesheet" href="css/estilizacao.css">

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
                <i class="fa-solid fa-wrench"></i>
                Gerenciamento de Serviços
            </h2>

            <button type="button" class="botao-padrao" id="btn-abrir-modal-servico">
                <i class="fa-solid fa-plus"></i>
                Novo Serviço
            </button>

        </div>

        <div class="barra-filtros">

            <div class="campo-pesquisa">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input type="text" id="pesquisa" placeholder="Pesquisar serviço">

            </div>

        </div>

        <div class="grade-servicos-admin">

            <?php if ($servicos->num_rows == 0): ?>
                <p>Nenhum serviço cadastrado.</p>
            <?php endif; ?>

            <?php while ($s = $servicos->fetch_assoc()): ?>

                <div class="card-servico-admin">

                    <div class="cabecalho-servico">

                        <div class="icone-servico">
                            <i class="fa-solid fa-wrench"></i>
                        </div>

                    </div>

                    <h3><?= htmlspecialchars($s['nome']) ?></h3>

                    <p>
                        <?= htmlspecialchars($s['descricao']) ?>
                    </p>

                    <div class="dados-servico">

                        <strong>
                            R$ <?= number_format($s['preco'], 2, ',', '.') ?>
                        </strong>

                    </div>

                    <div class="acoes-servico">

                        <button
                            type="button"
                            class="btn-editar"
                            data-id="<?= $s['id_servico'] ?>"
                            data-nome="<?= htmlspecialchars($s['nome'], ENT_QUOTES) ?>"
                            data-preco="<?= htmlspecialchars($s['preco'], ENT_QUOTES) ?>"
                            data-descricao="<?= htmlspecialchars($s['descricao'], ENT_QUOTES) ?>"
                        >
                            <i class="fa-solid fa-pen"></i>
                            Editar
                        </button>

                        <form method="POST" class="form-excluir-servico">
                            <input type="hidden" name="id_servico" value="<?= $s['id_servico'] ?>">
                            <input type="hidden" name="excluir_servico" value="1">
                            <button type="submit" class="btn-excluir" data-nome="<?= htmlspecialchars($s['nome'], ENT_QUOTES) ?>">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    </main>

    <div class="modal-overlay" id="modal-servico">
        <div class="modal-caixa">
            <div class="modal-cabecalho">
                <h2 id="titulo-modal-servico"><i class="fa-solid fa-wrench"></i> Novo Serviço</h2>
                <button type="button" class="modal-fechar" id="btn-fechar-modal-servico">&times;</button>
            </div>

            <form method="POST" id="form-servico">
                <input type="hidden" id="id-servico" name="id_servico" value="">

                <label for="nome-servico">Nome do Serviço</label>
                <input type="text" id="nome-servico" name="nome" maxlength="100" required>

                <label for="preco-servico">Preço (R$)</label>
                <input type="number" id="preco-servico" name="preco" step="0.01" min="0" required>

                <label for="descricao-servico">Descrição</label>
                <textarea id="descricao-servico" name="descricao" maxlength="2000" rows="4"></textarea>

                <button type="submit" class="botao-padrao" id="btn-salvar-servico" name="criar_servico">Criar</button>

            </form>
        </div>
    </div>

    <script>
        // filtra os cards pelo nome digitado
        document.getElementById('pesquisa').addEventListener('input', function () {
            let busca = this.value.toLowerCase();

            document.querySelectorAll('.card-servico-admin').forEach(function (card) {
                let nome = card.querySelector('h3').textContent.toLowerCase();
                card.style.display = nome.includes(busca) ? '' : 'none';
            });
        });

        // modal de servico: mesmo modal serve para criar e para editar
        const modalServico = document.getElementById('modal-servico');
        const btnAbrirModalServico = document.getElementById('btn-abrir-modal-servico');
        const btnFecharModalServico = document.getElementById('btn-fechar-modal-servico');
        const formServico = document.getElementById('form-servico');
        const tituloModalServico = document.getElementById('titulo-modal-servico');
        const btnSalvarServico = document.getElementById('btn-salvar-servico');
        const campoIdServico = document.getElementById('id-servico');
        const campoNomeServico = document.getElementById('nome-servico');
        const campoPrecoServico = document.getElementById('preco-servico');
        const campoDescricaoServico = document.getElementById('descricao-servico');

        function abrirModalCriarServico() {
            formServico.reset();
            campoIdServico.value = '';
            tituloModalServico.innerHTML = '<i class="fa-solid fa-wrench"></i> Novo Serviço';
            btnSalvarServico.name = 'criar_servico';
            btnSalvarServico.textContent = 'Criar';
            modalServico.classList.add('aberto');
        }

        function abrirModalEditarServico(btn) {
            campoIdServico.value = btn.dataset.id;
            campoNomeServico.value = btn.dataset.nome;
            campoPrecoServico.value = btn.dataset.preco;
            campoDescricaoServico.value = btn.dataset.descricao;
            tituloModalServico.innerHTML = '<i class="fa-solid fa-pen"></i> Editar Serviço';
            btnSalvarServico.name = 'salvar_servico';
            btnSalvarServico.textContent = 'Salvar';
            modalServico.classList.add('aberto');
        }

        btnAbrirModalServico.addEventListener('click', abrirModalCriarServico);
        btnFecharModalServico.addEventListener('click', () => modalServico.classList.remove('aberto'));
        modalServico.addEventListener('click', (e) => {
            if (e.target === modalServico) modalServico.classList.remove('aberto');
        });

        document.querySelectorAll('.btn-editar').forEach((btn) => {
            btn.addEventListener('click', () => abrirModalEditarServico(btn));
        });

        // confirmacao antes de excluir um servico
        document.querySelectorAll('.form-excluir-servico').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const btn = form.querySelector('.btn-excluir');

                Swal.fire({
                    icon: 'warning',
                    title: 'Excluir serviço?',
                    text: `O serviço "${btn.dataset.nome}" será excluído permanentemente. Essa ação não pode ser desfeita.`,
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
        campoIdServico.value = <?= json_encode($id ?? '') ?>;
        campoNomeServico.value = <?= json_encode($nome ?? '') ?>;
        campoPrecoServico.value = <?= json_encode($preco ?? '') ?>;
        campoDescricaoServico.value = <?= json_encode($descricao ?? '') ?>;
        <?php if (isset($_POST['salvar_servico'])): ?>
        tituloModalServico.innerHTML = '<i class="fa-solid fa-pen"></i> Editar Serviço';
        btnSalvarServico.name = 'salvar_servico';
        btnSalvarServico.textContent = 'Salvar';
        <?php else: ?>
        tituloModalServico.innerHTML = '<i class="fa-solid fa-wrench"></i> Novo Serviço';
        btnSalvarServico.name = 'criar_servico';
        btnSalvarServico.textContent = 'Criar';
        <?php endif; ?>
        modalServico.classList.add('aberto');
        <?php endif; ?>
    </script>
    <?php endif; ?>

</body>

</html>

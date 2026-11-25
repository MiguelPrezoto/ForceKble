<?php
include("verifica_acesso.php");
exigirNivel(['CLIENTE']);

include("conexao.php");

// o cliente só consulta; quem cria/edita/exclui serviços é o ADM (servicos.php)
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
                Nossos Serviços
            </h2>

            <a href="agendar.php" class="botao-padrao">
                <i class="fa-solid fa-calendar-plus"></i>
                Agendar Serviço
            </a>

        </div>

        <div class="barra-filtros">

            <div class="campo-pesquisa">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input type="text" id="pesquisa" placeholder="Pesquisar serviço">

            </div>

        </div>

        <div class="grade-servicos-admin">

            <?php if ($servicos->num_rows == 0): ?>
                <p>Nenhum serviço disponível no momento.</p>
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
                        <?= htmlspecialchars($s['descricao'] ?? '') ?>
                    </p>

                    <div class="dados-servico">

                        <strong>
                            R$ <?= number_format($s['preco'], 2, ',', '.') ?>
                        </strong>

                    </div>

                    <div class="acoes-servico">
                       
                        <a class="botao-padrao" href="agendar.php?servico=<?= (int) $s['id_servico'] ?>">
                            <i class="fa-solid fa-calendar-plus"></i>
                            Agendar
                        </a>
                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    </main>

    <script>

        document.getElementById('pesquisa').addEventListener('input', function () {
            let busca = this.value.toLowerCase();

            document.querySelectorAll('.card-servico-admin').forEach(function (card) {
                let nome = card.querySelector('h3').textContent.toLowerCase();
                card.style.display = nome.includes(busca) ? '' : 'none';
            });
        });
    </script>

</body>

</html>
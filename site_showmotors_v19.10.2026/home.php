<?php
// inicia a sessao e barra quem nao esta logado
include("verifica_acesso.php");
exigirNivel(['ADM', 'FUNCIONARIO', 'CLIENTE']);

$nivel = $_SESSION['nivel'];

// funcionario tem a propria area (painel com agendamentos, checklist e historico)
if ($nivel === 'FUNCIONARIO') {
    header("Location: painel_funcionario.php");
    exit();
}

// cliente: busca o proximo agendamento para mostrar na tela inicial
$proximo = null;
if ($nivel === 'CLIENTE') {
    include("conexao.php");
    date_default_timezone_set('America/Sao_Paulo');

    $idUsuario = (int) $_SESSION['id_usuario'];
    $agora = date('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        "SELECT a.data_hora_servico, c.marca, c.modelo, c.placa
         FROM agendamento a
         JOIN carro c ON c.id_carro = a.id_carro
         WHERE a.id_usuario = ? AND a.status = 'AGENDADO' AND a.data_hora_servico >= ?
         ORDER BY a.data_hora_servico
         LIMIT 1"
    );
    $stmt->bind_param("is", $idUsuario, $agora);
    $stmt->execute();
    $proximo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oficina Show Motors</title>
    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="css/cliente.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

        <?php if ($nivel === 'ADM'): ?>

            <section>
        <!-- adicionar área de relatórios; arrumar a exclusão de usuários e colocar a adição de novos usuários em outro local -->
                <h2>Tela ADM</h2>
                <p>Você está logado como Administrador.</p>
            </section>

        <?php else: ?>

            <div class="cabecalho-secao">
    <h2><i class="fa-solid fa-house"></i> Olá, <?= htmlspecialchars($_SESSION['usuario']) ?>!</h2>
</div>

<?php if ($proximo): ?>
    <?php $t = strtotime($proximo['data_hora_servico']); ?>
    <div class="proximo-agendamento">
        <i class="fa-solid fa-calendar-check"></i>
        <div>
            <strong>Seu próximo agendamento</strong>
            <p>
                <?= date('d/m/Y', $t) ?> às <?= date('H:i', $t) ?>
                &middot;
                <?= htmlspecialchars($proximo['marca'] . ' ' . $proximo['modelo']) ?>
                (<?= htmlspecialchars($proximo['placa']) ?>)
            </p>
        </div>
    </div>
    <?php endif; ?>

<div class="atalhos-cliente">

    <a class="card-atalho" href="servicos_cliente.php">
        <i class="fa-solid fa-wrench"></i>
        <h3>Nossos Serviços</h3>
        <p>Veja o que a oficina oferece e os valores.</p>
    </a>

    <a class="card-atalho" href="agendar.php">
        <i class="fa-solid fa-calendar-plus"></i>
        <h3>Agendar Serviço</h3>
        <p>Escolha o veículo, os serviços e o horário.</p>
    </a>

    <a class="card-atalho" href="historico.php">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h3>Histórico</h3>
        <p>Acompanhe seus agendamentos e serviços feitos.</p>
    </a>

</div>

        <?php endif; ?>

    </main>

</body>
</html>

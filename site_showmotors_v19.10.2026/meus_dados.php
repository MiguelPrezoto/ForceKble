<?php
include("verifica_acesso.php");
exigirNivel(['ADM', 'FUNCIONARIO', 'CLIENTE']);
include("conexao.php");

$idUser   = (int) $_SESSION['id_usuario'];
$mensagem = "";

// carrega dados atuais
$stmtLoad = $conn->prepare(
    "SELECT nome_cliente, nome_usuario, email,
            COALESCE(telefone,'') AS telefone,
            COALESCE(celular,'')  AS celular,
            COALESCE(cpf,'')      AS cpf
     FROM usuario WHERE id_usuario = ?"
);
$stmtLoad->bind_param("i", $idUser);
$stmtLoad->execute();
$dados = $stmtLoad->get_result()->fetch_assoc();
$stmtLoad->close();

if (isset($_POST['salvar'])) {

    $nome     = trim($_POST['nome_cliente']);
    $email    = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $celular  = trim($_POST['celular']);

    // verifica se o email ja pertence a outro usuario
    $stmtEmail = $conn->prepare(
        "SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?"
    );
    $stmtEmail->bind_param("si", $email, $idUser);
    $stmtEmail->execute();

    if ($stmtEmail->get_result()->num_rows > 0) {
        $mensagem = 'email_duplicado';
    } else {
        $stmtUp = $conn->prepare(
            "UPDATE usuario SET nome_cliente = ?, email = ?, telefone = ?, celular = ? WHERE id_usuario = ?"
        );
        $stmtUp->bind_param("ssssi", $nome, $email, $telefone, $celular, $idUser);

        if ($stmtUp->execute()) {
            // atualiza os dados exibidos sem precisar recarregar
            $dados['nome_cliente'] = $nome;
            $dados['email']        = $email;
            $dados['telefone']     = $telefone;
            $dados['celular']      = $celular;
            $mensagem = 'sucesso';
        } else {
            $mensagem = 'erro_db';
        }
        $stmtUp->close();
    }
    $stmtEmail->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Dados</title>
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

<div class="caixa">
    <form method="POST">

        <label for="nome_cliente">Nome Completo</label>
        <input type="text" name="nome_cliente" id="nome_cliente"
               value="<?= htmlspecialchars($dados['nome_cliente']) ?>" required>

        <label>Nome de Usuário</label>
        <input type="text" value="<?= htmlspecialchars($dados['nome_usuario']) ?>" disabled
               style="opacity:0.5; cursor:not-allowed;">
        <small style="color:#999; font-size:0.75rem;">O nome de usuário não pode ser alterado.</small>

        <label for="email">E-mail</label>
        <input type="email" name="email" id="email"
               value="<?= htmlspecialchars($dados['email']) ?>" required>

        <label for="telefone">Telefone</label>
        <input type="text" name="telefone" id="telefone"
               value="<?= htmlspecialchars($dados['telefone']) ?>" maxlength="14">

        <label for="celular">Celular</label>
        <input type="text" name="celular" id="celular"
               value="<?= htmlspecialchars($dados['celular']) ?>" maxlength="15">

        <label>CPF</label>
        <input type="text" value="<?= htmlspecialchars($dados['cpf']) ?>" disabled
               style="opacity:0.5; cursor:not-allowed;">
        <small style="color:#999; font-size:0.75rem;">O CPF não pode ser alterado.</small>

        <button type="submit" name="salvar">Salvar alterações</button>

    </form>
</div>

<script>
    // máscaras telefone/celular
    document.getElementById('telefone').addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10) v = v.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        else if (v.length > 6) v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        else if (v.length > 2) v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
        else if (v.length > 0) v = '(' + v;
        e.target.value = v;
    });

    document.getElementById('celular').addEventListener('input', (e) => {
        let v = e.target.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 10) v = v.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        else if (v.length > 6) v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        else if (v.length > 2) v = v.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
        else if (v.length > 0) v = '(' + v;
        e.target.value = v;
    });

    // alertas do PHP
    <?php if ($mensagem === 'sucesso'): ?>
    Swal.fire({ icon: 'success', title: 'Dados atualizados!', text: 'Suas informações foram salvas.' });
    <?php elseif ($mensagem === 'email_duplicado'): ?>
    Swal.fire({ icon: 'error', title: 'E-mail em uso', text: 'Este e-mail já está cadastrado em outra conta.' });
    <?php elseif ($mensagem === 'erro_db'): ?>
    Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível salvar. Tente novamente.' });
    <?php endif; ?>
</script>

</body>
</html>

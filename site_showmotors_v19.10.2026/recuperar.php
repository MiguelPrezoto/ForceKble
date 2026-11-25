<?php
session_start();
include("conexao.php");

$mensagem = "";

if (isset($_POST['verificar'])) {

    $email        = trim($_POST['email']);
    $nome_usuario = trim($_POST['nome_usuario']);

    $stmt = $conn->prepare(
        "SELECT id_usuario, email FROM usuario WHERE email = ? AND nome_usuario = ?"
    );
    $stmt->bind_param("ss", $email, $nome_usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {

        $usuario = $res->fetch_assoc();

        // gera token de 6 digitos e validade de 15 minutos
        $token   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expira  = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // invalida tokens anteriores do mesmo usuario
        $conn->prepare("UPDATE log_recuperacao SET usado = 1 WHERE id_usuario = ? AND usado = 0")
             ->execute([$usuario['id_usuario']]);

        // salva o novo token
        $ins = $conn->prepare(
            "INSERT INTO log_recuperacao (id_usuario, email, token, expira_em) VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("isss", $usuario['id_usuario'], $email, $token, $expira);
        $ins->execute();
        $idLog = $conn->insert_id;

        // tenta enviar email (funciona de verdade se SMTP estiver configurado)
        $assunto  = "ShowMotors - Código de recuperação de senha";
        $corpo    = "Seu código de recuperação é: $token\nVálido por 15 minutos.";
        $cabecalho = "From: noreply@showmotors.com.br";
        $enviado  = mail($email, $assunto, $corpo, $cabecalho) ? 1 : 0;

        // registra resultado do envio no log
        $conn->prepare("UPDATE log_recuperacao SET enviado = ? WHERE id = ?")
             ->execute([$enviado, $idLog]);

        // salva na sessao para validar na proxima tela
        $_SESSION['recuperacao_id_usuario'] = $usuario['id_usuario'];
        $_SESSION['recuperacao_token']      = $token;

        header("Location: codigo.php");
        exit();

    } else {

        $mensagem = '<script>
        Swal.fire({
            icon: "error",
            title: "Dados não encontrados",
            text: "E-mail ou telefone incorretos."
        });
        </script>';
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Recuperar Senha</title>
</head>

<body>

<header class="topo">
    <div class="lado-esquerdo">
        <h1 class="titulo">ShowMotors</h1>
    </div>
    <div class="usuario">
        <a class="link" href="login.php">Voltar</a>
    </div>
</header>

<div class="caixa">
    <form method="POST">

        <label for="email">E-mail</label>
        <input type="email" name="email" id="email" required>

        <label for="nome_usuario">Nome de Usuário</label>
        <input type="text" name="nome_usuario" id="nome_usuario" required>

        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <button type="submit" name="verificar">Confirmar</button>

    </form>
</div>

</body>
</html>

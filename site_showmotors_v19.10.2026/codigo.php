<?php
session_start();
include("conexao.php");

if (!isset($_SESSION['recuperacao_token'])) {
    header("Location: recuperar.php");
    exit();
}

$mensagem = "";

if (isset($_POST['validar'])) {

    $digitado = trim($_POST['codigo']);
    $token    = $_SESSION['recuperacao_token'];
    $idUser   = $_SESSION['recuperacao_id_usuario'];

    // verifica se o token ainda e valido (nao expirado e nao usado)
    $stmt = $conn->prepare(
        "SELECT id FROM log_recuperacao
         WHERE id_usuario = ? AND token = ? AND usado = 0 AND expira_em > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("is", $idUser, $digitado);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0 && $digitado === $token) {

        $logId = $res->fetch_assoc()['id'];

        // marca token como usado
        $conn->prepare("UPDATE log_recuperacao SET usado = 1 WHERE id = ?")
             ->execute([$logId]);

        $_SESSION['recuperacao_liberada'] = true;
        unset($_SESSION['recuperacao_token']);

        header("Location: nova_senha.php");
        exit();

    } else {

        $mensagem = '<script>
        Swal.fire({
            icon: "error",
            title: "Código inválido",
            text: "O código está incorreto ou expirou. Solicite um novo."
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
    <title>Confirmar Código</title>
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

        <p style="margin-bottom:16px; font-size:0.9rem; color:#555;">
            Um código de 6 dígitos foi gerado.<br>
            <small>Se o e-mail estiver configurado, ele foi enviado. Caso contrário, peça ao atendente.</small>
        </p>

        <label for="codigo">Código</label>
        <input type="text" name="codigo" id="codigo" maxlength="6" required autocomplete="off">

        <?php if (!empty($mensagem)) echo $mensagem; ?>

        <button type="submit" name="validar">Confirmar</button>

    </form>
</div>

</body>
</html>

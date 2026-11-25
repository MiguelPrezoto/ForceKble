<?php

session_start();
include("conexao.php");

$mensagem = "";

if(isset($_POST['entrar'])){

    $nome_usuario = trim($_POST['nome_usuario']);
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT * FROM USUARIO WHERE nome_usuario = ?");
    $stmt->bind_param("s", $nome_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if($resultado->num_rows > 0){

        $usuario = $resultado->fetch_assoc();

        if(password_verify($senha, $usuario['senha'])){

            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['usuario'] = $usuario['nome_usuario'];
            // nivel de acesso (usuarios antigos sem nivel viram CLIENTE)
            $_SESSION['nivel'] = $usuario['nivel_acesso'] ?: 'CLIENTE';

            header("Location: home.php");
            exit();

        } else {
            $mensagem = '<script>
            Swal.fire({
                icon: "error",
                title: "Dados incorretos",
                text: "Nome de usuário ou senha incorretos."
            });
            </script>';
        }

    } else {
        $mensagem = '<script>
        Swal.fire({
            icon: "error",
            title: "Dados incorretos",
            text: "Nome de usuário ou senha incorretos."
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
    <script src="js/script.js"></script>
    <title>Login</title>
</head>

<header class="topo">
        <div class="lado-esquerdo">

            <h1 class="titulo">ShowMotors</h1>

            <nav class="nav-principal">
                <a href="sobre.php"><i class="fa-solid fa-circle-info"></i>Sobre</a>
                <a href="index.php"><i class="fa-solid fa-arrow-left"></i>Voltar</a>
            </nav>
        </div>
    </header>

<body>

<div class="caixa">

    <form method="POST" class="texto">

        Nome de Usuário:<br>
        <input type="text" name="nome_usuario" required> <br><br>

        Senha:<br>
        <input type="password" name="senha" required><br><br>

<?php
    if(!empty($mensagem)){
        echo $mensagem;
    }
?>

        <button type="submit" name="entrar">
            Confirmar
        </button> 

        <div style="text-align:center; margin-top:16px;">
            Não possui conta?<br>
            <a class="Texto02" href="cadastro.php">Cadastre-se aqui</a>
        </div>

        <div style="text-align:center; margin-top:12px;">
            Perdeu a senha?<br>
            <a class="Texto02" href="recuperar.php">Recupere-a aqui</a>
        </div>

    </form>
</div>

</body>
</html>

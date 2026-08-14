<?php

include("conexao.php");

$mensagem = "";

if(isset($_POST['entrar'])){

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios
            WHERE email='$email'
            AND senha='$senha'";

    $resultado = $conn->query($sql);

    if($resultado->num_rows > 0){

        $usuario = $resultado->fetch_assoc();

        $_SESSION['usuario'] = $usuario['nome'];
        $_SESSION['email'] = $usuario['email'];

        header("Location: index.php");
        exit();

    }else{

        $mensagem = "E-mail ou senha incorretos.";

    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Login</title>
</head>

<style>

.link{
    text-decoration: none;
    color: rgb(26, 26, 26);
    background-color: aliceblue;
    border-radius: 10px;
    padding-inline: 20px;
    padding-block: 3px;
    font-weight: bold;
    font-size: large;
    
}

body{
    background-image: url("assets/oficina.png");
    background-size: cover;      
    background-position: center; 
    background-repeat: no-repeat;

    margin: 0;
    min-height: 100vh;
}
.caixa{
    background-color: #fff;
    border: 3.5px #eb0404ff solid;
    border-radius: 10px;
    padding: 10px;
    display: flex;
    justify-content: center; /* horizontal */
    align-items: center;  
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
}
.BTN_cadastro{
    border-radius: 5vh;
    border: none;
    font-size: small;
    margin-top:  1vh;
    margin-right: 1vh;
    color: aliceblue;
}
.Texto{
    color:aliceblue;
}
.Texto02{
    color:rgb(26, 26, 26);
    text-decoration: none;
    font-weight: bold;
}
.button{
    padding: 10px;
}
.caixa2{
    padding-top: 10px;
}

</style>
<header class="topo">
        <div class="lado-esquerdo">
            <button class="botao-menu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <h1 class="titulo">Oficina Show Motors</h1>
        </div>

        <div class="usuario">
            <div>
                <a class="link" href="index.php">Voltar</a>
            </div>
        </div>
    </header>

<body>

<div class="caixa">

    <form method="POST" class="texto">

        Email:<br>
        <input type="email" name="email" required> <br><br>

        Senha:<br>
        <input type="password" name="senha" required><br><br>

<?php
    if(!empty($mensagem)){
        echo "<p class='Texto'>$mensagem</p>";
    }
?>

        <button type="submit" name="entrar">
            Confirmar
        </button> 

        <br><br>

<a class="Texto02" href="cadastro.php"> Não possui conta? Cadastre-se </a>
    </form>

<br><br>

<a class="Texto02" href="recuperar.php"> Perdeu a senha? Clique aqui </a>

</div>

</body>
</html>
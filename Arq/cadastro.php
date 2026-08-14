<?php

include("conexao.php");

$mensagem = "";

if(isset($_POST['cadastrar'])){

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $senha = $_POST['senha'];
    $confirmar = $_POST['confirmarSenha'];

    if($senha != $confirmar){

        $mensagem = "As senhas não coincidem.";

    }else{

        $verifica = $conn->query(
            "SELECT * FROM usuarios WHERE email='$email'"
        );

        if($verifica->num_rows > 0){

            $mensagem = "Este e-mail já está cadastrado.";

        }else{

            $sql = "INSERT INTO usuarios
            (nome,email,telefone,senha)
            VALUES
            ('$nome','$email','$telefone','$senha')";

            if($conn->query($sql)){

                echo "
                <script>
                alert('Cadastro realizado com sucesso!');
                window.location='login.php';
                </script>";

                exit();
            }
        }
    }
}
?>
<HTML>
<HEAD>
 <TITLE>cadastro</TITLE>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
    background-color: #2563eb;
    border-radius: 15px;
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
.caixa2{
    padding: 20px;
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
                <a class="link" href="login.php">Voltar</a>
            </div>
        </div>
    </header>

<body>

<div class="caixa">

    <form method="POST">

        Nome:<br>
        <input type="text" name="nome" required><br><br>

        Email:<br>
        <input type="email" name="email" required><br><br>

        Telefone:<br>
        <input type="text" name="telefone" required><br><br>

        Senha:<br>
        <input type="password" name="senha" required><br><br>

        Confirmar senha:<br>
        <input type="password" name="confirmarSenha" required><br><br>

        <?php
            if(!empty($mensagem)){
            echo "<p>$mensagem</p>";
            }
        ?>

        <button type="submit" name="cadastrar">
            Confirmar
        </button>

    </form>

</div>

</body>
</html>
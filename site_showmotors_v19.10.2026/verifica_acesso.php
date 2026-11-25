<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// barra quem nao esta logado ou nao tem um dos niveis permitidos
// uso: exigirNivel(['ADM']);  ou  exigirNivel(['ADM', 'FUNCIONARIO']);
function exigirNivel(array $permitidos)
{
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }

    // sessao antiga (antes dos niveis) sem nivel definido vira CLIENTE
    if (empty($_SESSION['nivel'])) {
        $_SESSION['nivel'] = 'CLIENTE';
    }

    if (!in_array($_SESSION['nivel'], $permitidos, true)) {
        header("Location: home.php");
        exit();
    }
}

?>

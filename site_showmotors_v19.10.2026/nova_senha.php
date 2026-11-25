<?php
session_start();
include("conexao.php");

if (!isset($_SESSION['recuperacao_liberada']) || !isset($_SESSION['recuperacao_id_usuario'])) {
    header("Location: login.php");
    exit();
}

$mensagem = "";
$idUser   = $_SESSION['recuperacao_id_usuario'];

if (isset($_POST['alterar'])) {

    $senha    = $_POST['senha'];
    $confirmar = $_POST['confirmarSenha'];

    // requisitos de segurança (mesmo do cadastro)
    $forte = strlen($senha) >= 8 && strlen($senha) <= 128
          && preg_match('/[A-Z]/', $senha)
          && preg_match('/[a-z]/', $senha)
          && preg_match('/[0-9]/', $senha)
          && preg_match('/[^A-Za-z0-9]/', $senha);

    if ($senha !== $confirmar) {

        $mensagem = 'senhas_diferentes';

    } elseif (!$forte) {

        $mensagem = 'senha_fraca';

    } else {

        // verifica se a nova senha é igual à atual
        $stmtAtual = $conn->prepare("SELECT senha FROM usuario WHERE id_usuario = ?");
        $stmtAtual->bind_param("i", $idUser);
        $stmtAtual->execute();
        $senhaAtual = $stmtAtual->get_result()->fetch_assoc()['senha'];
        $stmtAtual->close();

        if (password_verify($senha, $senhaAtual)) {

            $mensagem = 'senha_igual';

        } else {

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuario SET senha = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $senhaHash, $idUser);

            if ($stmt->execute()) {
                unset($_SESSION['recuperacao_liberada'], $_SESSION['recuperacao_id_usuario']);
                $mensagem = 'sucesso';
            } else {
                $mensagem = 'erro_db';
            }

            $stmt->close();
        }
    }
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
    <title>Nova Senha</title>
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
    <form method="POST" id="form-nova-senha">

        <label for="senha">Nova senha</label>
        <input type="password" name="senha" id="senha" required>

        <ul id="checklist-senha" style="list-style:none; padding:0; margin:4px 0 10px; font-size:0.75rem; display:flex; flex-direction:column; gap:4px;">
            <li id="chk-len" style="color:#aaa;">&#10007; 8 a 128 caracteres</li>
            <li id="chk-mai" style="color:#aaa;">&#10007; Letra maiúscula</li>
            <li id="chk-min" style="color:#aaa;">&#10007; Letra minúscula</li>
            <li id="chk-num" style="color:#aaa;">&#10007; Número</li>
            <li id="chk-esp" style="color:#aaa;">&#10007; Caractere especial</li>
        </ul>

        <label for="confirmarSenha">Confirmar senha</label>
        <input type="password" name="confirmarSenha" id="confirmarSenha" required>

        <button type="submit" name="alterar">Confirmar</button>

    </form>
</div>

<script>
    // checklist
    const senhaCampo = document.getElementById('senha');
    const chks = {
        len: document.getElementById('chk-len'),
        mai: document.getElementById('chk-mai'),
        min: document.getElementById('chk-min'),
        num: document.getElementById('chk-num'),
        esp: document.getElementById('chk-esp'),
    };

    function marcar(el, ok) {
        el.textContent = (ok ? '✔ ' : '✘ ') + el.textContent.slice(2);
        el.style.color = ok ? '#6abf69' : '#aaa';
    }

    function senhaValida(v) {
        return v.length >= 8 && v.length <= 128
            && /[A-Z]/.test(v) && /[a-z]/.test(v)
            && /[0-9]/.test(v) && /[^A-Za-z0-9]/.test(v);
    }

    senhaCampo.addEventListener('input', () => {
        const v = senhaCampo.value;
        marcar(chks.len, v.length >= 8 && v.length <= 128);
        marcar(chks.mai, /[A-Z]/.test(v));
        marcar(chks.min, /[a-z]/.test(v));
        marcar(chks.num, /[0-9]/.test(v));
        marcar(chks.esp, /[^A-Za-z0-9]/.test(v));
    });

    // validacao no submit
    document.getElementById('form-nova-senha').addEventListener('submit', (e) => {
        if (!senhaValida(senhaCampo.value)) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Senha fraca',
                text: 'A senha deve ter entre 8 e 128 caracteres, incluindo maiúscula, minúscula, número e caractere especial.'
            });
        }
    });

    // alertas vindos do PHP
    <?php if ($mensagem === 'sucesso'): ?>
    Swal.fire({
        icon: 'success',
        title: 'Senha alterada!',
        text: 'Sua senha foi redefinida com sucesso.',
        confirmButtonText: 'Entrar'
    }).then(() => window.location = 'login.php');
    <?php elseif ($mensagem === 'senhas_diferentes'): ?>
    Swal.fire({ icon: 'error', title: 'Senhas diferentes', text: 'As senhas não coincidem.' });
    <?php elseif ($mensagem === 'senha_fraca'): ?>
    Swal.fire({ icon: 'error', title: 'Senha fraca', text: 'A senha não atende aos requisitos de segurança.' });
    <?php elseif ($mensagem === 'senha_igual'): ?>
    Swal.fire({ icon: 'warning', title: 'Senha igual', text: 'A nova senha não pode ser igual à senha atual.' });
    <?php elseif ($mensagem === 'erro_db'): ?>
    Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível alterar a senha. Tente novamente.' });
    <?php endif; ?>
</script>

</body>
</html>

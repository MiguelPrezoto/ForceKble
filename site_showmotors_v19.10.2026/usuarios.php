<?php
// area de administracao: so ADM entra
include("verifica_acesso.php");
exigirNivel(['ADM']);

include("conexao.php");
include("funcoes_username.php");

$niveis = ['ADM', 'FUNCIONARIO', 'CLIENTE'];
$mensagem = "";
$iconeMensagem = "info";

// excluir um usuario
if (isset($_POST['excluir_usuario'])) {
    $id = (int) $_POST['id_usuario'];

    if ($id === (int) $_SESSION['id_usuario']) {
        // evita o ADM excluir a propria conta e ficar sem acesso
        $mensagem = "Você não pode excluir o seu próprio usuário.";
        $iconeMensagem = "error";
    } else {
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensagem = "Usuário excluído.";
        $iconeMensagem = "success";
    }
}

// salvar edicao de um usuario (dados + nivel)
if (isset($_POST['salvar_usuario'])) {
    $id       = (int) $_POST['id_usuario'];
    $nome     = trim($_POST['nome']);
    $email    = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $celular  = trim($_POST['celular']);
    $cpf      = trim($_POST['cpf']);
    $nivel    = $_POST['nivel'] ?? '';

    if (!in_array($nivel, $niveis, true)) {
        $mensagem = "Nível inválido.";
        $iconeMensagem = "error";
    } elseif ($nome === '') {
        $mensagem = "Informe o nome.";
        $iconeMensagem = "error";
    } elseif ($id === (int) $_SESSION['id_usuario'] && $nivel !== $_SESSION['nivel']) {
        // evita o ADM se rebaixar e o sistema ficar sem administrador
        $mensagem = "Você não pode alterar o seu próprio nível.";
        $iconeMensagem = "error";
    } else {
        $verifica = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario != ?");
        $verifica->bind_param("si", $email, $id);
        $verifica->execute();
        $emailEmUso = $verifica->get_result()->num_rows > 0;
        $verifica->close();

        if ($emailEmUso) {
            $mensagem = "Este e-mail já está em uso por outro usuário.";
            $iconeMensagem = "error";
        } else {
            $stmt = $conn->prepare(
                "UPDATE usuario SET nome_cliente = ?, email = ?, telefone = ?, celular = ?, cpf = ?, nivel_acesso = ?
                 WHERE id_usuario = ?"
            );
            $stmt->bind_param("ssssssi", $nome, $email, $telefone, $celular, $cpf, $nivel, $id);
            $stmt->execute();
            $stmt->close();
            $mensagem = "Usuário atualizado.";
            $iconeMensagem = "success";
        }
    }
}

// criar novo usuario (qualquer nivel)
$abrirModalComErro = false;

if (isset($_POST['criar_usuario'])) {
    $nome      = trim($_POST['nome']);
    $cpf       = trim($_POST['cpf']);
    $email     = trim($_POST['email']);
    $telefone  = trim($_POST['telefone']);
    $celular   = trim($_POST['celular']);
    $senha     = $_POST['senha'];
    $confirmar = $_POST['confirmarSenha'];
    $nivel     = $_POST['nivel'] ?? '';

    $verifica = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
    $verifica->bind_param("s", $email);
    $verifica->execute();
    $emailExiste = $verifica->get_result()->num_rows > 0;
    $verifica->close();

    if (!in_array($nivel, $niveis, true)) {
        $mensagem = "Nível inválido.";
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    } elseif ($nome === '') {
        $mensagem = "Informe o nome.";
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    } elseif ($senha !== $confirmar) {
        $mensagem = "As senhas não coincidem.";
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    } elseif ($emailExiste) {
        $mensagem = "Este e-mail já está cadastrado.";
        $iconeMensagem = "error";
        $abrirModalComErro = true;
    } else {
        $nomeUser  = gerarUsername($conn, $nome);
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO usuario (nome_cliente, nome_usuario, email, telefone, celular, cpf, senha, nivel_acesso)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssssss", $nome, $nomeUser, $email, $telefone, $celular, $cpf, $senhaHash, $nivel);

        if ($stmt->execute()) {
            $mensagem = "Usuário criado. Nome de usuário: " . $nomeUser;
            $iconeMensagem = "success";
        } else {
            $mensagem = "Erro ao criar usuário.";
            $iconeMensagem = "error";
            $abrirModalComErro = true;
        }
        $stmt->close();
    }
}

$usuarios = $conn->query(
    "SELECT id_usuario, nome_cliente, nome_usuario, email, telefone, celular, cpf, nivel_acesso
     FROM usuario ORDER BY nome_cliente"
);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Oficina Show Motors</title>
    <link rel="stylesheet" href="css/estilizacao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* table-layout fixed + larguras em % garantem que a tabela sempre cabe no espaco
           disponivel, sem precisar de scroll horizontal */
        .tabela-usuarios { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .tabela-usuarios th { background: #f5f6fa; text-align: left; padding: 8px 10px; font-size: 13px; border: 1px solid #d1d5db; }
        .tabela-usuarios td { padding: 8px 10px; font-size: 13px; border: 1px solid #d1d5db; overflow-wrap: break-word; }

        .tabela-usuarios th:nth-child(1), .tabela-usuarios td:nth-child(1) { width: 14%; }
        .tabela-usuarios th:nth-child(2), .tabela-usuarios td:nth-child(2) { width: 10%; }
        .tabela-usuarios th:nth-child(3), .tabela-usuarios td:nth-child(3) { width: 16%; }
        .tabela-usuarios th:nth-child(4), .tabela-usuarios td:nth-child(4) { width: 12%; }
        .tabela-usuarios th:nth-child(5), .tabela-usuarios td:nth-child(5) { width: 12%; }
        .tabela-usuarios th:nth-child(6), .tabela-usuarios td:nth-child(6) { width: 12%; }
        .tabela-usuarios th:nth-child(7), .tabela-usuarios td:nth-child(7) { width: 10%; }
        .tabela-usuarios th:nth-child(8), .tabela-usuarios td:nth-child(8) { width: 14%; }

        .tabela-usuarios td select {
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            width: 100%;
        }
        .acoes-usuario { display: flex; gap: 6px; }
        .tabela-usuarios-scroll { overflow-x: auto; }

        /* coluna "Acoes" fixa na borda direita: protege em telas bem estreitas,
           caso ainda sobre algum scroll residual */
        .tabela-usuarios th:last-child,
        .tabela-usuarios td:last-child {
            position: sticky;
            right: 0;
            background: white;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.08);
        }
        .tabela-usuarios th:last-child { background: #f5f6fa; }

        .tabela-usuarios .valor-edicao {
            display: none;
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
        }
        .tabela-usuarios tr.modo-edicao .valor-exibicao { display: none; }
        .tabela-usuarios tr.modo-edicao .valor-edicao { display: inline-block; }
        .botao-editar {
            border: 2px solid #eb0404;
            background: white;
            color: #eb0404;
            padding: 5px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(235, 4, 4, 0.15);
        }
        .botao-editar:hover { background: #fef2f2; }
        .tabela-usuarios .botao-excluir,
        .tabela-usuarios .botao-salvar {
            padding: 5px 8px;
            font-size: 12px;
        }
        .escondido { display: none; }
    </style>
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
        <h2><i class="fa-solid fa-users-gear"></i> Controle de Usuários</h2>
        <button type="button" class="botao-padrao" id="btn-abrir-modal"><i class="fa-solid fa-plus"></i> Adicionar</button>
    </div>

    <section class="bloco-relatorio">
        <div class="tabela-usuarios-scroll">
            <table class="tabela-usuarios">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Celular</th>
                        <th>CPF</th>
                        <th>Nível</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $usuarios->fetch_assoc()): ?>
                        <?php $nivelAtual = $u['nivel_acesso'] ?: 'CLIENTE'; ?>
                        <tr>
                            <td>
                                <span class="valor-exibicao"><?= htmlspecialchars($u['nome_cliente']) ?></span>
                                <input type="text" class="valor-edicao" name="nome" value="<?= htmlspecialchars($u['nome_cliente']) ?>" form="form-<?= $u['id_usuario'] ?>">
                            </td>
                            <td><?= htmlspecialchars($u['nome_usuario']) ?></td>
                            <td>
                                <span class="valor-exibicao"><?= htmlspecialchars($u['email']) ?></span>
                                <input type="email" class="valor-edicao" name="email" value="<?= htmlspecialchars($u['email']) ?>" form="form-<?= $u['id_usuario'] ?>">
                            </td>
                            <td>
                                <span class="valor-exibicao"><?= htmlspecialchars($u['telefone'] ?: '—') ?></span>
                                <input type="text" class="valor-edicao campo-telefone" name="telefone" maxlength="14" placeholder="(00) 0000-0000" value="<?= htmlspecialchars($u['telefone'] ?? '') ?>" form="form-<?= $u['id_usuario'] ?>">
                            </td>
                            <td>
                                <span class="valor-exibicao"><?= htmlspecialchars($u['celular'] ?: '—') ?></span>
                                <input type="text" class="valor-edicao campo-telefone" name="celular" maxlength="15" placeholder="(00) 00000-0000" value="<?= htmlspecialchars($u['celular'] ?? '') ?>" form="form-<?= $u['id_usuario'] ?>">
                            </td>
                            <td>
                                <span class="valor-exibicao"><?= htmlspecialchars($u['cpf'] ?: '—') ?></span>
                                <input type="text" class="valor-edicao campo-cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" value="<?= htmlspecialchars($u['cpf'] ?? '') ?>" form="form-<?= $u['id_usuario'] ?>">
                            </td>
                            <td>
                                <form method="POST" id="form-<?= $u['id_usuario'] ?>">
                                    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                    <select name="nivel" disabled>
                                        <?php foreach ($niveis as $n): ?>
                                            <option value="<?= $n ?>" <?= $n === $nivelAtual ? 'selected' : '' ?>><?= $n ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="acoes-usuario">
                                    <button type="submit" class="botao-excluir" name="excluir_usuario" form="form-<?= $u['id_usuario'] ?>" title="Excluir"><i class="fa-solid fa-trash"></i></button>
                                    <button type="button" class="botao-editar" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                    <button type="submit" class="botao-salvar escondido" name="salvar_usuario" form="form-<?= $u['id_usuario'] ?>" title="Salvar"><i class="fa-solid fa-floppy-disk"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
    // mascaras (mesma logica usada em cadastro.php) - compartilhadas entre o modal e a edicao na tabela
    function aplicarMascaraTelefone(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 11) valor = valor.slice(0, 11);
        if (valor.length > 10) {
            valor = valor.replace(/^(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (valor.length > 6) {
            valor = valor.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else if (valor.length > 2) {
            valor = valor.replace(/^(\d{2})(\d{0,5})/, '($1) $2')
        } else if (valor.length > 0) {
            valor = valor.replace(/^(\d*)/, '($1')
        }
        e.target.value = valor;
    }

    function aplicarMascaraCpf(e) {
        let valor = e.target.value.replace(/\D/g, '');
        if (valor.length > 11) valor = valor.slice(0, 11);

        if (valor.length > 9) {
            valor = valor.replace(/^(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
        } else if (valor.length > 6) {
            valor = valor.replace(/^(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
        } else if (valor.length > 3) {
            valor = valor.replace(/^(\d{3})(\d{0,3})/, '$1.$2')
        }

        e.target.value = valor;
    }

    // valida CPF pelos dois digitos verificadores
    function cpfValido(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;
        for (let i = 0; i < 9; i++) soma += parseInt(cpf[i]) * (10 - i);
        const dv1 = soma % 11 < 2 ? 0 : 11 - (soma % 11);

        soma = 0;
        for (let i = 0; i < 10; i++) soma += parseInt(cpf[i]) * (11 - i);
        const dv2 = soma % 11 < 2 ? 0 : 11 - (soma % 11);

        return dv1 === parseInt(cpf[9]) && dv2 === parseInt(cpf[10]);
    }

    // editar/salvar por linha na tabela de usuarios
    document.querySelectorAll('.botao-editar').forEach((btnEditar) => {
        btnEditar.addEventListener('click', () => {
            const linha = btnEditar.closest('tr');
            linha.classList.add('modo-edicao');

            const select = linha.querySelector('select[name="nivel"]');
            if (select) select.disabled = false;

            btnEditar.classList.add('escondido');
            linha.querySelector('.botao-salvar').classList.remove('escondido');
        });
    });

    // confirmacao antes de excluir um usuario
    document.querySelectorAll('.botao-excluir').forEach((btnExcluir) => {
        btnExcluir.addEventListener('click', (e) => {
            e.preventDefault();

            const linha = btnExcluir.closest('tr');
            const nome = linha.querySelector('.valor-exibicao').textContent.trim();

            Swal.fire({
                icon: 'warning',
                title: 'Excluir usuário?',
                text: `O usuário "${nome}" será excluído permanentemente. Essa ação não pode ser desfeita.`,
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#eb0404'
            }).then((resultado) => {
                if (resultado.isConfirmed) {
                    const form = document.getElementById(btnExcluir.getAttribute('form'));
                    form.requestSubmit(btnExcluir);
                }
            });
        });
    });

    document.querySelectorAll('.tabela-usuarios .campo-telefone').forEach((input) => {
        input.addEventListener('input', aplicarMascaraTelefone);
    });

    document.querySelectorAll('.tabela-usuarios .campo-cpf').forEach((input) => {
        input.addEventListener('input', aplicarMascaraCpf);
    });

    // impede salvar a linha se o CPF preenchido for invalido
    document.querySelectorAll('.tabela-usuarios form').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!(e.submitter && e.submitter.name === 'salvar_usuario')) return;

            const campoCpf = document.querySelector('.campo-cpf[form="' + form.id + '"]');
            if (campoCpf.value.trim() !== '' && !cpfValido(campoCpf.value)) {
                e.preventDefault();
                campoCpf.style.borderColor = 'red';
                Swal.fire({ icon: 'error', title: 'CPF inválido', text: 'Verifique o CPF digitado.' });
                campoCpf.focus();
            }
        });
    });
</script>

<div class="modal-overlay" id="modal-novo-usuario">
    <div class="modal-caixa">
        <div class="modal-cabecalho">
            <h2><i class="fa-solid fa-user-plus"></i> Novo usuário</h2>
            <button type="button" class="modal-fechar" id="btn-fechar-modal">&times;</button>
        </div>

        <form method="POST" id="form-novo-usuario">

            <label for="nome-novo">Nome Completo</label>
            <input type="text" id="nome-novo" name="nome" required>

            <label for="cpf-novo">CPF</label>
            <input type="text" id="cpf-novo" name="cpf">

            <label for="email-novo">E-mail</label>
            <input type="email" id="email-novo" name="email" required>

            <label for="telefone-novo">Telefone</label>
            <input type="text" id="telefone-novo" name="telefone" placeholder="(00) 0000-0000" maxlength="14">

            <label for="celular-novo">Celular</label>
            <input type="text" id="celular-novo" name="celular" placeholder="(00) 00000-0000" maxlength="15">

            <label for="senha-novo">Senha</label>
            <input type="password" id="senha-novo" name="senha" required>

            <label for="confirmar-novo">Confirmar senha</label>
            <input type="password" id="confirmar-novo" name="confirmarSenha" required>

            <label for="nivel-novo">Nível</label>
            <select id="nivel-novo" name="nivel">
                <?php foreach ($niveis as $n): ?>
                    <option value="<?= $n ?>"><?= $n ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="botao-padrao" name="criar_usuario">Criar</button>

        </form>
    </div>
</div>

<script>
    // abrir/fechar modal
    const modalNovoUsuario = document.getElementById('modal-novo-usuario');
    const btnAbrirModal = document.getElementById('btn-abrir-modal');
    const btnFecharModal = document.getElementById('btn-fechar-modal');

    btnAbrirModal.addEventListener('click', () => modalNovoUsuario.classList.add('aberto'));
    btnFecharModal.addEventListener('click', () => modalNovoUsuario.classList.remove('aberto'));
    modalNovoUsuario.addEventListener('click', (e) => {
        if (e.target === modalNovoUsuario) modalNovoUsuario.classList.remove('aberto');
    });

    // mascaras (funcoes compartilhadas definidas no script anterior)
    document.getElementById('telefone-novo').addEventListener('input', aplicarMascaraTelefone);
    document.getElementById('celular-novo').addEventListener('input', aplicarMascaraTelefone);

    const cpfCampoNovo = document.getElementById('cpf-novo');
    let cpfAlertadoNovo = '';

    cpfCampoNovo.addEventListener('input', (e) => {
        aplicarMascaraCpf(e);

        const cpfNum = e.target.value.replace(/\D/g, '');
        if (cpfNum.length === 11) {
            if (cpfValido(cpfNum)) {
                cpfCampoNovo.style.borderColor = '';
            } else {
                cpfCampoNovo.style.borderColor = 'red';
                if (cpfAlertadoNovo !== cpfNum) {
                    cpfAlertadoNovo = cpfNum;
                    Swal.fire({
                        icon: 'error',
                        title: 'CPF inválido',
                        text: 'O CPF digitado não é válido. Verifique os números.',
                        confirmButtonText: 'OK'
                    }).then(() => cpfCampoNovo.focus());
                }
            }
        } else {
            cpfCampoNovo.style.borderColor = '';
            cpfAlertadoNovo = '';
        }
    });

    // impede o envio do formulario se o CPF for invalido
    document.getElementById('form-novo-usuario').addEventListener('submit', (e) => {
        if (!cpfValido(cpfCampoNovo.value)) {
            e.preventDefault();
            cpfCampoNovo.style.borderColor = 'red';
            Swal.fire({
                icon: 'error',
                title: 'CPF inválido',
                text: 'Verifique o CPF digitado.'
            });
            cpfCampoNovo.focus();
        }
    });
</script>

<?php if ($mensagem): ?>
<script>
    Swal.fire({ icon: '<?= $iconeMensagem ?>', text: <?= json_encode($mensagem) ?> });
    <?php if ($abrirModalComErro): ?>
    document.getElementById('modal-novo-usuario').classList.add('aberto');
    <?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>

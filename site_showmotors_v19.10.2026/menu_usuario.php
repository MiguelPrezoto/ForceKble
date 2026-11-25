<div class="usuario">
    <div class="menu-usuario" id="menuUsuario">

        <button type="button" class="menu-usuario-botao" onclick="abrirMenu(event)">
            <span class="nome-usuario"><?= htmlspecialchars($_SESSION['usuario']) ?></span>
            <span class="foto-usuario"><i class="fa-solid fa-user"></i></span>
            <i class="fa-solid fa-chevron-down seta-menu"></i>
        </button>

        <div class="menu-usuario-opcoes">
            <a class="menu-usuario-item" href="meus_dados.php">
                <i class="fa-solid fa-user-pen"></i> Meus dados
            </a>

            <a class="menu-usuario-item sair" href="logout.php">
                <i class="fa-solid fa-right-from-bracket"></i> Sair
            </a>
        </div>

    </div>
</div>

<script>
    function abrirMenu(e) {
        e.stopPropagation();
        document.getElementById('menuUsuario').classList.toggle('aberto');
    }

    // clicou fora fecha o menu
    document.addEventListener('click', function () {
        document.getElementById('menuUsuario').classList.remove('aberto');
    });
</script>

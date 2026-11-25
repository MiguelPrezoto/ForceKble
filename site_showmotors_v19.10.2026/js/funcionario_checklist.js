// checklist_funcionario.php: tarefas de cada atendimento, progresso e salvamento

const listaEl = document.getElementById('lista-atendimentos');
const resumoEl = document.getElementById('resumo-atendimentos');
const paginacaoEl = document.getElementById('paginacao');
const painelEl = document.getElementById('painel-checklist');
const campoBusca = document.getElementById('busca-atendimento');

const filtros = { status: 'ATIVOS', busca: '', pagina: 1, ordem: 'asc' };

// atendimento aberto: { agendamento, itens, editavel }
let atual = null;
let alterado = false;

// ---------------- lista de atendimentos em aberto ----------------

async function carregarAtendimentos() {
    try {
        const dados = await api('agendamentos', filtros);
        paginacaoEl.innerHTML = htmlPaginacao(dados.pagina, dados.paginas);
        resumoEl.textContent = dados.total === 0
            ? 'Nenhum atendimento em aberto.'
            : dados.total + ' atendimento(s) em aberto';
        listaEl.innerHTML = dados.lista.map((a) =>
            '<button type="button" class="item-atendimento' + (atual && atual.agendamento.id_agendamento === a.id_agendamento ? ' ativo' : '') + '" data-id="' + a.id_agendamento + '">' +
            '<div class="linha"><strong>' + htmlVeiculo(a) + '</strong>' + htmlBadgeStatus(a.status) + '</div>' +
            '<small>' + escaparHtml(a.nome_cliente || a.nome_usuario || '') + ' &middot; ' + formatarDataHora(a.data_hora_servico) + '</small>' +
            htmlProgresso(a.checklist_feitos, a.checklist_total) +
            '</button>'
        ).join('');
    } catch (e) {
        mostrarErro(e);
    }
}

listaEl.addEventListener('click', (e) => {
    const item = e.target.closest('.item-atendimento');
    if (item) abrirAtendimento(Number(item.dataset.id));
});

campoBusca.addEventListener('input', debounce(() => {
    filtros.busca = campoBusca.value.trim();
    filtros.pagina = 1;
    carregarAtendimentos();
}, 350));

paginacaoEl.addEventListener('click', (e) => {
    const botao = e.target.closest('[data-pagina]');
    if (!botao || botao.disabled) return;
    filtros.pagina = Number(botao.dataset.pagina);
    carregarAtendimentos();
});

// ---------------- atendimento aberto ----------------

async function confirmarDescarte() {
    if (!alterado) return true;
    const r = await Swal.fire({
        icon: 'warning',
        title: 'Alterações não salvas',
        text: 'Deseja descartar as alterações do checklist atual?',
        showCancelButton: true,
        confirmButtonText: 'Descartar',
        cancelButtonText: 'Voltar',
        confirmButtonColor: '#eb0404'
    });
    return r.isConfirmed;
}

async function abrirAtendimento(id) {
    if (atual && atual.agendamento.id_agendamento === id && !alterado) return;
    if (!(await confirmarDescarte())) return;

    try {
        const dados = await api('checklist', { id: id });
        atual = { agendamento: dados.agendamento, itens: dados.itens, editavel: dados.editavel };
        alterado = false;
        history.replaceState(null, '', '?id=' + id);
        renderizarPainel();
        listaEl.querySelectorAll('.item-atendimento').forEach((b) => b.classList.toggle('ativo', Number(b.dataset.id) === id));
    } catch (e) {
        mostrarErro(e);
    }
}

function renderizarPainel() {
    const a = atual.agendamento;
    const ed = atual.editavel;

    let acoesStatus = '';
    if (a.status === 'AGENDADO') {
        acoesStatus = '<button type="button" class="btn-acao iniciar" data-status="EM_ANDAMENTO"><i class="fa-solid fa-play"></i> Iniciar atendimento</button>';
    } else if (a.status === 'EM_ANDAMENTO') {
        acoesStatus = '<button type="button" class="btn-acao concluir" data-status="CONCLUIDO"><i class="fa-solid fa-flag-checkered"></i> Concluir atendimento</button>';
    }

    painelEl.innerHTML =
        '<div class="topo-historico"><div>' +
        '<p class="cliente-label"><i class="fa-solid fa-user"></i> ' + escaparHtml(a.nome_cliente || a.nome_usuario || 'Cliente removido') + ' &middot; #' + a.id_agendamento + '</p>' +
        '<h3><i class="fa-solid fa-car"></i> ' + htmlVeiculo(a) + '</h3>' +
        '<p><i class="fa-solid fa-calendar-day"></i> ' + formatarDataHora(a.data_hora_servico) + '</p>' +
        (a.descricao ? '<p class="obs-historico"><i class="fa-solid fa-comment"></i> ' + escaparHtml(a.descricao) + '</p>' : '') +
        '</div>' + htmlBadgeStatus(a.status) + '</div>' +
        '<div id="progresso-atual"></div>' +
        (ed ? '' : '<p class="aviso-modal" style="margin-top:14px;"><i class="fa-solid fa-lock"></i> Atendimento encerrado: o checklist está disponível apenas para consulta.</p>') +
        '<ul class="tarefas-checklist" id="tarefas"></ul>' +
        (ed
            ? '<div class="nova-tarefa">' +
              '<input type="text" id="nova-tarefa" maxlength="150" placeholder="Nova tarefa (ex.: verificar nível do óleo)">' +
              '<button type="button" class="btn-acao" id="btn-adicionar"><i class="fa-solid fa-plus"></i> Adicionar</button>' +
              '</div>'
            : '') +
        (ed
            ? '<div class="rodape-checklist">' +
              '<span class="alteracoes-pendentes" id="aviso-alterado"></span>' +
              '<div class="acoes-atendimento">' + acoesStatus +
              '<button type="button" class="btn-acao" id="btn-descartar"><i class="fa-solid fa-rotate-left"></i> Descartar</button>' +
              '<button type="button" class="botao-padrao" id="btn-salvar"><i class="fa-solid fa-floppy-disk"></i> Salvar alterações</button>' +
              '</div></div>'
            : '');

    renderizarTarefas();
}

function renderizarTarefas() {
    const ul = document.getElementById('tarefas');
    const ed = atual.editavel;

    if (atual.itens.length === 0) {
        const temServicos = atual.agendamento.servicos.length > 0;
        ul.innerHTML = '<li class="tarefa"><span class="dica" style="margin:0;">Nenhuma tarefa neste checklist.</span>' +
            (ed && temServicos && !alterado ? '<button type="button" class="btn-acao" id="btn-gerar"><i class="fa-solid fa-wand-magic-sparkles"></i> Criar a partir dos serviços</button>' : '') +
            '</li>';
    } else {
        ul.innerHTML = atual.itens.map((t, i) =>
            '<li class="tarefa' + (t.concluido ? ' feita' : '') + '" data-i="' + i + '">' +
            '<input type="checkbox" aria-label="Concluída"' + (t.concluido ? ' checked' : '') + (ed ? '' : ' disabled') + '>' +
            '<input type="text" class="texto-tarefa" maxlength="150" value="' + escaparHtml(t.descricao) + '"' + (ed ? '' : ' readonly') + '>' +
            (t.concluido && t.concluido_por ? '<span class="autor-tarefa">' + escaparHtml(t.concluido_por) + ' &middot; ' + formatarDataHora(t.data_conclusao) + '</span>' : '') +
            (ed ? '<button type="button" class="btn-remover-tarefa" title="Remover tarefa" aria-label="Remover tarefa"><i class="fa-solid fa-trash"></i></button>' : '') +
            '</li>'
        ).join('');
    }
    atualizarProgresso();
}

function atualizarProgresso() {
    const feitos = atual.itens.filter((t) => t.concluido).length;
    document.getElementById('progresso-atual').innerHTML = htmlProgresso(feitos, atual.itens.length);

    const aviso = document.getElementById('aviso-alterado');
    if (aviso) {
        aviso.innerHTML = alterado ? '<i class="fa-solid fa-circle-exclamation"></i> Alterações não salvas' : '';
        document.getElementById('btn-descartar').disabled = !alterado;
        document.getElementById('btn-salvar').disabled = !alterado;
    }
}

function marcarAlterado() {
    alterado = true;
    atualizarProgresso();
}

function adicionarTarefa() {
    const campo = document.getElementById('nova-tarefa');
    const texto = campo.value.trim();
    if (!texto) {
        campo.focus();
        return;
    }
    atual.itens.push({ id_item: null, descricao: texto, concluido: false, concluido_por: null, data_conclusao: null });
    campo.value = '';
    alterado = true;
    renderizarTarefas();
    campo.focus();
}

// ---------------- salvar / descartar / status ----------------

async function salvar() {
    const vazia = atual.itens.findIndex((t) => !t.descricao.trim());
    if (vazia >= 0) {
        Swal.fire({ icon: 'error', text: 'Toda tarefa precisa de uma descrição.' });
        return false;
    }

    const id = atual.agendamento.id_agendamento;
    const botao = document.getElementById('btn-salvar');
    botao.disabled = true;
    try {
        const dados = await api('checklist_salvar', null, {
            id_agendamento: id,
            itens: atual.itens.map((t) => ({ id_item: t.id_item, descricao: t.descricao.trim(), concluido: t.concluido }))
        });
        atual.itens = dados.itens;
        atual.agendamento = dados.agendamento;
        alterado = false;
        renderizarPainel();
        carregarAtendimentos();
        return true;
    } catch (e) {
        botao.disabled = false;
        mostrarErro(e);
        return false;
    }
}

async function mudarStatus(status) {
    // salva o checklist antes de mudar o status
    if (alterado && !(await salvar())) return;

    const textos = {
        EM_ANDAMENTO: ['Iniciar atendimento?', 'O status muda para "Em andamento".', 'Iniciar', 'Atendimento iniciado.'],
        CONCLUIDO: ['Concluir atendimento?', 'Depois de concluído, o checklist fica apenas para consulta.', 'Concluir', 'Atendimento concluído.']
    }[status];

    const r = await Swal.fire({
        icon: 'question', title: textos[0], text: textos[1],
        showCancelButton: true, confirmButtonText: textos[2], cancelButtonText: 'Voltar', confirmButtonColor: '#eb0404'
    });
    if (!r.isConfirmed) return;

    const id = atual.agendamento.id_agendamento;
    try {
        await enviarStatus(id, status, false, false);
        avisoSucesso(textos[3]);
        alterado = false;
        atual = null;
        await abrirAtendimento(id);
        carregarAtendimentos();
    } catch (e) {
        if (e.message !== 'cancelado') mostrarErro(e);
    }
}

painelEl.addEventListener('change', (e) => {
    if (e.target.type !== 'checkbox') return;
    const li = e.target.closest('.tarefa');
    const tarefa = atual.itens[Number(li.dataset.i)];
    tarefa.concluido = e.target.checked;
    li.classList.toggle('feita', tarefa.concluido);
    marcarAlterado();
});

painelEl.addEventListener('input', (e) => {
    if (!e.target.classList.contains('texto-tarefa')) return;
    atual.itens[Number(e.target.closest('.tarefa').dataset.i)].descricao = e.target.value;
    marcarAlterado();
});

painelEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.id === 'nova-tarefa') {
        e.preventDefault();
        adicionarTarefa();
    }
});

painelEl.addEventListener('click', async (e) => {
    const alvo = e.target.closest('button');
    if (!alvo || !atual) return;

    if (alvo.classList.contains('btn-remover-tarefa')) {
        atual.itens.splice(Number(alvo.closest('.tarefa').dataset.i), 1);
        alterado = true;
        renderizarTarefas();
    } else if (alvo.id === 'btn-adicionar') {
        adicionarTarefa();
    } else if (alvo.id === 'btn-salvar') {
        if (await salvar()) avisoSucesso('Checklist salvo.');
    } else if (alvo.id === 'btn-descartar') {
        if (await confirmarDescarte()) {
            alterado = false;
            const id = atual.agendamento.id_agendamento;
            atual = null;
            abrirAtendimento(id);
        }
    } else if (alvo.id === 'btn-gerar') {
        alvo.disabled = true;
        try {
            const dados = await api('checklist_gerar', null, { id_agendamento: atual.agendamento.id_agendamento });
            atual.itens = dados.itens;
            renderizarTarefas();
            carregarAtendimentos();
        } catch (err) {
            alvo.disabled = false;
            mostrarErro(err);
        }
    } else if (alvo.dataset.status) {
        mudarStatus(alvo.dataset.status);
    }
});

window.addEventListener('beforeunload', (e) => {
    if (alterado) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// ---------------- inicio ----------------

carregarAtendimentos();
const idInicial = Number(new URLSearchParams(window.location.search).get('id'));
if (idInicial) abrirAtendimento(idInicial);

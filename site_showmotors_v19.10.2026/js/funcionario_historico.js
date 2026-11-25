// historico_funcionario.php: consulta de atendimentos anteriores (somente leitura)

const filtros = { busca: '', status: '', inicio: '', fim: '', pagina: 1 };

const listaEl = document.getElementById('lista-historico');
const resumoEl = document.getElementById('resumo-lista');
const paginacaoEl = document.getElementById('paginacao');
const formFiltros = document.getElementById('form-filtros');
const campoBusca = document.getElementById('filtro-busca');
const campoInicio = document.getElementById('filtro-inicio');
const campoFim = document.getElementById('filtro-fim');

async function carregarLista() {
    resumoEl.textContent = 'Carregando...';
    try {
        const dados = await api('historico', filtros);
        renderizarLista(dados);
    } catch (e) {
        resumoEl.textContent = '';
        listaEl.innerHTML = '';
        mostrarErro(e);
    }
}

function renderizarLista(dados) {
    paginacaoEl.innerHTML = htmlPaginacao(dados.pagina, dados.paginas);

    if (dados.total === 0) {
        resumoEl.textContent = '';
        listaEl.innerHTML =
            '<div class="aviso-vazio"><i class="fa-solid fa-clipboard-list"></i>' +
            '<p>Nenhum atendimento anterior encontrado com esses filtros.</p></div>';
        return;
    }

    resumoEl.textContent = dados.total + (dados.total === 1 ? ' atendimento encontrado' : ' atendimentos encontrados');
    listaEl.innerHTML = dados.lista.map(htmlCardHistorico).join('');
}

function htmlCardHistorico(a) {
    const nome = a.nome_cliente || a.nome_usuario || 'Cliente removido';
    const contato = [a.celular, a.telefone, a.email].filter(Boolean).map(escaparHtml).join(' &middot; ');
    const naoIniciado = a.status === 'AGENDADO'
        ? '<p class="obs-historico"><i class="fa-solid fa-triangle-exclamation"></i> Horário passou sem o atendimento ser iniciado.</p>'
        : '';

    return '<div class="card-historico" data-id="' + a.id_agendamento + '">' +
        '<div class="topo-historico"><div>' +
        '<p class="cliente-label"><i class="fa-solid fa-user"></i> ' + escaparHtml(nome) + ' &middot; #' + a.id_agendamento + '</p>' +
        '<h3><i class="fa-solid fa-calendar-day"></i> ' + formatarDataHora(a.data_hora_servico) + '</h3>' +
        '<p><i class="fa-solid fa-car"></i> ' + htmlVeiculo(a) + '</p>' +
        (contato ? '<p class="contato-cliente"><i class="fa-solid fa-phone"></i> ' + contato + '</p>' : '') +
        '</div>' + htmlBadgeStatus(a.status) + '</div>' +
        htmlServicos(a) +
        (a.descricao ? '<p class="obs-historico"><i class="fa-solid fa-comment"></i> ' + escaparHtml(a.descricao) + '</p>' : '') +
        (a.data_hora_entrega ? '<p class="obs-historico"><i class="fa-solid fa-flag-checkered"></i> Entrega prevista: ' + formatarDataHora(a.data_hora_entrega) + '</p>' : '') +
        naoIniciado +
        (a.checklist_total ? htmlProgresso(a.checklist_feitos, a.checklist_total) : '') +
        '<div class="detalhe-checklist"></div>' +
        '<div class="rodape-historico">' +
        '<span class="total">Total: <strong>' + formatarMoeda(a.total) + '</strong></span>' +
        '<div class="acoes-atendimento">' +
        (a.checklist_total ? '<button type="button" class="btn-acao" data-acao="ver-checklist"><i class="fa-solid fa-list-check"></i> Ver tarefas</button>' : '') +
        '<a class="btn-acao" href="checklist_funcionario.php?id=' + a.id_agendamento + '"><i class="fa-solid fa-up-right-from-square"></i> Abrir</a>' +
        '</div></div></div>';
}

// mostra/esconde as tarefas do checklist dentro do card
listaEl.addEventListener('click', async (e) => {
    const botao = e.target.closest('[data-acao="ver-checklist"]');
    if (!botao) return;

    const card = botao.closest('.card-historico');
    const caixa = card.querySelector('.detalhe-checklist');
    if (caixa.innerHTML) {
        caixa.innerHTML = '';
        return;
    }

    botao.disabled = true;
    try {
        const dados = await api('checklist', { id: card.dataset.id });
        caixa.innerHTML = '<ul class="tarefas-checklist">' + dados.itens.map((t) =>
            '<li class="tarefa' + (t.concluido ? ' feita' : '') + '">' +
            '<i class="fa-' + (t.concluido ? 'solid fa-square-check' : 'regular fa-square') + '" style="color:#eb0404"></i>' +
            '<span class="texto-tarefa">' + escaparHtml(t.descricao) + '</span>' +
            (t.concluido && t.concluido_por ? '<span class="autor-tarefa">' + escaparHtml(t.concluido_por) + ' &middot; ' + formatarDataHora(t.data_conclusao) + '</span>' : '') +
            '</li>'
        ).join('') + '</ul>';
    } catch (err) {
        mostrarErro(err);
    } finally {
        botao.disabled = false;
    }
});

function aplicarFiltros() {
    filtros.busca = campoBusca.value.trim();
    filtros.inicio = campoInicio.value;
    filtros.fim = campoFim.value;
    filtros.pagina = 1;
    carregarLista();
}

campoBusca.addEventListener('input', debounce(aplicarFiltros, 350));
campoInicio.addEventListener('change', aplicarFiltros);
campoFim.addEventListener('change', aplicarFiltros);
formFiltros.addEventListener('submit', (e) => {
    e.preventDefault();
    aplicarFiltros();
});
formFiltros.addEventListener('reset', () => setTimeout(aplicarFiltros, 0));

document.getElementById('filtros-status').addEventListener('click', (e) => {
    const botao = e.target.closest('.filtro-status');
    if (!botao) return;
    document.querySelectorAll('#filtros-status .filtro-status').forEach((b) => b.classList.toggle('ativo', b === botao));
    filtros.status = botao.dataset.filtro;
    filtros.pagina = 1;
    carregarLista();
});

paginacaoEl.addEventListener('click', (e) => {
    const botao = e.target.closest('[data-pagina]');
    if (!botao || botao.disabled) return;
    filtros.pagina = Number(botao.dataset.pagina);
    carregarLista();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

carregarLista();

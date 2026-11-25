// agendamentos_funcionario.php: lista, filtros, cadastro, edicao, cancelamento e status

const filtros = { busca: '', status: 'ATIVOS', inicio: '', fim: '', pagina: 1 };

const listaEl = document.getElementById('lista-agendamentos');
const resumoEl = document.getElementById('resumo-lista');
const paginacaoEl = document.getElementById('paginacao');
const formFiltros = document.getElementById('form-filtros');
const campoBusca = document.getElementById('filtro-busca');
const campoInicio = document.getElementById('filtro-inicio');
const campoFim = document.getElementById('filtro-fim');

// ---------------- lista ----------------

async function carregarLista() {
    resumoEl.textContent = 'Carregando...';
    try {
        const dados = await api('agendamentos', filtros);
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
            '<div class="aviso-vazio"><i class="fa-solid fa-calendar-xmark"></i>' +
            '<p>Nenhum agendamento encontrado com esses filtros.</p></div>';
        return;
    }

    resumoEl.textContent = dados.total + (dados.total === 1 ? ' agendamento encontrado' : ' agendamentos encontrados');
    listaEl.innerHTML = dados.lista.map((a) => htmlCardAgendamento(a, dados.transicoes[a.status] || [])).join('');
}

function htmlCardAgendamento(a, proximos) {
    const nome = a.nome_cliente || a.nome_usuario || 'Cliente removido';
    const contato = [a.celular, a.telefone, a.email].filter(Boolean).map(escaparHtml).join(' &middot; ');
    const ativo = a.status === 'AGENDADO' || a.status === 'EM_ANDAMENTO';

    let acoes = '';
    if (ativo) {
        acoes += '<button type="button" class="btn-acao" data-acao="editar"><i class="fa-solid fa-pen"></i> Editar</button>';
    }
    if (proximos.includes('EM_ANDAMENTO')) {
        acoes += '<button type="button" class="btn-acao iniciar" data-acao="status" data-status="EM_ANDAMENTO"><i class="fa-solid fa-play"></i> Iniciar</button>';
    }
    if (proximos.includes('CONCLUIDO')) {
        acoes += '<button type="button" class="btn-acao concluir" data-acao="status" data-status="CONCLUIDO"><i class="fa-solid fa-flag-checkered"></i> Concluir</button>';
    }
    acoes += '<a class="btn-acao" href="checklist_funcionario.php?id=' + a.id_agendamento + '"><i class="fa-solid fa-list-check"></i> Checklist</a>';
    if (proximos.includes('CANCELADO')) {
        acoes += '<button type="button" class="btn-cancelar" data-acao="status" data-status="CANCELADO"><i class="fa-solid fa-ban"></i> Cancelar</button>';
    }

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
        (a.status !== 'CANCELADO' ? htmlProgresso(a.checklist_feitos, a.checklist_total) : '') +
        '<div class="rodape-historico">' +
        '<span class="total">Total: <strong>' + formatarMoeda(a.total) + '</strong></span>' +
        '<div class="acoes-atendimento">' + acoes + '</div>' +
        '</div></div>';
}

// ---------------- filtros ----------------

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

document.getElementById('btn-hoje').addEventListener('click', () => {
    campoInicio.value = campoFim.value = dataHojeIso();
    aplicarFiltros();
});

document.getElementById('btn-semana').addEventListener('click', () => {
    campoInicio.value = dataHojeIso();
    campoFim.value = somarDias(dataHojeIso(), 7);
    aplicarFiltros();
});

function marcarFiltroStatus(valor) {
    document.querySelectorAll('#filtros-status .filtro-status').forEach((b) => {
        b.classList.toggle('ativo', b.dataset.filtro === valor);
    });
}

document.getElementById('filtros-status').addEventListener('click', (e) => {
    const botao = e.target.closest('.filtro-status');
    if (!botao) return;
    marcarFiltroStatus(botao.dataset.filtro);
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

// ---------------- status / cancelamento ----------------

const CONFIRMACOES = {
    EM_ANDAMENTO: {
        icon: 'question', title: 'Iniciar atendimento?',
        text: 'O status muda para "Em andamento" e o checklist é montado com os serviços.',
        confirmButtonText: 'Iniciar', sucesso: 'Atendimento iniciado.'
    },
    CONCLUIDO: {
        icon: 'question', title: 'Concluir atendimento?',
        text: 'Depois de concluído, o atendimento vai para o histórico e não pode mais ser editado.',
        confirmButtonText: 'Concluir', sucesso: 'Atendimento concluído.'
    },
    CANCELADO: {
        icon: 'warning', title: 'Cancelar agendamento?',
        text: 'O horário ficará livre para outros clientes.',
        confirmButtonText: 'Cancelar agendamento', cancelButtonText: 'Voltar', sucesso: 'Agendamento cancelado.'
    }
};

async function mudarStatus(id, status) {
    const conf = CONFIRMACOES[status];
    const resposta = await Swal.fire({
        icon: conf.icon, title: conf.title, text: conf.text,
        showCancelButton: true,
        confirmButtonText: conf.confirmButtonText,
        cancelButtonText: conf.cancelButtonText || 'Voltar',
        confirmButtonColor: '#eb0404'
    });
    if (!resposta.isConfirmed) return;

    try {
        const dados = await enviarStatus(id, status, false);
        avisoSucesso(conf.sucesso + (dados.tarefas_criadas ? ' Checklist criado com ' + dados.tarefas_criadas + ' tarefa(s).' : ''));
        carregarLista();
    } catch (e) {
        if (e.message !== 'cancelado') mostrarErro(e);
    }
}

listaEl.addEventListener('click', (e) => {
    const botao = e.target.closest('[data-acao]');
    if (!botao) return;
    const id = Number(botao.closest('.card-historico').dataset.id);

    if (botao.dataset.acao === 'editar') abrirEdicao(id);
    if (botao.dataset.acao === 'status') mudarStatus(id, botao.dataset.status);
});

// ---------------- modal de cadastro / edicao ----------------

const modal = document.getElementById('modal-agendamento');
const formAg = document.getElementById('form-agendamento');
const tituloModal = document.getElementById('titulo-modal-agendamento');
const avisoAndamento = document.getElementById('aviso-em-andamento');
const campoCliente = document.getElementById('ag-cliente');
const campoCarro = document.getElementById('ag-carro');
const caixaServicos = document.getElementById('ag-servicos');
const campoData = document.getElementById('ag-data');
const campoEntrega = document.getElementById('ag-entrega');
const gradeHorarios = document.getElementById('ag-horarios');
const avisoHorarios = document.getElementById('aviso-horarios');
const campoDescricao = document.getElementById('ag-descricao');
const btnSalvar = document.getElementById('btn-salvar-agendamento');

// agendamento em edicao (null = cadastro novo)
let emEdicao = null;
let cacheClientes = null;
let cacheServicos = null;

async function carregarCadastros() {
    if (!cacheClientes) cacheClientes = (await api('clientes')).clientes;
    if (!cacheServicos) cacheServicos = (await api('servicos')).servicos;
}

function preencherClientes(selecionado) {
    campoCliente.innerHTML = '<option value="">Selecione o cliente</option>' + cacheClientes.map((c) => {
        const semCarro = Number(c.qtd_carros) === 0;
        const nome = c.nome_cliente + ' (' + c.nome_usuario + ')' + (semCarro ? ' - sem veículo' : '');
        return '<option value="' + c.id_usuario + '"' + (semCarro ? ' disabled' : '') + '>' + escaparHtml(nome) + '</option>';
    }).join('');
    campoCliente.value = selecionado || '';
}

async function carregarCarros(idCliente, selecionado) {
    campoCarro.disabled = true;
    if (!idCliente) {
        campoCarro.innerHTML = '<option value="">Selecione o cliente</option>';
        return;
    }
    campoCarro.innerHTML = '<option value="">Carregando...</option>';
    const carros = (await api('carros', { id_usuario: idCliente })).carros;
    campoCarro.innerHTML = carros.length === 0
        ? '<option value="">Cliente sem veículo cadastrado</option>'
        : '<option value="">Selecione o veículo</option>' + carros.map((c) =>
            '<option value="' + c.id_carro + '">' + escaparHtml(c.marca + ' ' + c.modelo + ' - ' + c.placa) + '</option>'
        ).join('');
    campoCarro.value = selecionado || '';
    campoCarro.disabled = !!(emEdicao && emEdicao.status === 'EM_ANDAMENTO');
}

// servicos do catalogo + os que ja estao no agendamento (mesmo se sairam do catalogo)
function preencherServicos(marcados) {
    marcados = marcados || [];
    const lista = cacheServicos.map((s) => ({ id: Number(s.id_servico), nome: s.nome, desc: s.descricao || '', preco: Number(s.preco) }));
    marcados.forEach((m) => {
        if (!lista.some((s) => s.id === m.id_servico)) {
            lista.push({ id: m.id_servico, nome: m.nome, desc: 'Fora do catálogo atual', preco: m.preco });
        }
    });
    // servico que o agendamento ja tinha mantem o preco gravado nele
    const precoGravado = Object.fromEntries(marcados.map((m) => [m.id_servico, m.preco]));

    caixaServicos.innerHTML = lista.map((s) => {
        const preco = precoGravado[s.id] !== undefined ? precoGravado[s.id] : s.preco;
        return '<label class="opcao-servico">' +
            '<input type="checkbox" name="servicos" value="' + s.id + '" data-preco="' + preco + '"' + (precoGravado[s.id] !== undefined ? ' checked' : '') + '>' +
            '<span><span class="nome">' + escaparHtml(s.nome) + '</span><span class="desc">' + escaparHtml(s.desc) + '</span></span>' +
            '<span class="preco">' + formatarMoeda(preco) + '</span></label>';
    }).join('');
    atualizarTotal();
}

function atualizarTotal() {
    let total = 0;
    caixaServicos.querySelectorAll('input:checked').forEach((c) => { total += Number(c.dataset.preco); });
    document.getElementById('ag-total').textContent = formatarMoeda(total);
}
caixaServicos.addEventListener('change', atualizarTotal);

// horarios livres do dia (na edicao, o horario original continua disponivel)
async function carregarHorarios(data, horaMarcada) {
    gradeHorarios.innerHTML = '';
    if (!data) {
        avisoHorarios.textContent = 'Escolha a data para ver os horários livres.';
        return;
    }

    const travado = emEdicao && emEdicao.status === 'EM_ANDAMENTO';
    const original = emEdicao ? emEdicao.data_hora_servico.split(' ') : null;
    const horaOriginal = original && original[0] === data ? original[1].slice(0, 5) : null;
    let horarios = [];

    if (travado) {
        horarios = [horaOriginal];
        avisoHorarios.textContent = '';
    } else {
        avisoHorarios.textContent = 'Carregando horários...';
        try {
            const dados = await api('horarios', { data: data, ignorar: emEdicao ? emEdicao.id_agendamento : '' });
            horarios = dados.horarios;
            avisoHorarios.textContent = dados.erro || '';
        } catch (e) {
            avisoHorarios.textContent = e.message;
            return;
        }
        if (horaOriginal && !horarios.includes(horaOriginal)) {
            horarios.push(horaOriginal);
            horarios.sort();
        }
        if (horarios.length === 0 && !avisoHorarios.textContent) {
            avisoHorarios.textContent = 'Não há horários livres nesse dia. Escolha outra data.';
        }
    }

    horarios.forEach((h) => {
        const label = document.createElement('label');
        label.className = 'horario-opcao';
        label.innerHTML = '<input type="radio" name="hora" value="' + h + '" required' + (travado ? ' disabled' : '') + '><span>' + h + '</span>';
        if (h === horaMarcada) label.querySelector('input').checked = true;
        gradeHorarios.appendChild(label);
    });
}

campoCliente.addEventListener('change', () => {
    carregarCarros(campoCliente.value, '').catch(mostrarErro);
});
campoData.addEventListener('change', () => carregarHorarios(campoData.value, ''));

function abrirModal() {
    modal.classList.add('aberto');
}

function fecharModal() {
    modal.classList.remove('aberto');
    emEdicao = null;
}

document.getElementById('btn-fechar-modal').addEventListener('click', fecharModal);
modal.addEventListener('click', (e) => {
    if (e.target === modal) fecharModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('aberto')) fecharModal();
});

async function abrirCadastro() {
    try {
        await carregarCadastros();
    } catch (e) {
        mostrarErro(e);
        return;
    }
    emEdicao = null;
    formAg.reset();
    tituloModal.innerHTML = '<i class="fa-solid fa-calendar-plus"></i> Novo Agendamento';
    avisoAndamento.hidden = true;
    campoCliente.disabled = campoData.disabled = false;
    preencherClientes('');
    await carregarCarros('', '');
    preencherServicos([]);
    await carregarHorarios('', '');
    abrirModal();
}

async function abrirEdicao(id) {
    let a;
    try {
        await carregarCadastros();
        a = (await api('agendamento', { id: id })).agendamento;
    } catch (e) {
        mostrarErro(e);
        return;
    }
    emEdicao = a;
    formAg.reset();
    const travado = a.status === 'EM_ANDAMENTO';
    const [data, hora] = a.data_hora_servico.split(' ');

    tituloModal.innerHTML = '<i class="fa-solid fa-pen"></i> Editar Agendamento #' + a.id_agendamento;
    avisoAndamento.hidden = !travado;
    campoCliente.disabled = campoData.disabled = travado;

    preencherClientes(String(a.id_usuario));
    // cliente que deixou de ser CLIENTE ou foi removido: mantem a opcao para nao perder o vinculo
    if (campoCliente.value !== String(a.id_usuario)) {
        campoCliente.insertAdjacentHTML('beforeend', '<option value="' + a.id_usuario + '">' + escaparHtml(a.nome_cliente || 'Cliente #' + a.id_usuario) + '</option>');
        campoCliente.value = String(a.id_usuario);
    }
    preencherServicos(a.servicos);
    campoData.value = data;
    campoEntrega.value = a.data_hora_entrega ? a.data_hora_entrega.slice(0, 16).replace(' ', 'T') : '';
    campoDescricao.value = a.descricao || '';
    abrirModal();

    try {
        await carregarCarros(a.id_usuario, String(a.id_carro));
        await carregarHorarios(data, hora.slice(0, 5));
    } catch (e) {
        mostrarErro(e);
    }
}

document.getElementById('btn-novo-agendamento').addEventListener('click', abrirCadastro);

formAg.addEventListener('submit', async (e) => {
    e.preventDefault();

    const servicos = Array.from(caixaServicos.querySelectorAll('input:checked')).map((c) => Number(c.value));
    const hora = gradeHorarios.querySelector('input[name="hora"]:checked');

    if (servicos.length === 0) {
        Swal.fire({ icon: 'error', text: 'Escolha pelo menos um serviço.' });
        return;
    }
    if (!hora) {
        Swal.fire({ icon: 'error', text: 'Escolha um horário.' });
        return;
    }

    const corpo = {
        id_agendamento: emEdicao ? emEdicao.id_agendamento : 0,
        id_usuario: Number(campoCliente.value),
        id_carro: Number(campoCarro.value),
        servicos: servicos,
        data: campoData.value,
        hora: hora.value,
        entrega: campoEntrega.value,
        descricao: campoDescricao.value
    };

    btnSalvar.disabled = true;
    try {
        await api('salvar_agendamento', null, corpo);
        const editou = !!emEdicao;
        fecharModal();
        avisoSucesso(editou ? 'Agendamento atualizado.' : 'Agendamento cadastrado.');
        carregarLista();
    } catch (err) {
        mostrarErro(err);
        // horario pode ter sido ocupado nesse meio tempo
        if (campoData.value) carregarHorarios(campoData.value, hora.value);
    } finally {
        btnSalvar.disabled = false;
    }
});

// ---------------- inicio ----------------

// links do painel: ?status=EM_ANDAMENTO, ?periodo=hoje, ?ate=hoje, ?novo=1, ?editar=ID
(function iniciar() {
    const url = new URLSearchParams(window.location.search);
    const status = url.get('status');

    if (status !== null && document.querySelector('#filtros-status [data-filtro="' + CSS.escape(status) + '"]')) {
        filtros.status = status;
        marcarFiltroStatus(status);
    }
    if (url.get('periodo') === 'hoje') {
        campoInicio.value = campoFim.value = dataHojeIso();
    }
    if (url.get('ate') === 'hoje') {
        campoFim.value = dataHojeIso();
    }
    filtros.inicio = campoInicio.value;
    filtros.fim = campoFim.value;

    carregarLista();

    if (url.get('novo')) abrirCadastro();
    if (url.get('editar')) abrirEdicao(Number(url.get('editar')));
})();

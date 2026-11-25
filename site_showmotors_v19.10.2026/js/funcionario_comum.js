// funcoes compartilhadas da area do funcionario (usadas pelos outros js/funcionario_*.js)

const ROTULOS_STATUS = {
    AGENDADO: 'Agendado',
    EM_ANDAMENTO: 'Em andamento',
    CONCLUIDO: 'Concluído',
    CANCELADO: 'Cancelado'
};

// chama api_funcionario.php
// GET:  api('agendamentos', { busca: 'x' })
// POST: api('status', null, { id_agendamento: 1, status: 'CONCLUIDO' })
async function api(acao, params, corpo) {
    const url = new URL('api_funcionario.php', window.location.href);
    url.searchParams.set('acao', acao);
    Object.entries(params || {}).forEach(([chave, valor]) => {
        if (valor !== undefined && valor !== null && valor !== '') url.searchParams.set(chave, valor);
    });

    const opcoes = { headers: { Accept: 'application/json' }, credentials: 'same-origin' };
    if (corpo) {
        opcoes.method = 'POST';
        opcoes.headers['Content-Type'] = 'application/json';
        opcoes.headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]').content;
        opcoes.body = JSON.stringify(corpo);
    }

    let resposta;
    let dados;
    try {
        resposta = await fetch(url, opcoes);
        dados = await resposta.json();
    } catch (e) {
        throw Object.assign(new Error('Não foi possível falar com o servidor. Verifique a conexão e tente novamente.'), { dados: {} });
    }

    if (resposta.status === 401) {
        window.location.href = 'login.php';
    }
    if (!resposta.ok || !dados.ok) {
        throw Object.assign(new Error(dados.erro || 'Erro inesperado.'), { dados: dados, status: resposta.status });
    }
    return dados;
}

// muda o status do atendimento (api_funcionario.php?acao=status)
// concluir com checklist pendente pede uma segunda confirmacao
// (na propria tela do checklist, irParaChecklist = false)
async function enviarStatus(id, status, forcar, irParaChecklist = true) {
    try {
        return await api('status', null, { id_agendamento: id, status: status, forcar: forcar });
    } catch (e) {
        if (e.dados && e.dados.codigo === 'checklist_pendente' && !forcar) {
            const r = await Swal.fire({
                icon: 'warning',
                title: 'Checklist incompleto',
                text: e.message + ' Deseja concluir mesmo assim?',
                showCancelButton: true,
                confirmButtonText: 'Concluir mesmo assim',
                cancelButtonText: irParaChecklist ? 'Ver checklist' : 'Voltar',
                confirmButtonColor: '#eb0404'
            });
            if (r.isConfirmed) return enviarStatus(id, status, true, irParaChecklist);
            if (irParaChecklist && r.dismiss === Swal.DismissReason.cancel) window.location.href = 'checklist_funcionario.php?id=' + id;
            throw new Error('cancelado');
        }
        throw e;
    }
}

function mostrarErro(e) {
    Swal.fire({ icon: 'error', text: e.message });
}

function avisoSucesso(texto) {
    Swal.fire({ icon: 'success', text: texto, timer: 1600, showConfirmButton: false });
}

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto == null ? '' : String(texto);
    return div.innerHTML;
}

// "2026-10-14 14:00:00" -> "14/10/2026 às 14:00"
function formatarDataHora(dataHora) {
    if (!dataHora) return '';
    const [data, hora] = dataHora.split(' ');
    const [a, m, d] = data.split('-');
    return d + '/' + m + '/' + a + ' às ' + (hora || '').slice(0, 5);
}

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function dataHojeIso() {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 10);
}

function somarDias(dataIso, dias) {
    const d = new Date(dataIso + 'T12:00:00');
    d.setDate(d.getDate() + dias);
    return d.toISOString().slice(0, 10);
}

function htmlBadgeStatus(status) {
    return '<span class="badge-status ' + status.toLowerCase() + '">' + (ROTULOS_STATUS[status] || escaparHtml(status)) + '</span>';
}

function htmlProgresso(feitos, total) {
    if (!total) {
        return '<div class="progresso"><i class="fa-solid fa-list-check"></i> Checklist sem tarefas</div>';
    }
    const pct = Math.round((feitos / total) * 100);
    return '<div class="progresso' + (feitos === total ? ' completo' : '') + '">' +
        '<i class="fa-solid fa-list-check"></i>' +
        '<div class="progresso-barra"><span style="width:' + pct + '%"></span></div>' +
        '<span>' + feitos + '/' + total + ' tarefas (' + pct + '%)</span>' +
        '</div>';
}

function htmlVeiculo(a) {
    if (a.marca === null) return 'Veículo removido';
    return escaparHtml(a.marca + ' ' + a.modelo) + ' (' + escaparHtml(a.placa) + ')';
}

function htmlServicos(a) {
    return '<ul class="itens-historico">' + a.servicos.map((s) =>
        '<li><span>' + escaparHtml(s.nome) + '</span><span>' + formatarMoeda(s.preco) + '</span></li>'
    ).join('') + '</ul>';
}

function htmlPaginacao(pagina, paginas) {
    if (paginas <= 1) return '';
    return '<div class="paginacao">' +
        '<button type="button" class="btn-acao" data-pagina="' + (pagina - 1) + '"' + (pagina <= 1 ? ' disabled' : '') + '>' +
        '<i class="fa-solid fa-chevron-left"></i> Anterior</button>' +
        '<span>Página ' + pagina + ' de ' + paginas + '</span>' +
        '<button type="button" class="btn-acao" data-pagina="' + (pagina + 1) + '"' + (pagina >= paginas ? ' disabled' : '') + '>' +
        'Próxima <i class="fa-solid fa-chevron-right"></i></button>' +
        '</div>';
}

function debounce(fn, espera) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), espera);
    };
}

<?php
// api JSON da area do funcionario (chamada via fetch pelos arquivos js/funcionario_*.js)
// GET  ?acao=...  -> consultas
// POST ?acao=...  -> alteracoes (corpo JSON + cabecalho X-CSRF-Token)
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("conexao.php");
include("regras_agendamento.php");
include("funcoes_funcionario.php");

date_default_timezone_set('America/Sao_Paulo');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


function responder($dados, $codigo = 200)
{
    ob_clean();
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

function erro($mensagem, $codigo = 400, $extra = [])
{
    responder(['ok' => false, 'erro' => $mensagem] + $extra, $codigo);
}


// mesma regra do verifica_acesso.php, mas respondendo JSON em vez de redirecionar
if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    erro('Sessão expirada. Faça login novamente.', 401);
}
if (!in_array($_SESSION['nivel'] ?? 'CLIENTE', ['ADM', 'FUNCIONARIO'], true)) {
    erro('Acesso permitido apenas para funcionários.', 403);
}

$idLogado = (int) $_SESSION['id_usuario'];
$acao     = $_GET['acao'] ?? '';
$metodo   = $_SERVER['REQUEST_METHOD'];
$corpo    = [];

if ($metodo === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        erro('Sessão inválida. Recarregue a página e tente novamente.', 419);
    }
    $corpo = json_decode(file_get_contents('php://input'), true);
    if (!is_array($corpo)) {
        erro('Dados inválidos.');
    }
}

// acoes que precisam da tabela do checklist
$precisaChecklist = ['checklist', 'checklist_salvar', 'checklist_gerar'];
if (in_array($acao, $precisaChecklist, true) && !checklistDisponivel($conn)) {
    erro('Checklist indisponível: execute o script conexao/checklist_funcionario.sql no banco.', 500);
}


try {
    switch ($metodo . ' ' . $acao) {

        // ---------- consultas ----------

        case 'GET agendamentos':
        case 'GET historico':
            $porPagina = 15;
            $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
            $status    = $_GET['status'] ?? '';
            if ($status === 'ATIVOS') {
                $status = $STATUS_ATIVOS;
            }
            $filtros = [
                'busca'     => $_GET['busca'] ?? '',
                'status'    => $status,
                'inicio'    => $_GET['inicio'] ?? '',
                'fim'       => $_GET['fim'] ?? '',
                'historico' => $acao === 'historico',
                'ordem'     => $acao === 'historico' ? 'desc' : ($_GET['ordem'] ?? 'asc'),
            ];
            $res = buscarAgendamentos($conn, $filtros, $porPagina, ($pagina - 1) * $porPagina);
            responder([
                'ok'         => true,
                'lista'      => $res['lista'],
                'total'      => $res['total'],
                'pagina'     => $pagina,
                'paginas'    => max(1, (int) ceil($res['total'] / $porPagina)),
                'transicoes' => $TRANSICOES_STATUS,
            ]);

        case 'GET agendamento':
            $a = buscarAgendamentoPorId($conn, $_GET['id'] ?? 0);
            if (!$a) {
                erro('Agendamento não encontrado.', 404);
            }
            responder(['ok' => true, 'agendamento' => $a]);

        case 'GET clientes':
            $res = $conn->query(
                "SELECT u.id_usuario, u.nome_cliente, u.nome_usuario, u.telefone, u.celular,
                        COUNT(c.id_carro) AS qtd_carros
                 FROM usuario u
                 LEFT JOIN carro c ON c.id_usuario = u.id_usuario
                 WHERE u.nivel_acesso = 'CLIENTE'
                 GROUP BY u.id_usuario
                 ORDER BY u.nome_cliente"
            );
            responder(['ok' => true, 'clientes' => $res->fetch_all(MYSQLI_ASSOC)]);

        case 'GET carros':
            $idCliente = (int) ($_GET['id_usuario'] ?? 0);
            $stmt = $conn->prepare("SELECT id_carro, marca, modelo, placa FROM carro WHERE id_usuario = ? ORDER BY marca, modelo");
            $stmt->bind_param("i", $idCliente);
            $stmt->execute();
            $carros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            responder(['ok' => true, 'carros' => $carros]);

        case 'GET servicos':
            $res = $conn->query("SELECT id_servico, nome, preco, descricao FROM servicos ORDER BY nome");
            responder(['ok' => true, 'servicos' => $res->fetch_all(MYSQLI_ASSOC)]);

        case 'GET horarios':
            $data      = $_GET['data'] ?? '';
            $ignorarId = (int) ($_GET['ignorar'] ?? 0);
            if (!diaPermitido($data, $DIAS_UTEIS, $DIAS_ANTECEDENCIA_MAX, true)) {
                responder(['ok' => true, 'horarios' => [], 'erro' => "Escolha um dia útil (segunda a sexta), de hoje até $DIAS_ANTECEDENCIA_MAX dias."]);
            }
            responder(['ok' => true, 'horarios' => horariosLivres($conn, $data, $ignorarId)]);

        case 'GET checklist':
            $a = buscarAgendamentoPorId($conn, $_GET['id'] ?? 0);
            if (!$a) {
                erro('Agendamento não encontrado.', 404);
            }
            responder([
                'ok'          => true,
                'agendamento' => $a,
                'itens'       => listarChecklist($conn, $a['id_agendamento']),
                'editavel'    => in_array($a['status'], $STATUS_ATIVOS, true),
            ]);

        // ---------- alteracoes ----------

        case 'POST salvar_agendamento':
            $id = salvarAgendamento($conn, $corpo);
            responder(['ok' => true, 'id_agendamento' => $id, 'agendamento' => buscarAgendamentoPorId($conn, $id)]);

        case 'POST status':
            $res = alterarStatus($conn, $corpo);
            responder(['ok' => true] + $res + ['agendamento' => buscarAgendamentoPorId($conn, $corpo['id_agendamento'])]);

        case 'POST checklist_salvar':
            salvarChecklist($conn, $corpo, $idLogado);
            $id = (int) $corpo['id_agendamento'];
            responder(['ok' => true, 'itens' => listarChecklist($conn, $id), 'agendamento' => buscarAgendamentoPorId($conn, $id)]);

        case 'POST checklist_gerar':
            $a = agendamentoAtivo($conn, $corpo['id_agendamento'] ?? 0);
            $criadas = criarChecklistDosServicos($conn, $a['id_agendamento']);
            responder(['ok' => true, 'criadas' => $criadas, 'itens' => listarChecklist($conn, $a['id_agendamento'])]);

        default:
            erro('Ação inválida.', 404);
    }
} catch (mysqli_sql_exception $e) {
    error_log('api_funcionario: ' . $e->getMessage());
    erro('Erro ao acessar o banco de dados. Tente novamente.', 500);
}


// ================= funcoes das acoes =================

function horariosLivres(mysqli $conn, $data, $ignorarId = 0)
{
    global $HORARIOS, $VAGAS_POR_HORARIO;

    $ocupados = vagasOcupadas($conn, $data, $ignorarId);
    $agora    = date('H:i');
    $ehHoje   = $data === date('Y-m-d');
    $livres   = [];

    foreach ($HORARIOS as $h) {
        if ($ehHoje && $h <= $agora) {
            continue; // horario de hoje que ja passou
        }
        if (($ocupados[$h] ?? 0) < $VAGAS_POR_HORARIO) {
            $livres[] = $h;
        }
    }
    return $livres;
}

// busca o agendamento e garante que ainda esta em aberto (agendado ou em andamento)
function agendamentoAtivo(mysqli $conn, $id)
{
    global $STATUS_ATIVOS;

    $a = buscarAgendamentoPorId($conn, $id);
    if (!$a) {
        erro('Agendamento não encontrado.', 404);
    }
    if (!in_array($a['status'], $STATUS_ATIVOS, true)) {
        erro('Atendimento concluído ou cancelado não pode ser alterado.', 409);
    }
    return $a;
}


// cria (id_agendamento = 0) ou edita um agendamento
// em andamento: cliente, veiculo, data e horario ficam travados; servicos, observacao e entrega podem mudar
function salvarAgendamento(mysqli $conn, array $d)
{
    global $HORARIOS, $DIAS_UTEIS, $DIAS_ANTECEDENCIA_MAX, $VAGAS_POR_HORARIO;

    $id        = (int) ($d['id_agendamento'] ?? 0);
    $idCliente = (int) ($d['id_usuario'] ?? 0);
    $idCarro   = (int) ($d['id_carro'] ?? 0);
    $data      = (string) ($d['data'] ?? '');
    $hora      = (string) ($d['hora'] ?? '');
    $entrega   = trim((string) ($d['entrega'] ?? ''));
    $obs       = mb_substr(trim((string) ($d['descricao'] ?? '')), 0, 256);
    $idsServ   = array_values(array_unique(array_filter(array_map('intval', (array) ($d['servicos'] ?? [])), fn($i) => $i > 0)));

    $atual = $id > 0 ? agendamentoAtivo($conn, $id) : null;

    // cliente + veiculo do cliente
    $stmt = $conn->prepare(
        "SELECT c.id_carro FROM carro c
         JOIN usuario u ON u.id_usuario = c.id_usuario
         WHERE c.id_carro = ? AND c.id_usuario = ? AND u.nivel_acesso = 'CLIENTE'"
    );
    $stmt->bind_param("ii", $idCarro, $idCliente);
    $stmt->execute();
    $carroOk = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$carroOk) {
        erro('Escolha o cliente e um veículo cadastrado para ele.');
    }

    // servicos (preco atual do catalogo)
    if (!$idsServ) {
        erro('Escolha pelo menos um serviço.');
    }
    $ph = implode(',', array_fill(0, count($idsServ), '?'));
    $stmt = $conn->prepare("SELECT id_servico, preco FROM servicos WHERE id_servico IN ($ph)");
    $stmt->bind_param(str_repeat('i', count($idsServ)), ...$idsServ);
    $stmt->execute();
    $precoPorId = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $s) {
        $precoPorId[(int) $s['id_servico']] = (float) $s['preco'];
    }
    $stmt->close();

    // servico que ja estava no agendamento continua valendo mesmo se saiu do catalogo
    $servicosAtuais = $atual ? array_column($atual['servicos'], 'preco', 'id_servico') : [];
    foreach ($idsServ as $i) {
        if (!isset($precoPorId[$i]) && !isset($servicosAtuais[$i])) {
            erro('Um dos serviços escolhidos não existe mais. Atualize a página.');
        }
    }

    $dataHora = $data . ' ' . $hora . ':00';
    $mudouHorario = !$atual || $atual['data_hora_servico'] !== $dataHora;

    if ($atual && $atual['status'] === 'EM_ANDAMENTO'
        && ($mudouHorario || (int) $atual['id_carro'] !== $idCarro || (int) $atual['id_usuario'] !== $idCliente)) {
        erro('Atendimento em andamento: cliente, veículo, data e horário não podem ser alterados.', 409);
    }

    if ($mudouHorario) {
        if (!diaPermitido($data, $DIAS_UTEIS, $DIAS_ANTECEDENCIA_MAX, true)) {
            erro("Escolha um dia útil (segunda a sexta), de hoje até $DIAS_ANTECEDENCIA_MAX dias.");
        }
        if (!in_array($hora, horariosLivres($conn, $data, $id), true)) {
            erro(in_array($hora, $HORARIOS, true)
                ? 'Esse horário não está mais disponível. Escolha outro.'
                : 'Escolha um horário.');
        }
    }

    // mesmo veiculo nao pode ter dois agendamentos no mesmo dia (regra do agendar.php)
    $stmt = $conn->prepare(
        "SELECT id_agendamento FROM agendamento
         WHERE id_carro = ? AND DATE(data_hora_servico) = ? AND status <> 'CANCELADO' AND id_agendamento <> ?"
    );
    $diaServico = substr($atual && !$mudouHorario ? $atual['data_hora_servico'] : $dataHora, 0, 10);
    $stmt->bind_param("isi", $idCarro, $diaServico, $id);
    $stmt->execute();
    $carroJaAgendado = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($carroJaAgendado) {
        erro('Esse veículo já tem um agendamento nesse dia.');
    }

    // entrega prevista (opcional) - vem do input datetime-local (Y-m-d\TH:i)
    $entregaSql = null;
    if ($entrega !== '') {
        $e = DateTime::createFromFormat('Y-m-d\TH:i', $entrega);
        if (!$e || $e->format('Y-m-d\TH:i') !== $entrega) {
            erro('Data de entrega inválida.');
        }
        $entregaSql = $e->format('Y-m-d H:i:s');
        if ($entregaSql < $dataHora) {
            erro('A entrega prevista não pode ser antes do horário do serviço.');
        }
    }

    if (!$atual) {
        $stmt = $conn->prepare(
            "INSERT INTO agendamento (id_usuario, id_carro, data_hora_servico, data_hora_entrega, descricao)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iisss", $idCliente, $idCarro, $dataHora, $entregaSql, $obs);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();

        try {
            $stmt = $conn->prepare("INSERT INTO agendamento_servicos (id_agendamento, id_servico, preco_unitario) VALUES (?, ?, ?)");
            foreach ($idsServ as $idServico) {
                $preco = $precoPorId[$idServico];
                $stmt->bind_param("iid", $id, $idServico, $preco);
                $stmt->execute();
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // tabelas MyISAM nao tem transacao: desfaz na mao (mesma ideia do agendar.php)
            $conn->query("DELETE FROM agendamento_servicos WHERE id_agendamento = " . (int) $id);
            $conn->query("DELETE FROM agendamento WHERE id_agendamento = " . (int) $id);
            throw $e;
        }
        return $id;
    }

    $stmt = $conn->prepare(
        "UPDATE agendamento
         SET id_usuario = ?, id_carro = ?, data_hora_servico = ?, data_hora_entrega = ?, descricao = ?
         WHERE id_agendamento = ? AND status IN ('AGENDADO', 'EM_ANDAMENTO')"
    );
    $stmt->bind_param("iisssi", $idCliente, $idCarro, $dataHora, $entregaSql, $obs, $id);
    $stmt->execute();
    $stmt->close();

    // servicos: remove os desmarcados e inclui os novos; os que ficaram mantem o preco da epoca do agendamento
    $remover = array_diff(array_keys($servicosAtuais), $idsServ);
    if ($remover) {
        $remover = array_values($remover);
        $ph = implode(',', array_fill(0, count($remover), '?'));
        $stmt = $conn->prepare("DELETE FROM agendamento_servicos WHERE id_agendamento = ? AND id_servico IN ($ph)");
        $stmt->bind_param('i' . str_repeat('i', count($remover)), $id, ...$remover);
        $stmt->execute();
        $stmt->close();
    }

    $incluir = array_diff($idsServ, array_keys($servicosAtuais));
    if ($incluir) {
        $stmt = $conn->prepare("INSERT INTO agendamento_servicos (id_agendamento, id_servico, preco_unitario) VALUES (?, ?, ?)");
        foreach ($incluir as $idServico) {
            $preco = $precoPorId[$idServico];
            $stmt->bind_param("iid", $id, $idServico, $preco);
            $stmt->execute();
        }
        $stmt->close();
    }

    return $id;
}


// muda o status respeitando $TRANSICOES_STATUS
// concluir com tarefas pendentes no checklist exige confirmacao (forcar = true)
function alterarStatus(mysqli $conn, array $d)
{
    global $TRANSICOES_STATUS;

    $id   = (int) ($d['id_agendamento'] ?? 0);
    $novo = (string) ($d['status'] ?? '');

    $a = buscarAgendamentoPorId($conn, $id);
    if (!$a) {
        erro('Agendamento não encontrado.', 404);
    }

    $permitidos = $TRANSICOES_STATUS[$a['status']] ?? [];
    if (!in_array($novo, $permitidos, true)) {
        erro('Não é possível mudar esse atendimento para o status escolhido.', 409);
    }

    $temChecklist = checklistDisponivel($conn);
    $pendentes = $a['checklist_total'] - $a['checklist_feitos'];
    if ($novo === 'CONCLUIDO' && $pendentes > 0 && empty($d['forcar'])) {
        erro("O checklist ainda tem $pendentes tarefa(s) pendente(s).", 409, ['codigo' => 'checklist_pendente', 'pendentes' => $pendentes]);
    }

    $stmt = $conn->prepare("UPDATE agendamento SET status = ? WHERE id_agendamento = ? AND status = ?");
    $stmt->bind_param("sis", $novo, $id, $a['status']);
    $stmt->execute();
    $mudou = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$mudou) {
        erro('O atendimento foi alterado por outra pessoa. Atualize a lista.', 409);
    }

    // ao iniciar o atendimento, monta o checklist com os servicos (se ainda estiver vazio)
    $criadas = 0;
    if ($novo === 'EM_ANDAMENTO' && $temChecklist) {
        $criadas = criarChecklistDosServicos($conn, $id);
    }

    return ['tarefas_criadas' => $criadas];
}


// salva o checklist inteiro de uma vez (como esta na tela):
// itens com id_item sao atualizados, sem id_item sao criados e os que sumiram sao excluidos
function salvarChecklist(mysqli $conn, array $d, $idLogado)
{
    $a  = agendamentoAtivo($conn, $d['id_agendamento'] ?? 0);
    $id = (int) $a['id_agendamento'];

    $itens = $d['itens'] ?? null;
    if (!is_array($itens) || count($itens) > 100) {
        erro('Lista de tarefas inválida.');
    }

    $existentes = [];
    foreach (listarChecklist($conn, $id) as $i) {
        $existentes[$i['id_item']] = $i;
    }

    $normalizados = [];
    foreach ($itens as $i) {
        $descricao = mb_substr(trim((string) ($i['descricao'] ?? '')), 0, 150);
        if ($descricao === '') {
            erro('Toda tarefa precisa de uma descrição.');
        }
        $idItem = (int) ($i['id_item'] ?? 0);
        if ($idItem > 0 && !isset($existentes[$idItem])) {
            erro('O checklist foi alterado por outra pessoa. Recarregue a página.', 409);
        }
        $normalizados[] = ['id_item' => $idItem, 'descricao' => $descricao, 'concluido' => !empty($i['concluido'])];
    }

    $manter = array_filter(array_column($normalizados, 'id_item'));
    $remover = array_values(array_diff(array_keys($existentes), $manter));
    if ($remover) {
        $ph = implode(',', array_fill(0, count($remover), '?'));
        $stmt = $conn->prepare("DELETE FROM checklist_item WHERE id_agendamento = ? AND id_item IN ($ph)");
        $stmt->bind_param('i' . str_repeat('i', count($remover)), $id, ...$remover);
        $stmt->execute();
        $stmt->close();
    }

    $agora = date('Y-m-d H:i:s');
    $upd = $conn->prepare(
        "UPDATE checklist_item SET descricao = ?, concluido = ?, ordem = ?, id_usuario_conclusao = ?, data_conclusao = ?
         WHERE id_item = ? AND id_agendamento = ?"
    );
    $ins = $conn->prepare(
        "INSERT INTO checklist_item (id_agendamento, descricao, concluido, ordem, id_usuario_conclusao, data_conclusao)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($normalizados as $ordem => $i) {
        $concluido = $i['concluido'] ? 1 : 0;
        $anterior  = $existentes[$i['id_item']] ?? null;

        // quem marcou e quando: so muda quando a tarefa troca de pendente para feita
        if (!$concluido) {
            $quem = null;
            $quando = null;
        } elseif ($anterior && $anterior['concluido']) {
            $quem = $anterior['id_usuario_conclusao'];
            $quando = $anterior['data_conclusao'];
        } else {
            $quem = (int) $idLogado;
            $quando = $agora;
        }

        if ($anterior) {
            $upd->bind_param("siiisii", $i['descricao'], $concluido, $ordem, $quem, $quando, $i['id_item'], $id);
            $upd->execute();
        } else {
            $ins->bind_param("isiiis", $id, $i['descricao'], $concluido, $ordem, $quem, $quando);
            $ins->execute();
        }
    }
    $upd->close();
    $ins->close();
}

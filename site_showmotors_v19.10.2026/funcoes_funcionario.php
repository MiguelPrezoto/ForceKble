<?php
// funcoes compartilhadas da area do funcionario
// usadas pelas paginas (painel, agendamentos, historico, checklist) e por api_funcionario.php

$ROTULOS_STATUS = [
    'AGENDADO'     => 'Agendado',
    'EM_ANDAMENTO' => 'Em andamento',
    'CONCLUIDO'    => 'Concluído',
    'CANCELADO'    => 'Cancelado',
];

// de qual status pode ir para qual (concluido e cancelado sao finais)
$TRANSICOES_STATUS = [
    'AGENDADO'     => ['EM_ANDAMENTO', 'CANCELADO'],
    'EM_ANDAMENTO' => ['CONCLUIDO', 'CANCELADO'],
    'CONCLUIDO'    => [],
    'CANCELADO'    => [],
];

// status em que o atendimento ainda pode ser editado e ter o checklist alterado
$STATUS_ATIVOS = ['AGENDADO', 'EM_ANDAMENTO'];


// token simples contra envio de formulario por outro site (usado nas chamadas POST da api)
function tokenCsrf()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function formatarDataHoraFunc($dataHora)
{
    if (empty($dataHora)) {
        return '';
    }
    $t = strtotime($dataHora);
    return date('d/m/Y', $t) . ' às ' . date('H:i', $t);
}

// a tabela checklist_item vem do script conexao/checklist_funcionario.sql
function checklistDisponivel(mysqli $conn)
{
    static $existe = null;
    if ($existe === null) {
        $existe = $conn->query("SHOW TABLES LIKE 'checklist_item'")->num_rows > 0;
    }
    return $existe;
}


// busca agendamentos com filtros
// $f: busca, status (string ou array), inicio, fim (Y-m-d), historico (bool), ordem ('asc'|'desc')
// retorna ['lista' => [...], 'total' => n]
function buscarAgendamentos(mysqli $conn, array $f, $limite = 0, $offset = 0)
{
    $where  = [];
    $types  = '';
    $params = [];

    $busca = trim($f['busca'] ?? '');
    if ($busca !== '') {
        $like = '%' . $busca . '%';
        $where[] = "(u.nome_cliente LIKE ? OR u.nome_usuario LIKE ? OR c.placa LIKE ?
                     OR CONCAT(c.marca, ' ', c.modelo) LIKE ? OR a.descricao LIKE ?
                     OR a.id_agendamento = ?)";
        $types  .= 'sssssi';
        array_push($params, $like, $like, $like, $like, $like, (int) ltrim($busca, '#'));
    }

    $status = $f['status'] ?? [];
    if (!is_array($status)) {
        $status = $status === '' ? [] : [$status];
    }
    $status = array_values(array_intersect($status, ['AGENDADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO']));
    if ($status) {
        $where[] = "a.status IN (" . implode(',', array_fill(0, count($status), '?')) . ")";
        $types  .= str_repeat('s', count($status));
        array_push($params, ...$status);
    }

    if (!empty($f['inicio']) && dataValida($f['inicio'])) {
        $where[] = "DATE(a.data_hora_servico) >= ?";
        $types  .= 's';
        $params[] = $f['inicio'];
    }
    if (!empty($f['fim']) && dataValida($f['fim'])) {
        $where[] = "DATE(a.data_hora_servico) <= ?";
        $types  .= 's';
        $params[] = $f['fim'];
    }

    // historico = atendimentos encerrados ou com data ja passada
    if (!empty($f['historico'])) {
        $where[] = "(a.status IN ('CONCLUIDO', 'CANCELADO') OR DATE(a.data_hora_servico) < CURDATE())";
    }

    $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $ordem    = (($f['ordem'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

    $from = "FROM agendamento a
             LEFT JOIN usuario u ON u.id_usuario = a.id_usuario
             LEFT JOIN carro   c ON c.id_carro   = a.id_carro
             $sqlWhere";

    $stmt = $conn->prepare("SELECT COUNT(*) AS qtd $from");
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int) $stmt->get_result()->fetch_assoc()['qtd'];
    $stmt->close();

    $sql = "SELECT a.id_agendamento, a.id_usuario, a.id_carro, a.data_hora_servico, a.data_hora_entrega,
                   a.descricao, a.status,
                   u.nome_cliente, u.nome_usuario, u.email, u.telefone, u.celular,
                   c.marca, c.modelo, c.placa, c.ano_modelo, c.cor
            $from
            ORDER BY a.data_hora_servico $ordem, a.id_agendamento $ordem";
    if ($limite > 0) {
        $sql .= " LIMIT " . (int) $limite . " OFFSET " . max(0, (int) $offset);
    }

    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    anexarServicos($conn, $lista);
    anexarProgressoChecklist($conn, $lista);

    return ['lista' => $lista, 'total' => $total];
}


// coloca em cada agendamento a lista de servicos e o valor total
function anexarServicos(mysqli $conn, array &$lista)
{
    $ids = array_map('intval', array_column($lista, 'id_agendamento'));
    $itens = [];

    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare(
            "SELECT asv.id_agendamento, asv.id_servico,
                    COALESCE(s.nome, 'Serviço removido') AS nome,
                    COALESCE(asv.preco_unitario, s.preco, 0) AS preco
             FROM agendamento_servicos asv
             LEFT JOIN servicos s ON s.id_servico = asv.id_servico
             WHERE asv.id_agendamento IN ($ph)
             ORDER BY nome"
        );
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
            $itens[(int) $item['id_agendamento']][] = [
                'id_servico' => (int) $item['id_servico'],
                'nome'       => $item['nome'],
                'preco'      => (float) $item['preco'],
            ];
        }
        $stmt->close();
    }

    foreach ($lista as &$a) {
        $a['servicos'] = $itens[(int) $a['id_agendamento']] ?? [];
        $a['total']    = array_sum(array_column($a['servicos'], 'preco'));
    }
    unset($a);
}


// coloca em cada agendamento quantas tarefas do checklist existem e quantas estao feitas
function anexarProgressoChecklist(mysqli $conn, array &$lista)
{
    $ids = array_map('intval', array_column($lista, 'id_agendamento'));
    $prog = [];

    if ($ids && checklistDisponivel($conn)) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare(
            "SELECT id_agendamento, COUNT(*) AS total, SUM(concluido) AS feitos
             FROM checklist_item
             WHERE id_agendamento IN ($ph)
             GROUP BY id_agendamento"
        );
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
            $prog[(int) $p['id_agendamento']] = ['total' => (int) $p['total'], 'feitos' => (int) $p['feitos']];
        }
        $stmt->close();
    }

    foreach ($lista as &$a) {
        $p = $prog[(int) $a['id_agendamento']] ?? ['total' => 0, 'feitos' => 0];
        $a['checklist_total']  = $p['total'];
        $a['checklist_feitos'] = $p['feitos'];
    }
    unset($a);
}


function buscarAgendamentoPorId(mysqli $conn, $id)
{
    $id = (int) $id;
    $stmt = $conn->prepare(
        "SELECT a.id_agendamento, a.id_usuario, a.id_carro, a.data_hora_servico, a.data_hora_entrega,
                a.descricao, a.status,
                u.nome_cliente, u.nome_usuario, u.email, u.telefone, u.celular,
                c.marca, c.modelo, c.placa, c.ano_modelo, c.cor
         FROM agendamento a
         LEFT JOIN usuario u ON u.id_usuario = a.id_usuario
         LEFT JOIN carro   c ON c.id_carro   = a.id_carro
         WHERE a.id_agendamento = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $a = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$a) {
        return null;
    }
    $lista = [$a];
    anexarServicos($conn, $lista);
    anexarProgressoChecklist($conn, $lista);
    return $lista[0];
}


function listarChecklist(mysqli $conn, $idAgendamento)
{
    $id = (int) $idAgendamento;
    $stmt = $conn->prepare(
        "SELECT ci.id_item, ci.descricao, ci.concluido, ci.data_conclusao, ci.id_usuario_conclusao,
                u.nome_usuario AS concluido_por
         FROM checklist_item ci
         LEFT JOIN usuario u ON u.id_usuario = ci.id_usuario_conclusao
         WHERE ci.id_agendamento = ?
         ORDER BY ci.ordem, ci.id_item"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($itens as &$i) {
        $i['id_item']   = (int) $i['id_item'];
        $i['concluido'] = (int) $i['concluido'] === 1;
        $i['id_usuario_conclusao'] = $i['id_usuario_conclusao'] === null ? null : (int) $i['id_usuario_conclusao'];
    }
    unset($i);
    return $itens;
}


// checklist vazio: cria uma tarefa para cada servico do agendamento
// retorna quantas tarefas foram criadas
function criarChecklistDosServicos(mysqli $conn, $idAgendamento)
{
    $id = (int) $idAgendamento;

    $stmt = $conn->prepare("SELECT COUNT(*) AS qtd FROM checklist_item WHERE id_agendamento = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $jaTem = (int) $stmt->get_result()->fetch_assoc()['qtd'] > 0;
    $stmt->close();
    if ($jaTem) {
        return 0;
    }

    $stmt = $conn->prepare(
        "SELECT s.nome FROM agendamento_servicos asv
         JOIN servicos s ON s.id_servico = asv.id_servico
         WHERE asv.id_agendamento = ?
         ORDER BY s.nome"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $nomes = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'nome');
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO checklist_item (id_agendamento, descricao, ordem) VALUES (?, ?, ?)");
    foreach ($nomes as $ordem => $nome) {
        $descricao = mb_substr($nome, 0, 150);
        $stmt->bind_param("isi", $id, $descricao, $ordem);
        $stmt->execute();
    }
    $stmt->close();

    return count($nomes);
}

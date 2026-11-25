<?php
// regras de agendamento compartilhadas entre agendar.php (cliente)
// e a area do funcionario (api_funcionario.php)

$HORARIOS = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
$DIAS_UTEIS = [1, 2, 3, 4, 5];
$VAGAS_POR_HORARIO = 1;
$DIAS_ANTECEDENCIA_MAX = 60;


function dataValida($data)
{
    $d = DateTime::createFromFormat('Y-m-d', (string) $data);
    return $d && $d->format('Y-m-d') === $data;
}


// cliente agenda a partir de amanha; funcionario pode encaixar no proprio dia ($permitirHoje)
function diaPermitido($data, $diasUteis, $maxDias, $permitirHoje = false)
{
    if (!dataValida($data)) {
        return false;
    }
    $hoje = new DateTime('today');
    $d = new DateTime($data);
    $limite = (clone $hoje)->modify("+$maxDias days");

    if ($d < $hoje || ($d == $hoje && !$permitirHoje) || $d > $limite) {
        return false;
    }
    return in_array((int) $d->format('N'), $diasUteis, true);
}


// $ignorarId: na edicao, o proprio agendamento nao ocupa a vaga dele
function vagasOcupadas($conn, $data, $ignorarId = 0)
{
    $ocupados = [];
    $ignorarId = (int) $ignorarId;
    $stmt = $conn->prepare(
        "SELECT DATE_FORMAT(data_hora_servico, '%H:%i') AS hora, COUNT(*) AS qtd
         FROM agendamento
         WHERE DATE(data_hora_servico) = ? AND status <> 'CANCELADO' AND id_agendamento <> ?
         GROUP BY hora"
    );
    $stmt->bind_param("si", $data, $ignorarId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($linha = $res->fetch_assoc()) {
        $ocupados[$linha['hora']] = (int) $linha['qtd'];
    }
    $stmt->close();
    return $ocupados;
}

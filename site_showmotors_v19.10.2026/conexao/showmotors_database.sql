-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Tempo de geração: 19/10/2026 às 11:48
-- Versão do servidor: 11.4.9-MariaDB
-- Versão do PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `showmotors_database`
--
CREATE DATABASE IF NOT EXISTS `showmotors_database` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `showmotors_database`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamento`
--

DROP TABLE IF EXISTS `agendamento`;
CREATE TABLE IF NOT EXISTS `agendamento` (
  `id_agendamento` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `id_carro` int(11) NOT NULL,
  `data_hora_servico` datetime NOT NULL,
  `data_hora_entrega` datetime DEFAULT NULL,
  `descricao` varchar(256) DEFAULT NULL,
  `status` enum('AGENDADO','EM_ANDAMENTO','CONCLUIDO','CANCELADO') NOT NULL DEFAULT 'AGENDADO',
  PRIMARY KEY (`id_agendamento`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_carro` (`id_carro`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `agendamento`
--

INSERT INTO `agendamento` (`id_agendamento`, `id_usuario`, `id_carro`, `data_hora_servico`, `data_hora_entrega`, `descricao`, `status`) VALUES
(1, 3, 1, '2026-10-14 17:00:00', NULL, 'tenho que trocar a matÃ©ria clara', 'CANCELADO'),
(2, 3, 1, '2026-10-14 14:00:00', NULL, 'real', 'AGENDADO'),
(3, 21, 2, '2026-10-07 10:00:00', '2026-10-07 17:30:00', 'Barulho ao frear', 'CONCLUIDO'),
(4, 22, 3, '2026-10-06 13:00:00', '2026-10-06 17:30:00', 'Revisão dos 40 mil km', 'CONCLUIDO'),
(5, 23, 4, '2026-10-16 16:00:00', '2026-10-16 17:30:00', 'Luz do motor acesa', 'CONCLUIDO'),
(6, 24, 5, '2026-10-19 14:00:00', NULL, 'Carro puxando para a direita', 'EM_ANDAMENTO'),
(7, 25, 6, '2026-10-19 08:00:00', NULL, 'Troca de óleo programada', 'EM_ANDAMENTO'),
(8, 26, 7, '2026-10-23 09:00:00', NULL, 'Ar-condicionado não gela', 'AGENDADO'),
(9, 27, 8, '2026-10-27 08:00:00', NULL, 'Bateria fraca de manhã', 'AGENDADO'),
(10, 28, 9, '2026-10-29 10:00:00', NULL, 'Vibração no volante', 'AGENDADO'),
(11, 21, 10, '2026-10-27 08:00:00', NULL, 'Revisão antes de viagem', 'CANCELADO'),
(12, 22, 11, '2026-10-16 16:00:00', '2026-10-16 17:30:00', 'Verificar correia', 'CONCLUIDO'),
(13, 21, 2, '2026-10-26 14:00:00', NULL, 'Manutenção preventiva', 'AGENDADO'),
(14, 22, 3, '2026-10-19 15:00:00', NULL, 'Barulho na suspensão', 'EM_ANDAMENTO');

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamento_servicos`
--

DROP TABLE IF EXISTS `agendamento_servicos`;
CREATE TABLE IF NOT EXISTS `agendamento_servicos` (
  `id_agendamento` int(11) NOT NULL,
  `id_servico` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_agendamento`,`id_servico`),
  KEY `id_servico` (`id_servico`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `agendamento_servicos`
--

INSERT INTO `agendamento_servicos` (`id_agendamento`, `id_servico`, `preco_unitario`) VALUES
(1, 11, 67.67),
(1, 12, 11.00),
(2, 11, 67.67),
(2, 12, 11.00),
(3, 17, 380.00),
(3, 14, 450.00),
(4, 15, 140.00),
(4, 17, 380.00),
(5, 15, 140.00),
(6, 20, 160.00),
(6, 16, 520.00),
(6, 15, 140.00),
(7, 13, 189.90),
(8, 18, 120.00),
(9, 14, 450.00),
(9, 16, 520.00),
(9, 15, 140.00),
(10, 14, 450.00),
(10, 15, 140.00),
(10, 16, 520.00),
(11, 16, 520.00),
(11, 19, 610.00),
(11, 15, 140.00),
(12, 18, 120.00),
(12, 13, 189.90),
(13, 18, 120.00),
(13, 15, 140.00),
(13, 20, 160.00),
(14, 19, 610.00),
(14, 15, 140.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `carro`
--

DROP TABLE IF EXISTS `carro`;
CREATE TABLE IF NOT EXISTS `carro` (
  `id_carro` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `marca` varchar(15) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `num_chassi` varchar(17) DEFAULT NULL,
  `ano_fabricacao` year(4) NOT NULL,
  `ano_modelo` year(4) NOT NULL,
  `cor` varchar(10) DEFAULT NULL,
  `placa` varchar(8) NOT NULL,
  `tipo_combustivel` varchar(20) DEFAULT NULL,
  `qtdd_portas` int(11) DEFAULT NULL,
  `nivel_tanque` varchar(12) DEFAULT NULL,
  `km_rodados` int(11) DEFAULT NULL,
  `direcao` varchar(15) DEFAULT NULL,
  `ar` varchar(15) DEFAULT NULL,
  `motor` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id_carro`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `carro`
--

INSERT INTO `carro` (`id_carro`, `id_usuario`, `marca`, `modelo`, `num_chassi`, `ano_fabricacao`, `ano_modelo`, `cor`, `placa`, `tipo_combustivel`, `qtdd_portas`, `nivel_tanque`, `km_rodados`, `direcao`, `ar`, `motor`) VALUES
(1, 3, 'ford', 'ka', NULL, '2017', '2017', 'branco', 'FOX4470', 'Flex', NULL, NULL, 44000, NULL, NULL, NULL),
(2, 21, 'fiat', 'uno', '9BWJR7J9VF3LJW931', '2012', '2012', 'vermelho', 'LYV9S98', 'Flex', 4, '1/2', 104000, 'hidraulica', 'sim', '1.0'),
(3, 22, 'volkswagen', 'gol', '9BW97XW5XH86LKP4Y', '2018', '2019', 'vermelho', 'CBM5A13', 'Flex', 4, '1/2', 25000, 'mecanica', 'nao', '1.6'),
(4, 23, 'chevrolet', 'onix', '9BWD5N7BJPJLM1U43', '2012', '2012', 'cinza', 'GXF0X79', 'Flex', 4, '3/4', 55000, 'eletrica', 'sim', '1.0'),
(5, 24, 'honda', 'civic', '9BWX75HUGPRAF6Y3M', '2016', '2016', 'prata', 'RSP4R32', 'Gasolina', 4, '1/2', 98000, 'mecanica', 'sim', '2.0'),
(6, 25, 'toyota', 'corolla', '9BWVUJGTG4LGDZMG9', '2013', '2014', 'prata', 'WZJ8V37', 'Flex', 4, 'reserva', 24000, 'eletrica', 'nao', '2.0'),
(7, 26, 'hyundai', 'hb20', '9BW3ADGSBWHGZX6C2', '2014', '2015', 'vermelho', 'JJC0P42', 'Flex', 4, '1/2', 160000, 'eletrica', 'nao', '1.0'),
(8, 27, 'renault', 'sandero', '9BW1VZD4BVYVHW3XB', '2022', '2023', 'branco', 'DYM8Y42', 'Flex', 4, 'reserva', 22000, 'mecanica', 'sim', '1.6'),
(9, 28, 'jeep', 'compass', '9BWZWB4JZ311S3RJM', '2014', '2014', 'azul', 'WDD0E76', 'Diesel', 4, 'reserva', 150000, 'eletrica', 'nao', '2.0'),
(10, 21, 'fiat', 'strada', '9BW73TXJFMC9TYXDA', '2024', '2024', 'cinza', 'EHA0J79', 'Flex', 2, '1/4', 64000, 'mecanica', 'sim', '1.4'),
(11, 22, 'ford', 'fiesta', '9BWYN0Z667WNL3K8A', '2023', '2024', 'vermelho', 'NZM3U45', 'Flex', 4, 'cheio', 17000, 'mecanica', 'sim', '1.6');

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist`
--

DROP TABLE IF EXISTS `checklist`;
CREATE TABLE IF NOT EXISTS `checklist` (
  `id_checklist` int(11) NOT NULL AUTO_INCREMENT,
  `id_carro` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `diagnostico` varchar(256) NOT NULL,
  PRIMARY KEY (`id_checklist`),
  UNIQUE KEY `id_carro` (`id_carro`),
  UNIQUE KEY `id_usuario` (`id_usuario`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `checklist`
--

INSERT INTO `checklist` (`id_checklist`, `id_carro`, `id_usuario`, `diagnostico`) VALUES
(1, 2, 21, 'Pastilhas desgastadas, discos ok'),
(2, 3, 22, 'Óleo e filtros trocados, sem vazamentos'),
(3, 4, 23, 'Sensor de oxigênio com falha intermitente'),
(4, 5, 24, 'Geometria desalinhada, pneus com desgaste irregular'),
(5, 6, 25, 'Bateria com 40% da capacidade');

-- --------------------------------------------------------

--
-- Estrutura para tabela `checklist_item`
--

DROP TABLE IF EXISTS `checklist_item`;
CREATE TABLE IF NOT EXISTS `checklist_item` (
  `id_item` int(11) NOT NULL AUTO_INCREMENT,
  `id_agendamento` int(11) NOT NULL,
  `descricao` varchar(150) NOT NULL,
  `concluido` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `id_usuario_conclusao` int(11) DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `data_criacao` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_item`),
  KEY `id_agendamento` (`id_agendamento`)
) ENGINE=MyISAM AUTO_INCREMENT=43 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `checklist_item`
--

INSERT INTO `checklist_item` (`id_item`, `id_agendamento`, `descricao`, `concluido`, `ordem`, `id_usuario_conclusao`, `data_conclusao`, `data_criacao`) VALUES
(1, 3, 'Receber o veículo e anotar a quilometragem', 1, 1, 2, '2026-10-07 10:47:00', '2026-10-19 11:25:08'),
(2, 3, 'Inspeção visual externa e interna', 1, 2, 19, '2026-10-07 11:57:00', '2026-10-19 11:25:08'),
(3, 3, 'Executar o serviço contratado', 1, 3, 19, '2026-10-07 12:34:00', '2026-10-19 11:25:08'),
(4, 3, 'Teste de rodagem', 1, 4, 19, '2026-10-07 13:54:00', '2026-10-19 11:25:08'),
(5, 3, 'Lavagem rápida e conferência final', 1, 5, 2, '2026-10-07 14:37:00', '2026-10-19 11:25:08'),
(6, 3, 'Avisar o cliente para retirada', 1, 6, 20, '2026-10-07 15:36:00', '2026-10-19 11:25:08'),
(7, 4, 'Receber o veículo e anotar a quilometragem', 1, 1, 19, '2026-10-06 10:05:00', '2026-10-19 11:25:08'),
(8, 4, 'Inspeção visual externa e interna', 1, 2, 19, '2026-10-06 11:46:00', '2026-10-19 11:25:08'),
(9, 4, 'Executar o serviço contratado', 1, 3, 20, '2026-10-06 12:21:00', '2026-10-19 11:25:08'),
(10, 4, 'Teste de rodagem', 1, 4, 2, '2026-10-06 13:16:00', '2026-10-19 11:25:08'),
(11, 4, 'Lavagem rápida e conferência final', 1, 5, 20, '2026-10-06 14:02:00', '2026-10-19 11:25:08'),
(12, 4, 'Avisar o cliente para retirada', 1, 6, 2, '2026-10-06 15:37:00', '2026-10-19 11:25:08'),
(13, 5, 'Receber o veículo e anotar a quilometragem', 1, 1, 20, '2026-10-16 10:18:00', '2026-10-19 11:25:08'),
(14, 5, 'Inspeção visual externa e interna', 1, 2, 2, '2026-10-16 11:18:00', '2026-10-19 11:25:08'),
(15, 5, 'Executar o serviço contratado', 1, 3, 20, '2026-10-16 12:08:00', '2026-10-19 11:25:08'),
(16, 5, 'Teste de rodagem', 1, 4, 20, '2026-10-16 13:12:00', '2026-10-19 11:25:08'),
(17, 5, 'Lavagem rápida e conferência final', 1, 5, 2, '2026-10-16 14:40:00', '2026-10-19 11:25:08'),
(18, 5, 'Avisar o cliente para retirada', 1, 6, 19, '2026-10-16 15:54:00', '2026-10-19 11:25:08'),
(19, 6, 'Receber o veículo e anotar a quilometragem', 1, 1, 19, '2026-10-19 10:46:00', '2026-10-19 11:25:08'),
(20, 6, 'Inspeção visual externa e interna', 1, 2, 2, '2026-10-19 11:01:00', '2026-10-19 11:25:08'),
(21, 6, 'Executar o serviço contratado', 1, 3, 19, '2026-10-19 12:20:00', '2026-10-19 11:25:08'),
(22, 6, 'Teste de rodagem', 0, 4, NULL, NULL, '2026-10-19 11:25:08'),
(23, 6, 'Lavagem rápida e conferência final', 0, 5, NULL, NULL, '2026-10-19 11:25:08'),
(24, 6, 'Avisar o cliente para retirada', 0, 6, NULL, NULL, '2026-10-19 11:25:08'),
(25, 7, 'Receber o veículo e anotar a quilometragem', 1, 0, 19, '2026-10-19 10:23:00', '2026-10-19 11:25:08'),
(26, 7, 'Inspeção visual externa e interna', 1, 1, 20, '2026-10-19 11:13:00', '2026-10-19 11:25:08'),
(27, 7, 'Executar o serviço contratado', 1, 2, 2, '2026-10-19 08:27:56', '2026-10-19 11:25:08'),
(28, 7, 'Teste de rodagem', 0, 3, NULL, NULL, '2026-10-19 11:25:08'),
(29, 7, 'Lavagem rápida e conferência final', 0, 4, NULL, NULL, '2026-10-19 11:25:08'),
(30, 7, 'Avisar o cliente para retirada', 0, 5, NULL, NULL, '2026-10-19 11:25:08'),
(31, 12, 'Receber o veículo e anotar a quilometragem', 1, 1, 19, '2026-10-16 10:49:00', '2026-10-19 11:25:08'),
(32, 12, 'Inspeção visual externa e interna', 1, 2, 19, '2026-10-16 11:51:00', '2026-10-19 11:25:08'),
(33, 12, 'Executar o serviço contratado', 1, 3, 20, '2026-10-16 12:49:00', '2026-10-19 11:25:08'),
(34, 12, 'Teste de rodagem', 1, 4, 20, '2026-10-16 13:16:00', '2026-10-19 11:25:08'),
(35, 12, 'Lavagem rápida e conferência final', 1, 5, 2, '2026-10-16 14:14:00', '2026-10-19 11:25:08'),
(36, 12, 'Avisar o cliente para retirada', 1, 6, 19, '2026-10-16 15:20:00', '2026-10-19 11:25:08'),
(37, 14, 'Receber o veículo e anotar a quilometragem', 1, 1, 19, '2026-10-19 10:51:00', '2026-10-19 11:25:08'),
(38, 14, 'Inspeção visual externa e interna', 0, 2, NULL, NULL, '2026-10-19 11:25:08'),
(39, 14, 'Executar o serviço contratado', 0, 3, NULL, NULL, '2026-10-19 11:25:08'),
(40, 14, 'Teste de rodagem', 0, 4, NULL, NULL, '2026-10-19 11:25:08'),
(41, 14, 'Lavagem rápida e conferência final', 0, 5, NULL, NULL, '2026-10-19 11:25:08'),
(42, 14, 'Avisar o cliente para retirada', 0, 6, NULL, NULL, '2026-10-19 11:25:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_recuperacao`
--

DROP TABLE IF EXISTS `log_recuperacao`;
CREATE TABLE IF NOT EXISTS `log_recuperacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` char(6) NOT NULL,
  `enviado` tinyint(1) NOT NULL DEFAULT 0,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `log_recuperacao`
--

INSERT INTO `log_recuperacao` (`id`, `id_usuario`, `email`, `token`, `enviado`, `usado`, `criado_em`, `expira_em`) VALUES
(1, 21, 'ana.costa@example.com', '429601', 1, 1, '2026-10-12 10:10:00', '2026-10-12 10:25:00'),
(2, 23, 'fernanda.oliveira@example.com', '872018', 1, 0, '2026-10-13 10:17:00', '2026-10-13 10:32:00'),
(3, 25, 'patricia.gomes@example.com', '842257', 1, 0, '2026-10-14 10:24:00', '2026-10-14 10:39:00'),
(4, 22, 'joao.almeida@example.com', '618516', 0, 0, '2026-10-19 08:40:00', '2026-10-19 08:55:00'),
(5, 1, 'admin@email.com', '684769', 0, 1, '2026-10-19 08:32:28', '2026-10-19 11:47:28'),
(6, 1, 'admin@email.com', '930270', 0, 1, '2026-10-19 08:32:30', '2026-10-19 11:47:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

DROP TABLE IF EXISTS `servicos`;
CREATE TABLE IF NOT EXISTS `servicos` (
  `id_servico` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `descricao` varchar(600) DEFAULT NULL,
  PRIMARY KEY (`id_servico`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id_servico`, `nome`, `preco`, `descricao`) VALUES
(12, 'Julia Pena', 0.01, 'Pague 1 leve 2, crispim vem depois.'),
(13, 'Troca de óleo e filtro', 189.90, 'Óleo sintético 5W30 e filtro de óleo'),
(14, 'Revisão completa', 450.00, 'Revisão de 30 pontos com relatório'),
(15, 'Alinhamento e balanceamento', 140.00, 'Alinhamento 3D e balanceamento das 4 rodas'),
(16, 'Troca de bateria', 520.00, 'Bateria 60Ah com garantia de 18 meses'),
(17, 'Pastilhas e discos de freio', 380.00, 'Troca do conjunto dianteiro'),
(18, 'Diagnóstico eletrônico', 120.00, 'Leitura de falhas via scanner OBD2'),
(19, 'Troca de correia dentada', 610.00, 'Correia, tensor e rolamentos'),
(20, 'Higienização do ar-condicionado', 160.00, 'Limpeza do sistema e troca do filtro de cabine');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nome_cliente` varchar(50) NOT NULL,
  `nome_usuario` varchar(50) NOT NULL,
  `senha` varchar(60) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) NOT NULL,
  `telefone` varchar(14) DEFAULT NULL,
  `celular` varchar(15) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `nivel_acesso` enum('ADM','FUNCIONARIO','CLIENTE') NOT NULL DEFAULT 'CLIENTE',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nome_usuario` (`nome_usuario`)
) ENGINE=MyISAM AUTO_INCREMENT=29 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome_cliente`, `nome_usuario`, `senha`, `data_cadastro`, `email`, `telefone`, `celular`, `cpf`, `nivel_acesso`) VALUES
(2, 'funcionario', 'funcionario', '$2y$10$QstHp8DepIy4MeyGcVp7C.znYp08QvpSnSfBMEYCuOXH3pvZamO9.', '2026-10-04 15:06:44', 'funcionario@email.com', '(11) 4002-0652', NULL, NULL, 'FUNCIONARIO'),
(1, 'admin', 'admin', '$2y$10$4bzLykLEw7OvetKn3epbA.Zg/YQd7W/nIdKeA9gAKRWukvlGcXmcC', '2026-10-04 15:06:14', 'admin@email.com', '(11) 4002-0652', '', '', 'ADM'),
(3, 'cliente', 'cliente', '$2y$10$S7nqAaA8dht3KAKC8E.cWeU42cEecN1EaLNzyhZr8V7QgQv9dh3Ca', '2026-10-04 15:08:25', 'cliente@email.com', '(11) 4002-0652', NULL, NULL, 'CLIENTE'),
(9, 'teste cliente', 'teste.cliente', '$2y$10$CN8yv6BcO8DKrxyfcfI3hORtDlhZo9qgNf8/a5gL/86fKH5CtQRb2', '2026-10-04 15:21:20', 'testecliente@email.com', '(11) 4002-0652', '(11) 99999-9999', '255.668.810-68', 'CLIENTE'),
(18, 'henrique bonato', 'henrique.bonato', '$2y$10$iDbnE6Wj35vvr93/.263ge3j4mtQzuuTysJkT4kOC.jc4L1H6NCUa', '2026-10-05 10:30:46', 'henrique@gmail.com', '(11) 4444-4444', '(11) 99999-9999', '103.230.539-80', 'ADM'),
(19, 'Mariana Souza Lima', 'mariana.lima', '$2y$10$ScZnvLVIgA2PvN20dXiNz.zRiYV8Srwp8bX2jojr6zm5CeQkpOHEu', '2026-10-06 09:56:00', 'mariana.lima@example.com', '(11) 3427-9233', NULL, '399.869.879-54', 'FUNCIONARIO'),
(20, 'Carlos Eduardo Ramos', 'carlos.ramos', '$2y$10$AXa7tJQcx.30L5L6P2ksK.XYCOtIIwHAZjxyKYru6ehiqKSr99DAK', '2026-10-07 10:18:00', 'carlos.ramos@example.com', '(11) 4345-1041', '(11) 92322-2814', '170.753.645-70', 'FUNCIONARIO'),
(21, 'Ana Beatriz Costa', 'ana.costa', '$2y$10$hiW5altYQ5mCkrDBcwS.guP1sam4N73GQ9KuM8HlhchLnXvhYqXJu', '2026-10-08 11:46:00', 'ana.costa@example.com', '(11) 4924-7166', '(11) 99414-2250', '518.844.729-07', 'CLIENTE'),
(22, 'João Pedro Almeida', 'joao.almeida', '$2y$10$nqNvTu3XcR9.uyUb7r0gk.NJxgBWYYsu/dXzKieluWxzlQ9xCgdcK', '2026-10-09 12:27:00', 'joao.almeida@example.com', '(11) 4125-7043', NULL, '169.871.689-38', 'CLIENTE'),
(23, 'Fernanda Oliveira', 'fernanda.oliveira', '$2y$10$GzvNdV2/sGZ9Wni.xtu56uB6vA810Gc7UM8TDBcFYaQ1oujTzLv7u', '2026-10-10 13:36:00', 'fernanda.oliveira@example.com', '(11) 4507-9595', '(11) 95237-7880', '788.093.208-91', 'CLIENTE'),
(24, 'Ricardo Menezes', 'ricardo.menezes', '$2y$10$6Yjr/C3LmtIYOa42F7OvPe94jtjfSwLRsPM0Sye.vez.6kWh4spUu', '2026-10-11 14:30:00', 'ricardo.menezes@example.com', '(11) 3834-8035', '(11) 98701-3333', '317.784.864-52', 'CLIENTE'),
(25, 'Patrícia Gomes', 'patricia.gomes', '$2y$10$Lji4uWjFhHTlRLgGopBO8el0t6DMdSPtphiXvRclEeGPBDn.C2uiS', '2026-10-12 15:18:00', 'patricia.gomes@example.com', '(11) 4924-7585', NULL, '400.297.875-30', 'CLIENTE'),
(26, 'Lucas Ferreira', 'lucas.ferreira', '$2y$10$MvxfZcoWMr014jJVcntFduDAf4Cr5xmVlmNBA9RJNEnXV4/WwRqgy', '2026-10-13 16:49:00', 'lucas.ferreira@example.com', '(11) 3366-8955', '(11) 98645-9376', '768.045.717-46', 'CLIENTE'),
(27, 'Juliana Martins', 'juliana.martins', '$2y$10$BAu0vkx9cHNjUpizHHJi1.EmxvvS9bLxLJhEqp5fF7a82WnmWCIBG', '2026-10-14 09:04:00', 'juliana.martins@example.com', '(11) 4563-3999', '(11) 97975-8850', '475.136.270-45', 'CLIENTE'),
(28, 'Roberto Nogueira', 'roberto.nogueira', '$2y$10$Ic9vn4QP4PcK3iFSA.haVOucvoN7KiHHwPD9tbplVUkR/rE1N7wS6', '2026-10-15 10:39:00', 'roberto.nogueira@example.com', '(11) 3100-8392', NULL, '331.908.452-63', 'CLIENTE');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

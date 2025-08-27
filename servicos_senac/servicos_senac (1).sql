-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 21/07/2025 às 00:02
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `servicos_senac`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradores`
--

CREATE TABLE `administradores` (
  `id_administrador` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `administradores`
--

INSERT INTO `administradores` (`id_administrador`, `nome`, `email`, `senha`, `data_cadastro`) VALUES
(1, 'Admin Geral', 'admin@servicos.com', '$2y$10$4QLwLpyAtVcKgIIlLcj/6eHh1PF/qpxm7axeca.8nSQ2HXSG2AG0i', '2025-07-19 13:22:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id_avaliacao` int(11) NOT NULL,
  `id_servico` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `nota` int(11) NOT NULL CHECK (`nota` >= 1 and `nota` <= 5),
  `comentario` text DEFAULT NULL,
  `data_avaliacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id_avaliacao`, `id_servico`, `id_usuario`, `nota`, `comentario`, `data_avaliacao`) VALUES
(1, 1, 5, 5, 'Excelente trabalho! O logotipo ficou perfeito.', '2025-07-19 13:22:51'),
(2, 2, 6, 4, 'Site muito bem feito e responsivo. Recomendo!', '2025-07-19 13:22:51'),
(3, 3, 1, 4, 'Bom gerenciamento das redes sociais, mas poderia ter mais posts.', '2025-07-19 13:22:51'),
(4, 4, 2, 5, 'Fotos incríveis, superou minhas expectativas.', '2025-07-19 13:22:51'),
(5, 1, 3, 4, 'Rápido e eficiente, gostei do resultado.', '2025-07-19 13:22:51'),
(6, 2, 4, 5, 'Profissional muito atencioso e entregou no prazo.', '2025-07-19 13:22:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nome_categoria` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nome_categoria`, `descricao`) VALUES
(1, 'Saúde e Bem-Estar', 'Serviços relacionados à saúde e bem-estar.'),
(2, 'Desenvolvimento', 'Criação de websites, aplicativos e sistemas.'),
(3, 'Marketing Digital', 'Serviços de SEO, mídias sociais e campanhas de marketing.'),
(4, 'Beleza e Estética', 'Serviços de cabeleireiro, maquiagem, manicure e massagens.'),
(5, 'Design Gráfico', 'Criação de logotipos, identidade visual e materiais gráficos.'),
(6, 'Fotografia e Vídeo', 'Serviços de fotografia e edição de vídeo.'),
(8, 'Consultoria', 'Consultoria em diversas áreas de negócio e pessoal.'),
(9, 'Cuidador de Idoso', 'Cuidar de Idosos');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidades_senac_mg`
--

CREATE TABLE `cidades_senac_mg` (
  `id_cidade` int(11) NOT NULL,
  `nome_cidade` varchar(100) NOT NULL,
  `uf` char(2) DEFAULT 'MG',
  `ativa` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cidades_senac_mg`
--

INSERT INTO `cidades_senac_mg` (`id_cidade`, `nome_cidade`, `uf`, `ativa`, `data_criacao`) VALUES
(1, 'Alfenas', 'MG', 1, '2025-07-19 15:06:05'),
(2, 'Araguari', 'MG', 1, '2025-07-19 15:06:05'),
(3, 'Araxá', 'MG', 1, '2025-07-19 15:06:05'),
(4, 'Arcos', 'MG', 1, '2025-07-19 15:06:05'),
(5, 'Barão de Cocais', 'MG', 1, '2025-07-19 15:06:05'),
(6, 'Barbacena', 'MG', 1, '2025-07-19 15:06:05'),
(7, 'Belo Horizonte', 'MG', 1, '2025-07-19 15:06:05'),
(8, 'Betim', 'MG', 1, '2025-07-19 15:06:05'),
(9, 'Boa Esperança', 'MG', 1, '2025-07-19 15:06:05'),
(10, 'Bom Despacho', 'MG', 1, '2025-07-19 15:06:05'),
(11, 'Bueno Brandão', 'MG', 1, '2025-07-19 15:06:05'),
(12, 'Camanducaia', 'MG', 1, '2025-07-19 15:06:05'),
(13, 'Campo Belo', 'MG', 1, '2025-07-19 15:06:05'),
(14, 'Carangola', 'MG', 1, '2025-07-19 15:06:05'),
(15, 'Caratinga', 'MG', 1, '2025-07-19 15:06:05'),
(16, 'Cataguases', 'MG', 1, '2025-07-19 15:06:05'),
(17, 'Caxambu', 'MG', 1, '2025-07-19 15:06:05'),
(18, 'Congonhas', 'MG', 1, '2025-07-19 15:06:05'),
(19, 'Conselheiro Lafaiete', 'MG', 1, '2025-07-19 15:06:05'),
(20, 'Contagem', 'MG', 1, '2025-07-19 15:06:05'),
(21, 'Coronel Fabriciano', 'MG', 1, '2025-07-19 15:06:05'),
(22, 'Curvelo', 'MG', 1, '2025-07-19 15:06:05'),
(23, 'Diamantina', 'MG', 1, '2025-07-19 15:06:05'),
(24, 'Divinópolis', 'MG', 1, '2025-07-19 15:06:05'),
(25, 'Dores do Indaiá', 'MG', 1, '2025-07-19 15:06:05'),
(26, 'Entre Rios de Minas', 'MG', 1, '2025-07-19 15:06:05'),
(27, 'Esmeraldas', 'MG', 1, '2025-07-19 15:06:05'),
(28, 'Extrema', 'MG', 1, '2025-07-19 15:06:05'),
(29, 'Fama', 'MG', 1, '2025-07-19 15:06:05'),
(30, 'Formiga', 'MG', 1, '2025-07-19 15:06:05'),
(31, 'Frutal', 'MG', 1, '2025-07-19 15:06:05'),
(32, 'Gonçalves', 'MG', 1, '2025-07-19 15:06:05'),
(33, 'Governador Valadares', 'MG', 1, '2025-07-19 15:06:05'),
(34, 'Guaxupé', 'MG', 1, '2025-07-19 15:06:05'),
(35, 'Ibirité', 'MG', 1, '2025-07-19 15:06:05'),
(36, 'Igarapé', 'MG', 1, '2025-07-19 15:06:05'),
(37, 'Ipatinga', 'MG', 1, '2025-07-19 15:06:05'),
(38, 'Itabira', 'MG', 1, '2025-07-19 15:06:05'),
(39, 'Itabirito', 'MG', 1, '2025-07-19 15:06:05'),
(40, 'Itajubá', 'MG', 1, '2025-07-19 15:06:05'),
(41, 'Itambacuri', 'MG', 1, '2025-07-19 15:06:05'),
(42, 'Itanhandu', 'MG', 1, '2025-07-19 15:06:05'),
(43, 'Itapecerica', 'MG', 1, '2025-07-19 15:06:05'),
(44, 'Itaúna', 'MG', 1, '2025-07-19 15:06:05'),
(45, 'Ituiutaba', 'MG', 1, '2025-07-19 15:06:05'),
(46, 'Jaboticatubas', 'MG', 1, '2025-07-19 15:06:05'),
(47, 'Jacutinga', 'MG', 1, '2025-07-19 15:06:05'),
(48, 'Janaúba', 'MG', 1, '2025-07-19 15:06:05'),
(49, 'João Pinheiro', 'MG', 1, '2025-07-19 15:06:05'),
(50, 'Juatuba', 'MG', 1, '2025-07-19 15:06:05'),
(51, 'Juiz de Fora', 'MG', 1, '2025-07-19 15:06:05'),
(52, 'Lagoa da Prata', 'MG', 1, '2025-07-19 15:06:05'),
(53, 'Lagoa Santa', 'MG', 1, '2025-07-19 15:06:05'),
(54, 'Lambari', 'MG', 1, '2025-07-19 15:06:05'),
(55, 'Lavras', 'MG', 1, '2025-07-19 15:06:05'),
(56, 'Leopoldina', 'MG', 1, '2025-07-19 15:06:05'),
(57, 'Manhuaçu', 'MG', 1, '2025-07-19 15:06:05'),
(58, 'Mariana', 'MG', 1, '2025-07-19 15:06:05'),
(59, 'Mateus Leme', 'MG', 1, '2025-07-19 15:06:05'),
(60, 'Montes Claros', 'MG', 1, '2025-07-19 15:06:05'),
(61, 'Muriaé', 'MG', 1, '2025-07-19 15:06:05'),
(62, 'Nova Lima', 'MG', 1, '2025-07-19 15:06:05'),
(63, 'Nova Serrana', 'MG', 1, '2025-07-19 15:06:05'),
(64, 'Ouro Branco', 'MG', 1, '2025-07-19 15:06:05'),
(65, 'Ouro Preto', 'MG', 1, '2025-07-19 15:06:05'),
(66, 'Pará de Minas', 'MG', 1, '2025-07-19 15:06:05'),
(67, 'Paracatu', 'MG', 1, '2025-07-19 15:06:05'),
(68, 'Passa Quatro', 'MG', 1, '2025-07-19 15:06:05'),
(69, 'Passos', 'MG', 1, '2025-07-19 15:06:05'),
(70, 'Patrocínio', 'MG', 1, '2025-07-19 15:06:05'),
(71, 'Pedro Leopoldo', 'MG', 1, '2025-07-19 15:06:05'),
(72, 'Poços de Caldas', 'MG', 1, '2025-07-19 15:06:05'),
(73, 'Ponte Nova', 'MG', 1, '2025-07-19 15:06:05'),
(74, 'Pouso Alegre', 'MG', 1, '2025-07-19 15:06:05'),
(75, 'Resplendor', 'MG', 1, '2025-07-19 15:06:05'),
(76, 'Ribeirão das Neves', 'MG', 1, '2025-07-19 15:06:05'),
(77, 'Sabará', 'MG', 1, '2025-07-19 15:06:05'),
(78, 'Salinas', 'MG', 1, '2025-07-19 15:06:05'),
(79, 'Santa Luzia', 'MG', 1, '2025-07-19 15:06:05'),
(80, 'Santa Rita de Ibitipoca', 'MG', 1, '2025-07-19 15:06:05'),
(81, 'Santana do Riacho', 'MG', 1, '2025-07-19 15:06:05'),
(82, 'Santos Dumont', 'MG', 1, '2025-07-19 15:06:05'),
(83, 'São João Del Rei', 'MG', 1, '2025-07-19 15:06:05'),
(84, 'São João Nepomuceno', 'MG', 1, '2025-07-19 15:06:05'),
(85, 'São Joaquim de Bicas', 'MG', 1, '2025-07-19 15:06:05'),
(86, 'São José da Barra', 'MG', 1, '2025-07-19 15:06:05'),
(87, 'São José da Lapa', 'MG', 1, '2025-07-19 15:06:05'),
(88, 'São Lourenço', 'MG', 1, '2025-07-19 15:06:05'),
(89, 'São Thomé das Letras', 'MG', 1, '2025-07-19 15:06:05'),
(90, 'Sete Lagoas', 'MG', 1, '2025-07-19 15:06:05'),
(91, 'Teófilo Ottoni', 'MG', 1, '2025-07-19 15:06:05'),
(92, 'Timóteo', 'MG', 1, '2025-07-19 15:06:05'),
(93, 'Tiradentes', 'MG', 1, '2025-07-19 15:06:05'),
(94, 'Três Marias', 'MG', 1, '2025-07-19 15:06:05'),
(95, 'Turmalina', 'MG', 1, '2025-07-19 15:06:05'),
(96, 'Ubá', 'MG', 1, '2025-07-19 15:06:05'),
(97, 'Uberaba', 'MG', 1, '2025-07-19 15:06:05'),
(98, 'Uberlândia', 'MG', 1, '2025-07-19 15:06:05'),
(99, 'Varginha', 'MG', 1, '2025-07-19 15:06:05'),
(100, 'Viçosa', 'MG', 1, '2025-07-19 15:06:05'),
(101, 'Volta Grande', 'MG', 1, '2025-07-19 15:06:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id_mensagem` int(11) NOT NULL,
  `id_remetente` int(11) NOT NULL,
  `id_destinatario` int(11) NOT NULL,
  `id_orcamento` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mensagens`
--

INSERT INTO `mensagens` (`id_mensagem`, `id_remetente`, `id_destinatario`, `id_orcamento`, `mensagem`, `data_envio`, `lida`) VALUES
(1, 5, 1, 1, 'Olá! Gostaria de saber mais detalhes sobre o logotipo.', '2025-07-19 13:22:51', 1),
(2, 1, 5, 1, 'Claro! Posso criar um logotipo moderno e profissional para sua empresa.', '2025-07-19 13:22:51', 1),
(3, 5, 1, 1, 'Perfeito! Quando podemos começar?', '2025-07-19 13:22:51', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id_notificacao` int(11) NOT NULL,
  `id_usuario_destino` int(11) NOT NULL,
  `tipo_notificacao` varchar(50) NOT NULL,
  `mensagem` text NOT NULL,
  `link_acao` varchar(255) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id_notificacao`, `id_usuario_destino`, `tipo_notificacao`, `mensagem`, `link_acao`, `lida`, `data_criacao`) VALUES
(1, 1, 'orcamento_recebido', 'Você recebeu um novo orçamento para o serviço de Design Gráfico.', 'orcamento.php?id=1', 0, '2025-07-19 13:22:51'),
(2, 2, 'servico_fechado', 'Seu serviço foi concluído com sucesso!', 'servico.php?id=1', 0, '2025-07-19 13:22:51'),
(3, 3, 'nova_mensagem', 'Você recebeu uma nova mensagem no chat.', 'chat.php?id=1', 0, '2025-07-19 13:22:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `orcamentos`
--

CREATE TABLE `orcamentos` (
  `id_orcamento` int(11) NOT NULL,
  `id_servico` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_profissional` int(11) NOT NULL,
  `status` enum('pendente','aceito','recusado','concluido','cancelado') DEFAULT 'pendente',
  `valor_proposto` decimal(10,2) DEFAULT NULL,
  `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `orcamentos`
--

INSERT INTO `orcamentos` (`id_orcamento`, `id_servico`, `id_cliente`, `id_profissional`, `status`, `valor_proposto`, `data_solicitacao`, `data_atualizacao`) VALUES
(1, 1, 5, 1, 'aceito', 250.00, '2025-07-19 13:22:51', '2025-07-19 13:22:51'),
(2, 2, 6, 2, 'pendente', 1200.00, '2025-07-19 13:22:51', '2025-07-19 13:22:51'),
(3, 3, 1, 3, 'concluido', 600.00, '2025-07-19 13:22:51', '2025-07-19 13:22:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `profissionais`
--

CREATE TABLE `profissionais` (
  `id_profissional` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `area_atuacao` varchar(255) DEFAULT NULL,
  `descricao_perfil` text DEFAULT NULL,
  `curriculo_url` varchar(255) DEFAULT NULL,
  `foto_perfil_url` varchar(255) DEFAULT NULL,
  `media_avaliacao` decimal(2,1) DEFAULT 0.0,
  `total_avaliacoes` int(11) DEFAULT 0,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `cidade_id` int(11) DEFAULT NULL,
  `endereco_comercial` text DEFAULT NULL,
  `disponibilidade` enum('disponivel','ocupado','indisponivel') DEFAULT 'disponivel',
  `aceita_orcamento` tinyint(1) DEFAULT 1,
  `atende_presencial` tinyint(1) DEFAULT 1,
  `atende_online` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `profissionais`
--

INSERT INTO `profissionais` (`id_profissional`, `id_usuario`, `cpf`, `area_atuacao`, `descricao_perfil`, `curriculo_url`, `foto_perfil_url`, `media_avaliacao`, `total_avaliacoes`, `data_cadastro`, `cidade_id`, `endereco_comercial`, `disponibilidade`, `aceita_orcamento`, `atende_presencial`, `atende_online`) VALUES
(1, 1, '111.111.111-11', 'Design Gráfico', 'Designer gráfica especializada em identidade visual e materiais impressos.', 'curriculo_ana.pdf', 'foto_ana.jpg', 4.5, 10, '2025-07-19 13:22:51', 7, NULL, 'disponivel', 1, 1, 0),
(2, 2, '222.222.222-22', 'Desenvolvimento Web', 'Desenvolvedor web full-stack com experiência em PHP, MySQL, HTML, CSS e JavaScript.', 'curriculo_carlos.pdf', 'foto_carlos.jpg', 4.8, 15, '2025-07-19 13:22:51', 7, NULL, 'disponivel', 1, 1, 0),
(3, 3, '333.333.333-33', 'Marketing Digital', 'Especialista em marketing digital, SEO e gestão de mídias sociais.', 'curriculo_juliana.pdf', 'foto_juliana.jpg', 4.2, 8, '2025-07-19 13:22:51', 7, NULL, 'disponivel', 1, 1, 0),
(4, 4, '444.444.444-44', 'Fotografia', 'Fotógrafo profissional para eventos, retratos e produtos.', 'curriculo_rafael.pdf', 'foto_rafael.jpg', 4.7, 12, '2025-07-19 13:22:51', 7, NULL, 'disponivel', 1, 1, 0),
(5, 10, '363.002.56', 'TI', 'FAÇO SERVIÇOS EM GERAL EM TI', NULL, NULL, 0.0, 0, '2025-07-19 14:30:00', 7, NULL, 'disponivel', 1, 1, 0),
(7, 12, '772.971.216-49', 'saude', 'Cuidador de idosos', NULL, NULL, 0.0, 0, '2025-07-20 18:46:14', NULL, NULL, 'disponivel', 1, 1, 0),
(8, 13, '100.057.676-01', 'saude', 'DFDFDFD', NULL, NULL, 0.0, 0, '2025-07-20 18:47:48', NULL, NULL, 'disponivel', 1, 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id_servico` int(11) NOT NULL,
  `id_profissional` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `tempo_entrega` varchar(100) DEFAULT NULL,
  `media_avaliacao` decimal(2,1) DEFAULT 0.0,
  `total_avaliacoes` int(11) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `cidade_id` int(11) DEFAULT NULL,
  `tipo_servico` enum('presencial','online','hibrido') DEFAULT 'presencial'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id_servico`, `id_profissional`, `id_categoria`, `titulo`, `descricao`, `preco`, `tempo_entrega`, `media_avaliacao`, `total_avaliacoes`, `data_criacao`, `cidade_id`, `tipo_servico`) VALUES
(1, 1, 5, 'Criação de Logotipos Profissionais', 'Criação de logotipos exclusivos para sua empresa ou projeto, com até 3 propostas e revisões ilimitadas.', 250.00, '5 dias úteis', 4.5, 10, '2025-07-19 13:22:51', 7, 'presencial'),
(2, 2, 2, 'Desenvolvimento de Sites Responsivos', 'Criação de sites modernos e responsivos, otimizados para SEO e com painel administrativo.', 1200.00, '15 dias úteis', 4.8, 15, '2025-07-19 13:22:51', 7, 'presencial'),
(3, 3, 3, 'Gestão de Redes Sociais', 'Planejamento, criação de conteúdo e gerenciamento completo de suas redes sociais.', 600.00, 'Mensal', 4.2, 8, '2025-07-19 13:22:51', 7, 'presencial'),
(4, 4, 6, 'Sessão de Fotos Profissionais', 'Sessão de fotos para portfólio, eventos ou produtos, com edição inclusa.', 350.00, '7 dias úteis', 4.7, 12, '2025-07-19 13:22:51', 7, 'presencial'),
(5, 1, 5, 'Identidade Visual Completa', 'Criação de logotipo, paleta de cores, tipografia e manual de marca.', 800.00, '10 dias úteis', 4.6, 7, '2025-07-19 13:22:51', 7, 'presencial'),
(6, 2, 2, 'Manutenção de Websites', 'Serviço de manutenção e atualização de websites existentes.', 300.00, 'Por demanda', 4.3, 5, '2025-07-19 13:22:51', 7, 'presencial');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `cidade_id` int(11) DEFAULT NULL,
  `tipo_usuario` enum('cliente','prestador','admin') DEFAULT 'cliente',
  `endereco_completo` text DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `nome`, `email`, `senha`, `telefone`, `endereco`, `data_cadastro`, `cidade_id`, `tipo_usuario`, `endereco_completo`, `cep`, `whatsapp`) VALUES
(1, 'Ana Silva', 'ana.silva@example.com', 'senha123', '11987654321', 'Rua A, 123, São Paulo', '2025-07-19 13:22:51', 7, 'prestador', NULL, NULL, NULL),
(2, 'Carlos Oliveira', 'carlos.o@example.com', 'senha123', '21998765432', 'Av. B, 456, Rio de Janeiro', '2025-07-19 13:22:51', 7, 'prestador', NULL, NULL, NULL),
(3, 'Juliana Santos', 'juliana.s@example.com', 'senha123', '31976543210', 'Rua C, 789, Belo Horizonte', '2025-07-19 13:22:51', 7, 'prestador', NULL, NULL, NULL),
(4, 'Rafael Mendes', 'rafael.m@example.com', 'senha123', '41965432109', 'Av. D, 101, Curitiba', '2025-07-19 13:22:51', 7, 'prestador', NULL, NULL, NULL),
(5, 'Fernanda Costa', 'fernanda.c@example.com', 'senha123', '51954321098', 'Rua E, 202, Porto Alegre', '2025-07-19 13:22:51', 7, 'cliente', NULL, NULL, NULL),
(6, 'Pedro Almeida', 'pedro.a@example.com', 'senha123', '61943210987', 'Av. F, 303, Brasília', '2025-07-19 13:22:51', 7, 'cliente', NULL, NULL, NULL),
(7, 'SARAH CAROLINE DA SILVA', 'rr@gmail.com', '$2y$10$ZeON7UKFnQukelOYLla40.4vt9ZqqNqlcAIjs8cnqO1byeiT7poUm', '(32) 99961-2303', 'José Pedro Azevedo, 52', '2025-07-19 13:27:46', 7, 'cliente', NULL, NULL, NULL),
(8, 'ricardo marcio', 'ricardomarciom@gmail.com', '$2y$10$MQrq9mwBkx9IDtLdt3EPCOc/YVFCp8sogeMviilalztmT7Pu/sFZu', '(32) 99961-2303', 'José Pedro Azevedo, 52', '2025-07-19 13:29:11', 7, 'cliente', NULL, NULL, NULL),
(10, 'ricardo mamede', 'ricardomarciom2@gmail.com', '$2y$10$8k7iP4xhWU4zhVr8ml13iuJU3tZlm038Dc1.8ePPJcap0CvDVhutm', '(32) 99961-2303', 'José Pedro Azevedo, 52', '2025-07-19 14:30:00', 7, 'prestador', NULL, NULL, NULL),
(12, 'pedro', 'rafael@gmail.com', '$2y$10$ZuBu0Wq1XmDl6kaexywDM.e/0RDyh28tvF9SpLNkLpbiUuLXPSJVm', '(32) 99961-2304', 'José Pedro Azevedo, 56', '2025-07-20 18:46:14', NULL, 'cliente', NULL, NULL, NULL),
(13, 'JOAO', 'rrr@gmail.com', '$2y$10$/xuKhGIJodVeABxRkXlc0uJa49m3WmrQm3CbPSHrvp.D/2nex6pvK', '(32) 99945-7687', 'Tv Montezuma 59 Senhor Dos Montes', '2025-07-20 18:47:48', NULL, 'cliente', NULL, NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id_administrador`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id_avaliacao`),
  ADD KEY `id_servico` (`id_servico`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nome_categoria` (`nome_categoria`);

--
-- Índices de tabela `cidades_senac_mg`
--
ALTER TABLE `cidades_senac_mg`
  ADD PRIMARY KEY (`id_cidade`),
  ADD UNIQUE KEY `nome_cidade` (`nome_cidade`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id_mensagem`),
  ADD KEY `id_remetente` (`id_remetente`),
  ADD KEY `id_destinatario` (`id_destinatario`),
  ADD KEY `id_orcamento` (`id_orcamento`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id_notificacao`),
  ADD KEY `id_usuario_destino` (`id_usuario_destino`);

--
-- Índices de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD PRIMARY KEY (`id_orcamento`),
  ADD KEY `id_servico` (`id_servico`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_profissional` (`id_profissional`);

--
-- Índices de tabela `profissionais`
--
ALTER TABLE `profissionais`
  ADD PRIMARY KEY (`id_profissional`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id_servico`),
  ADD KEY `id_profissional` (`id_profissional`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id_administrador` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id_avaliacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `cidades_senac_mg`
--
ALTER TABLE `cidades_senac_mg`
  MODIFY `id_cidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id_mensagem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id_notificacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `orcamentos`
--
ALTER TABLE `orcamentos`
  MODIFY `id_orcamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `profissionais`
--
ALTER TABLE `profissionais`
  MODIFY `id_profissional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id_servico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`id_servico`) REFERENCES `servicos` (`id_servico`),
  ADD CONSTRAINT `avaliacoes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`id_remetente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`id_destinatario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `mensagens_ibfk_3` FOREIGN KEY (`id_orcamento`) REFERENCES `orcamentos` (`id_orcamento`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `orcamentos`
--
ALTER TABLE `orcamentos`
  ADD CONSTRAINT `orcamentos_ibfk_1` FOREIGN KEY (`id_servico`) REFERENCES `servicos` (`id_servico`),
  ADD CONSTRAINT `orcamentos_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `orcamentos_ibfk_3` FOREIGN KEY (`id_profissional`) REFERENCES `profissionais` (`id_profissional`);

--
-- Restrições para tabelas `profissionais`
--
ALTER TABLE `profissionais`
  ADD CONSTRAINT `profissionais_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `servicos`
--
ALTER TABLE `servicos`
  ADD CONSTRAINT `servicos_ibfk_1` FOREIGN KEY (`id_profissional`) REFERENCES `profissionais` (`id_profissional`),
  ADD CONSTRAINT `servicos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

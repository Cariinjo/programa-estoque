# Sistema de Serviços Online SENAC

## Visão Geral

O Sistema de Serviços Online SENAC é uma plataforma completa que conecta alunos formados do SENAC com clientes que buscam serviços de qualidade. O sistema oferece um ambiente seguro e organizado para contratação de serviços, comunicação entre usuários e gerenciamento administrativo.

## Características Principais

### Front-end
- **Design Responsivo**: Interface adaptável para desktop, tablet e mobile
- **Layout Harmônico**: Design baseado na imagem de referência fornecida, com elementos bem distribuídos
- **Cores Modernas**: Paleta de cores roxo/lilás (#6c5ce7, #a29bfe) conforme solicitado
- **Animações Suaves**: Transições e efeitos visuais para melhor experiência do usuário
- **Busca Dinâmica**: Sistema de busca com sugestões em tempo real
- **Filtros Avançados**: Filtros por categoria, preço, avaliação e localização

### Back-end
- **PHP 8.1+**: Linguagem de programação principal
- **MySQL**: Banco de dados relacional
- **APIs RESTful**: Endpoints para comunicação front-end/back-end
- **Autenticação Segura**: Sistema de login com hash de senhas
- **Validação de Dados**: Sanitização e validação de todas as entradas

### Funcionalidades Principais

#### Para Clientes
- Cadastro e login
- Busca de serviços por categoria
- Visualização de perfis de profissionais
- Sistema de avaliações e comentários
- Chat em tempo real com profissionais
- Solicitação de orçamentos
- Histórico de serviços contratados

#### Para Profissionais
- Cadastro como prestador de serviços
- Criação e gerenciamento de serviços
- Recebimento de solicitações de orçamento
- Chat com clientes
- Gerenciamento de avaliações
- Dashboard com estatísticas

#### Para Administradores
- Painel administrativo completo
- Gerenciamento de usuários e profissionais
- Moderação de serviços e avaliações
- Sistema de notificações
- Relatórios e estatísticas
- Configurações do sistema

### Funcionalidades Avançadas

#### Sistema de Chat
- Mensagens em tempo real
- Histórico de conversas
- Notificações de novas mensagens
- Interface intuitiva e responsiva

#### Sistema de Notificações
- Notificações para novos orçamentos
- Alertas de conclusão de serviços
- Notificações de novas mensagens
- Sistema de leitura/não lida

#### Filtros Dinâmicos
- Filtros por categoria de serviço
- Ordenação por preço, avaliação e data
- Busca por localização
- Filtros combinados

## Estrutura do Projeto

```
servicos_senac/
├── admin/                  # Painel administrativo
│   ├── css/
│   │   └── admin.css      # Estilos do painel admin
│   ├── js/
│   │   └── admin.js       # JavaScript do painel admin
│   └── dashboard.php      # Dashboard principal do admin
├── api/                   # APIs do sistema
│   ├── chat-messages.php  # API para mensagens do chat
│   ├── notifications.php  # API para notificações
│   ├── search-suggestions.php # API para sugestões de busca
│   ├── send-message.php   # API para envio de mensagens
│   ├── submit-rating.php  # API para avaliações
│   └── mark-notification-read.php # API para marcar notificações como lidas
├── css/
│   └── style.css          # Estilos principais do front-end
├── js/
│   └── main.js            # JavaScript principal
├── images/                # Imagens do sistema
├── includes/
│   └── config.php         # Configurações do banco de dados
├── index.php              # Página inicial
├── login.php              # Página de login
├── cadastro.php           # Página de cadastro
├── chat.php               # Interface de chat
├── logout.php             # Script de logout
└── README.md              # Esta documentação
```

## Requisitos do Sistema

### Servidor
- **PHP**: 8.1 ou superior
- **MySQL**: 8.0 ou superior
- **Apache**: 2.4 ou superior
- **Extensões PHP**: mysqli, json, mbstring, session

### Cliente
- **Navegadores**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **JavaScript**: Habilitado
- **Resolução**: Mínima 320px (mobile) até 1920px+ (desktop)

## Instalação

### 1. Preparação do Ambiente

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 mysql-server php php-mysql php-json php-mbstring

# Iniciar serviços
sudo systemctl start apache2
sudo systemctl start mysql
```

### 2. Configuração do Banco de Dados

```bash
# Criar banco de dados
sudo mysql -e "CREATE DATABASE servicos_senac;"
sudo mysql -e "CREATE USER 'senac'@'localhost' IDENTIFIED BY 'senac123';"
sudo mysql -e "GRANT ALL PRIVILEGES ON servicos_senac.* TO 'senac'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Importar estrutura
mysql -u senac -psenac123 servicos_senac < servicos_senac.sql
```

### 3. Configuração do Apache

```bash
# Copiar arquivos para o diretório web
sudo cp -r servicos_senac /var/www/html/
sudo chown -R www-data:www-data /var/www/html/servicos_senac
sudo chmod -R 755 /var/www/html/servicos_senac
```

### 4. Configuração do Sistema

Edite o arquivo `includes/config.php` com suas configurações:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'servicos_senac');
define('DB_USER', 'senac');
define('DB_PASS', 'senac123');
define('SITE_URL', 'http://seu-dominio.com');
```

## Uso do Sistema

### Acesso Inicial

- **URL do Sistema**: `http://localhost/servicos_senac/`
- **Painel Admin**: `http://localhost/servicos_senac/admin/`

### Contas de Teste

#### Administrador
- **Email**: admin@servicos.com
- **Senha**: admin123

#### Usuários de Exemplo
- **Email**: ana.silva@example.com
- **Senha**: admin123
- **Tipo**: Profissional (Design Gráfico)

- **Email**: carlos.o@example.com
- **Senha**: admin123
- **Tipo**: Profissional (Desenvolvimento Web)

### Fluxo de Uso

#### Para Clientes
1. Acesse a página inicial
2. Cadastre-se como "Cliente"
3. Faça login no sistema
4. Navegue pelas categorias ou use a busca
5. Visualize perfis de profissionais
6. Solicite orçamentos
7. Converse via chat
8. Avalie os serviços

#### Para Profissionais
1. Cadastre-se como "Profissional"
2. Complete seu perfil
3. Cadastre seus serviços
4. Responda solicitações de orçamento
5. Converse com clientes via chat
6. Gerencie suas avaliações

#### Para Administradores
1. Acesse o painel administrativo
2. Gerencie usuários e profissionais
3. Modere serviços e avaliações
4. Envie notificações
5. Visualize relatórios

## Banco de Dados

### Principais Tabelas

#### usuarios
Armazena informações dos usuários (clientes e profissionais)
- `id_usuario` (PK)
- `nome`, `email`, `senha`
- `telefone`, `endereco`
- `data_cadastro`

#### profissionais
Informações específicas dos prestadores de serviços
- `id_profissional` (PK)
- `id_usuario` (FK)
- `cpf`, `area_atuacao`
- `descricao_perfil`
- `media_avaliacao`, `total_avaliacoes`

#### servicos
Serviços oferecidos pelos profissionais
- `id_servico` (PK)
- `id_profissional` (FK)
- `id_categoria` (FK)
- `titulo`, `descricao`, `preco`
- `media_avaliacao`, `total_avaliacoes`

#### orcamentos
Solicitações de orçamento e contratações
- `id_orcamento` (PK)
- `id_servico` (FK)
- `id_cliente` (FK)
- `status`, `valor_proposto`

#### mensagens
Sistema de chat entre usuários
- `id_mensagem` (PK)
- `id_remetente` (FK)
- `id_destinatario` (FK)
- `id_orcamento` (FK)
- `mensagem`, `lida`

#### notificacoes
Sistema de notificações
- `id_notificacao` (PK)
- `id_usuario_destino` (FK)
- `tipo_notificacao`, `mensagem`
- `lida`, `data_criacao`

## APIs Disponíveis

### Busca e Sugestões
- `GET /api/search-suggestions.php?q={termo}` - Sugestões de busca

### Chat
- `GET /api/chat-messages.php?chat_id={id}` - Carregar mensagens
- `POST /api/send-message.php` - Enviar mensagem

### Notificações
- `GET /api/notifications.php` - Listar notificações
- `POST /api/mark-notification-read.php` - Marcar como lida

### Avaliações
- `POST /api/submit-rating.php` - Enviar avaliação

## Segurança

### Medidas Implementadas
- **Hash de Senhas**: Todas as senhas são criptografadas com `password_hash()`
- **Sanitização**: Dados de entrada são sanitizados com `htmlspecialchars()`
- **Prepared Statements**: Proteção contra SQL Injection
- **Validação de Sessão**: Verificação de autenticação em páginas protegidas
- **CORS**: Configurado para APIs
- **Validação de Dados**: Validação client-side e server-side

### Recomendações de Produção
- Use HTTPS em produção
- Configure firewall adequadamente
- Mantenha PHP e MySQL atualizados
- Implemente rate limiting nas APIs
- Configure backup automático do banco
- Use senhas fortes para banco de dados

## Funcionalidades Inovadoras

### 1. Sistema de Chat em Tempo Real
- Interface moderna e intuitiva
- Indicadores de mensagem lida/não lida
- Histórico completo de conversas
- Notificações automáticas

### 2. Busca Inteligente
- Sugestões em tempo real
- Busca por múltiplos critérios
- Filtros combinados
- Resultados relevantes

### 3. Dashboard Administrativo
- Estatísticas em tempo real
- Gráficos interativos
- Ações rápidas
- Interface responsiva

### 4. Sistema de Avaliações
- Avaliações por estrelas
- Comentários detalhados
- Média automática
- Histórico completo

## Responsividade

O sistema foi desenvolvido com abordagem "Mobile First" e é totalmente responsivo:

### Breakpoints
- **Mobile**: 320px - 767px
- **Tablet**: 768px - 1023px
- **Desktop**: 1024px+

### Adaptações por Dispositivo
- **Mobile**: Menu hambúrguer, cards em coluna única, chat otimizado
- **Tablet**: Layout em duas colunas, navegação adaptada
- **Desktop**: Layout completo, sidebar fixa, múltiplas colunas

## Manutenção

### Logs
- Erros PHP são registrados no log do sistema
- Erros de banco são registrados separadamente
- JavaScript errors são capturados no console

### Backup
```bash
# Backup do banco de dados
mysqldump -u senac -psenac123 servicos_senac > backup_$(date +%Y%m%d).sql

# Backup dos arquivos
tar -czf backup_files_$(date +%Y%m%d).tar.gz /var/www/html/servicos_senac/
```

### Atualizações
1. Faça backup completo
2. Teste em ambiente de desenvolvimento
3. Aplique atualizações em produção
4. Verifique funcionalidades críticas

## Suporte e Contato

Para suporte técnico ou dúvidas sobre o sistema:
- **Email**: suporte@servicossenac.com
- **Documentação**: Consulte este README.md
- **Issues**: Reporte problemas através do sistema de tickets

## Licença

Este sistema foi desenvolvido especificamente para o SENAC e está protegido por direitos autorais. Uso restrito conforme acordo de desenvolvimento.

---

**Desenvolvido com ❤️ para conectar talentos e oportunidades**


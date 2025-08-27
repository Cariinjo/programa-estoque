# Sistema de Serviços Online SENAC - Documentação Completa

## 📋 Visão Geral

O Sistema de Serviços Online SENAC é uma plataforma completa que conecta profissionais formados pelo SENAC com clientes que buscam serviços de qualidade. O sistema oferece uma experiência moderna, responsiva e intuitiva para todos os tipos de usuários.

## 🎯 Objetivos do Sistema

- **Conectar Talentos**: Facilitar a conexão entre profissionais qualificados e clientes
- **Qualidade Garantida**: Assegurar serviços de alta qualidade através de profissionais formados pelo SENAC
- **Experiência Moderna**: Oferecer interface responsiva e intuitiva
- **Gestão Completa**: Fornecer ferramentas completas de gestão para todos os usuários

## 👥 Tipos de Usuários

### 1. **Clientes**
- Pessoas que buscam contratar serviços
- Acesso ao dashboard personalizado
- Sistema de busca e filtros avançados
- Chat direto com profissionais
- Histórico de serviços contratados

### 2. **Profissionais**
- Alunos formados pelo SENAC que prestam serviços
- Dashboard profissional com estatísticas
- Gerenciamento de serviços e orçamentos
- Sistema de notificações
- Controle de avaliações

### 3. **Administradores**
- Gestão completa da plataforma
- Painel administrativo avançado
- Controle de usuários e conteúdo
- Relatórios e estatísticas
- Moderação de avaliações

## 🛠️ Tecnologias Utilizadas

### **Frontend**
- **HTML5**: Estrutura semântica moderna
- **CSS3**: Estilos responsivos com Grid e Flexbox
- **JavaScript (ES6+)**: Interatividade e funcionalidades dinâmicas
- **Font Awesome**: Ícones profissionais
- **Design Responsivo**: Compatível com desktop, tablet e mobile

### **Backend**
- **PHP 8.1+**: Linguagem de programação principal
- **MySQL 8.0**: Banco de dados relacional
- **Apache 2.4**: Servidor web
- **APIs RESTful**: Comunicação entre frontend e backend

### **Segurança**
- **Prepared Statements**: Proteção contra SQL Injection
- **Hash de Senhas**: Criptografia segura com password_hash()
- **Sanitização de Dados**: Validação e limpeza de inputs
- **Sessões Seguras**: Gerenciamento seguro de autenticação

## 📁 Estrutura do Projeto

```
servicos_senac/
├── admin/                      # Painel Administrativo
│   ├── css/
│   │   └── admin.css          # Estilos do painel admin
│   ├── js/
│   │   └── admin.js           # JavaScript do painel admin
│   └── dashboard.php          # Dashboard administrativo
├── api/                       # APIs do Sistema
│   ├── chat-messages.php      # API para mensagens do chat
│   ├── mark-notification-read.php # API para notificações
│   ├── notifications.php      # API de notificações
│   ├── search-suggestions.php # API de sugestões de busca
│   ├── send-message.php       # API para envio de mensagens
│   ├── submit-rating.php      # API para avaliações
│   └── update-quote-status.php # API para status de orçamentos
├── css/
│   └── style.css              # Estilos principais
├── includes/
│   ├── config.php             # Configurações do banco
│   ├── footer.php             # Rodapé do site
│   └── header.php             # Cabeçalho do site
├── js/
│   └── main.js                # JavaScript principal
├── images/                    # Pasta para imagens
├── cadastro.php               # Página de cadastro
├── chat.php                   # Sistema de chat
├── como-funciona.php          # Página explicativa
├── contato.php                # Página de contato
├── dashboard.php              # Dashboard do cliente
├── dashboard-profissional.php # Dashboard do profissional
├── index.php                  # Página inicial
├── login.php                  # Página de login
├── logout.php                 # Script de logout
├── profissionais.php          # Listagem de profissionais
├── servicos.php               # Listagem de serviços
└── README.md                  # Documentação básica
```

## 🗄️ Estrutura do Banco de Dados

### **Tabelas Principais**

#### **usuarios**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nome` (VARCHAR(100), NOT NULL)
- `email` (VARCHAR(100), UNIQUE, NOT NULL)
- `senha` (VARCHAR(255), NOT NULL)
- `telefone` (VARCHAR(20))
- `tipo` (ENUM: 'cliente', 'profissional', 'admin')
- `data_cadastro` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `ativo` (BOOLEAN, DEFAULT TRUE)

#### **categorias**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nome` (VARCHAR(100), NOT NULL)
- `descricao` (TEXT)
- `icone` (VARCHAR(50))
- `cor` (VARCHAR(7))
- `ativa` (BOOLEAN, DEFAULT TRUE)

#### **servicos**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `profissional_id` (INT, FOREIGN KEY)
- `categoria_id` (INT, FOREIGN KEY)
- `titulo` (VARCHAR(200), NOT NULL)
- `descricao` (TEXT, NOT NULL)
- `preco` (DECIMAL(10,2), NOT NULL)
- `data_criacao` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `ativo` (BOOLEAN, DEFAULT TRUE)

#### **avaliacoes**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `servico_id` (INT, FOREIGN KEY)
- `cliente_id` (INT, FOREIGN KEY)
- `nota` (INT, CHECK: 1-5)
- `comentario` (TEXT)
- `data_avaliacao` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

#### **orcamentos**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `servico_id` (INT, FOREIGN KEY)
- `cliente_id` (INT, FOREIGN KEY)
- `descricao` (TEXT, NOT NULL)
- `valor_proposto` (DECIMAL(10,2))
- `status` (ENUM: 'pendente', 'aceito', 'recusado', 'concluido')
- `data_criacao` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

#### **mensagens**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `remetente_id` (INT, FOREIGN KEY)
- `destinatario_id` (INT, FOREIGN KEY)
- `orcamento_id` (INT, FOREIGN KEY, NULLABLE)
- `mensagem` (TEXT, NOT NULL)
- `data_envio` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- `lida` (BOOLEAN, DEFAULT FALSE)

#### **notificacoes**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `usuario_id` (INT, FOREIGN KEY)
- `tipo` (VARCHAR(50), NOT NULL)
- `titulo` (VARCHAR(200), NOT NULL)
- `mensagem` (TEXT, NOT NULL)
- `lida` (BOOLEAN, DEFAULT FALSE)
- `data_criacao` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

#### **contatos**
- `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `nome` (VARCHAR(100), NOT NULL)
- `email` (VARCHAR(100), NOT NULL)
- `telefone` (VARCHAR(20))
- `assunto` (VARCHAR(200), NOT NULL)
- `mensagem` (TEXT, NOT NULL)
- `data_envio` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

## 🚀 Funcionalidades Implementadas

### **Sistema de Autenticação**
- ✅ Cadastro de usuários (cliente/profissional)
- ✅ Login seguro com validação
- ✅ Logout com limpeza de sessão
- ✅ Redirecionamento automático baseado no tipo de usuário

### **Dashboard do Cliente**
- ✅ Visão geral de serviços contratados
- ✅ Histórico de orçamentos enviados
- ✅ Sistema de notificações
- ✅ Acesso rápido a funcionalidades principais
- ✅ Estatísticas personalizadas

### **Dashboard do Profissional**
- ✅ Gerenciamento de serviços cadastrados
- ✅ Controle de orçamentos recebidos
- ✅ Estatísticas de desempenho
- ✅ Sistema de notificações
- ✅ Gestão de perfil profissional

### **Sistema de Busca e Filtros**
- ✅ Busca inteligente de serviços
- ✅ Filtros por categoria, preço e avaliação
- ✅ Ordenação por relevância, preço e avaliação
- ✅ Sugestões de busca em tempo real
- ✅ Resultados dinâmicos do banco de dados

### **Sistema de Chat e Mensagens**
- ✅ Chat em tempo real entre usuários
- ✅ Histórico de conversas
- ✅ Indicadores de mensagens não lidas
- ✅ Interface moderna e responsiva
- ✅ Integração com sistema de orçamentos

### **Sistema de Notificações**
- ✅ Notificações em tempo real
- ✅ Diferentes tipos de notificação
- ✅ Marcação de leitura
- ✅ Interface visual atrativa
- ✅ Integração com todas as funcionalidades

### **Sistema de Avaliações**
- ✅ Avaliação por estrelas (1-5)
- ✅ Comentários detalhados
- ✅ Cálculo automático de médias
- ✅ Exibição em perfis e serviços
- ✅ Validação de avaliações

### **Painel Administrativo**
- ✅ Dashboard com estatísticas gerais
- ✅ Gerenciamento de usuários
- ✅ Controle de serviços e categorias
- ✅ Moderação de avaliações
- ✅ Relatórios detalhados

### **Páginas Informativas**
- ✅ Página inicial com design moderno
- ✅ Listagem de serviços com filtros
- ✅ Listagem de profissionais
- ✅ Página "Como Funciona" explicativa
- ✅ Página de contato com formulário

### **Design Responsivo**
- ✅ Layout adaptável para todos os dispositivos
- ✅ Interface moderna com cores do SENAC
- ✅ Animações suaves e transições
- ✅ Ícones profissionais (Font Awesome)
- ✅ Tipografia legível e hierárquica

## 🔧 Instalação e Configuração

### **Pré-requisitos**
- Apache 2.4+
- PHP 8.1+
- MySQL 8.0+
- Extensões PHP: PDO, PDO_MySQL, mbstring

### **Passos de Instalação**

1. **Clone ou extraia o projeto**
   ```bash
   # Extrair o ZIP ou clonar o repositório
   unzip sistema_servicos_senac_completo.zip
   cd servicos_senac
   ```

2. **Configure o servidor web**
   ```bash
   # Copie os arquivos para o diretório web
   sudo cp -r * /var/www/html/servicos_senac/
   
   # Configure as permissões
   sudo chown -R www-data:www-data /var/www/html/servicos_senac/
   sudo chmod -R 755 /var/www/html/servicos_senac/
   ```

3. **Configure o banco de dados**
   ```bash
   # Crie o banco de dados
   mysql -u root -p -e "CREATE DATABASE servicos_senac CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Crie o usuário
   mysql -u root -p -e "CREATE USER 'senac'@'localhost' IDENTIFIED BY 'senac123';"
   mysql -u root -p -e "GRANT ALL PRIVILEGES ON servicos_senac.* TO 'senac'@'localhost';"
   mysql -u root -p -e "FLUSH PRIVILEGES;"
   
   # Importe a estrutura
   mysql -u senac -psenac123 servicos_senac < servicos_senac.sql
   ```

4. **Configure as credenciais**
   ```php
   // Edite o arquivo includes/config.php
   $host = 'localhost';
   $dbname = 'servicos_senac';
   $username = 'senac';
   $password = 'senac123';
   ```

5. **Acesse o sistema**
   - URL: `http://localhost/servicos_senac/`
   - Admin: `admin@servicos.com` / `admin123`

## 👤 Credenciais de Teste

### **Administrador**
- **Email**: admin@servicos.com
- **Senha**: admin123

### **Profissionais**
- **Ana Silva** (Design): ana.silva@example.com / admin123
- **Carlos Oliveira** (Desenvolvimento): carlos.o@example.com / admin123
- **Rafael Mendes** (Fotografia): rafael.m@example.com / admin123
- **Juliana Santos** (Marketing): juliana.s@example.com / admin123

### **Clientes**
- **Maria Santos**: maria.santos@example.com / admin123
- **João Silva**: joao.silva@example.com / admin123

## 🎨 Paleta de Cores

- **Primária**: #6c5ce7 (Roxo SENAC)
- **Secundária**: #a29bfe (Roxo Claro)
- **Sucesso**: #00b894 (Verde)
- **Aviso**: #fdcb6e (Amarelo)
- **Erro**: #e17055 (Vermelho)
- **Texto**: #2d3436 (Cinza Escuro)
- **Texto Secundário**: #636e72 (Cinza Médio)
- **Fundo**: #f8f9fa (Cinza Claro)

## 📱 Responsividade

O sistema foi desenvolvido com abordagem **mobile-first** e é totalmente responsivo:

- **Desktop**: 1200px+
- **Tablet**: 768px - 1199px
- **Mobile**: até 767px

### **Breakpoints CSS**
```css
/* Mobile First */
@media (min-width: 768px) { /* Tablet */ }
@media (min-width: 1200px) { /* Desktop */ }
```

## 🔒 Segurança Implementada

### **Autenticação**
- Hash seguro de senhas com `password_hash()`
- Validação de sessões
- Proteção contra ataques de força bruta

### **Banco de Dados**
- Prepared Statements para todas as consultas
- Proteção contra SQL Injection
- Validação de tipos de dados

### **Entrada de Dados**
- Sanitização com `htmlspecialchars()`
- Validação de emails e campos obrigatórios
- Proteção contra XSS

### **Sessões**
- Regeneração de ID de sessão
- Timeout automático
- Limpeza segura no logout

## 🚀 Funcionalidades Futuras Sugeridas

### **Fase 1 (Curto Prazo)**
- Sistema de Portfólio Visual para profissionais
- Agendamento de serviços com calendário
- Chatbot para atendimento automático
- Sistema de indicações com recompensas

### **Fase 2 (Médio Prazo)**
- Gamificação com pontos e badges
- Carteira digital integrada
- Dashboard analítico avançado
- Aplicativo mobile nativo

### **Fase 3 (Longo Prazo)**
- Sistema de mentoria SENAC
- IA para recomendações personalizadas
- Programa de qualidade certificado
- Iniciativas de sustentabilidade

## 🐛 Solução de Problemas

### **Erro de Conexão com Banco**
```
Verifique:
1. Credenciais no config.php
2. Serviço MySQL ativo
3. Permissões do usuário
4. Nome do banco correto
```

### **Páginas em Branco**
```
Verifique:
1. Logs de erro do PHP
2. Permissões de arquivo
3. Sintaxe do código
4. Extensões PHP instaladas
```

### **CSS/JS não Carregando**
```
Verifique:
1. Caminhos dos arquivos
2. Permissões de leitura
3. Cache do navegador
4. Configuração do servidor
```

## 📞 Suporte

Para suporte técnico ou dúvidas sobre o sistema:

- **Email**: contato@servicossenac.com.br
- **Telefone**: (11) 1234-5678
- **Documentação**: README.md
- **Código Fonte**: Comentado e organizado

## 📄 Licença

Este projeto foi desenvolvido para fins educacionais e de demonstração. Todos os direitos reservados ao SENAC.

---

**Desenvolvido com ❤️ para conectar talentos e oportunidades**


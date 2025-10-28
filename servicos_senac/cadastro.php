<?php
require_once 'includes/config.php';

$error = '';
$success = '';

// Buscar categorias e cidades para os selects
try {
    // Buscar Categorias
    $stmt_cat = $pdo->query("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // Buscar Cidades (NOVO)
    $stmt_cid = $pdo->query("SELECT id_cidade, nome_cidade, uf FROM cidades_senac_mg ORDER BY nome_cidade");
    $cidades = $stmt_cid->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar dados iniciais: " . $e->getMessage());
    $categorias = [];
    $cidades = []; // Garante que a variável exista
    $error = "Erro ao carregar dados necessários para o cadastro."; // Informa erro ao carregar
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? ''); // Usa ?? '' para evitar erro se não existir
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $telefone = sanitize($_POST['telefone'] ?? '');
    $endereco = sanitize($_POST['endereco'] ?? ''); // Endereço geral (pode remover se não usar mais)
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $cidade_id_usuario = (int)($_POST['cidade_id_usuario'] ?? 0); // Cidade para TODOS os usuários (NOVO)

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario) || $cidade_id_usuario <= 0) { // Adicionada validação de cidade
        $error = 'Por favor, preencha todos os campos obrigatórios, incluindo a cidade.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Adicionada validação de email
         $error = 'Formato de email inválido.';
    } else {
        try {
            // Verificar se email já existe
            $stmt_check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt_check->execute([$email]);
            if ($stmt_check->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                $pdo->beginTransaction();

                // Inserir usuário na tabela 'usuarios'
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, telefone, endereco_completo, tipo_usuario, cidade_id, data_cadastro) -- Usando endereco_completo e cidade_id
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) -- Adicionado cidade_id e data_cadastro
                ");
                // Passa $endereco para endereco_completo e $cidade_id_usuario para cidade_id
                $stmt_user->execute([$nome, $email, $senhaHash, $telefone, $endereco, $tipo_usuario, $cidade_id_usuario]);
                $userId = $pdo->lastInsertId();

                // Se for profissional, inserir na tabela profissionais
                if ($tipo_usuario === 'prestador') {
                    $cpf = sanitize($_POST['cpf'] ?? '');
                    $id_categoria = (int)($_POST['id_categoria'] ?? 0); // Convertido para int
                    $descricao_perfil = sanitize($_POST['descricao_perfil'] ?? '');
                    $cidade_id_prestador = $cidade_id_usuario; // Usa a mesma cidade selecionada no cadastro geral

                    // Validação específica para profissional
                    if (empty($cpf) || $id_categoria <= 0 || $cidade_id_prestador <= 0) { // Adicionada validação de cidade aqui tbm
                        throw new Exception('CPF, categoria e cidade são obrigatórios para profissionais.');
                    }

                    // Busca nome da categoria para usar como 'area_atuacao' (se ainda precisar)
                    // Ou pode remover 'area_atuacao' se a categoria for suficiente
                    $stmt_cat_name = $pdo->prepare("SELECT nome_categoria FROM categorias WHERE id_categoria = ?");
                    $stmt_cat_name->execute([$id_categoria]);
                    $categoria_data = $stmt_cat_name->fetch(PDO::FETCH_ASSOC);
                    $area_atuacao = $categoria_data ? $categoria_data['nome_categoria'] : 'Não especificado'; // Área de atuação baseada na categoria

                    // Insere na tabela 'profissionais'
                    $stmt_prof = $pdo->prepare("
                        INSERT INTO profissionais (id_usuario, cpf, area_atuacao, descricao_perfil, cidade_id, data_cadastro) -- Adicionado cidade_id e data_cadastro
                        VALUES (?, ?, ?, ?, ?, NOW()) -- Adicionado placeholder para cidade_id e data_cadastro
                    ");
                    $stmt_prof->execute([$userId, $cpf, $area_atuacao, $descricao_perfil, $cidade_id_prestador]); // Passa a cidade_id
                }

                $pdo->commit(); // Confirma as inserções
                $success = 'Cadastro realizado com sucesso! Você pode fazer login agora.';
                // Limpar os dados do POST para não repopular o form após sucesso (opcional)
                $_POST = [];

            }
        } catch (Exception $e) { // Captura exceções gerais (validações, etc)
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro no cadastro (Exception): " . $e->getMessage());
            $error = $e->getMessage(); // Mostra a mensagem da exceção
        } catch (PDOException $e) { // Captura erros específicos do banco
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro no cadastro (PDO): " . $e->getMessage());
            // Mostra mensagem genérica para o usuário em caso de erro de BD
            $error = 'Erro interno do servidor ao processar o cadastro. Tente novamente mais tarde.';
            // Em desenvolvimento, pode ser útil mostrar $e->getMessage()
            // $error = 'Erro DB: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Cole TODO o seu CSS aqui */
        .register-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); padding: 2rem; }
        .register-form { background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); width: 100%; max-width: 600px; }
        .register-header { text-align: center; margin-bottom: 2rem; }
        .register-header h1 { color: #2d3436; margin-bottom: 0.5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group.full-width { grid-column: 1 / -1; } /* Classe para ocupar largura total */
        .form-group label { display: block; margin-bottom: 0.5rem; color: #2d3436; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 10px; font-size: 1rem; transition: border-color 0.3s ease; box-sizing: border-box; } /* Adicionado box-sizing */
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #6c5ce7; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .user-type-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .user-type-option { padding: 1.5rem; border: 2px solid #ddd; border-radius: 10px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .user-type-option:hover { border-color: #6c5ce7; }
        .user-type-option.active { border-color: #6c5ce7; background: #f8f9ff; }
        .user-type-option input[type="radio"] { display: none; }
        .professional-fields { display: none; background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-top: 1.5rem; border: 1px solid #eee; } /* Adicionado margin-top e border */
        .professional-fields.active { display: block; }
        .error-message { background: #ff7675; color: white; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
        .success-message { background: #00b894; color: white; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
        .register-btn { width: 100%; padding: 1rem; background: linear-gradient(45deg, #6c5ce7, #a29bfe); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: transform 0.3s ease; }
        .register-btn:hover { transform: translateY(-2px); }
        .register-links { text-align: center; margin-top: 2rem; }
        .register-links a { color: #6c5ce7; text-decoration: none; margin: 0 1rem; }
        .register-links a:hover { text-decoration: underline; }
        .required-mark { color: #ff7675; margin-left: 2px;} /* Estilo para asterisco */
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .user-type-selector { grid-template-columns: 1fr; }
            .register-form { padding: 2rem; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <form class="register-form" method="POST">
            <div class="register-header">
                <h1><i class="fas fa-user-plus"></i> Criar Conta</h1>
                <p>Junte-se à nossa plataforma</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
                 <div class="register-links">
                     <a href="login.php">Ir para Login</a>
                 </div>
                 <script>
                    // Opcional: esconder o form após sucesso, ou redirecionar
                    // document.querySelector('.register-form').style.display = 'none';
                    // window.location.href = 'login.php'; // Ou redireciona direto
                 </script>
            <?php else: // Só mostra o formulário se não houver sucesso ?>

            <div class="user-type-selector">
                <label class="user-type-option <?= (!isset($_POST['tipo_usuario']) || $_POST['tipo_usuario'] === 'cliente') ? 'active' : '' ?>">
                    <input type="radio" name="tipo_usuario" value="cliente" <?= (!isset($_POST['tipo_usuario']) || $_POST['tipo_usuario'] === 'cliente') ? 'checked' : '' ?>>
                    <i class="fas fa-user" style="font-size: 2rem; color: #6c5ce7; margin-bottom: 0.5rem;"></i>
                    <h3>Sou Cliente</h3>
                    <p>Quero contratar serviços</p>
                </label>

                <label class="user-type-option <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'prestador') ? 'active' : '' ?>">
                    <input type="radio" name="tipo_usuario" value="prestador" <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'prestador') ? 'checked' : '' ?>>
                    <i class="fas fa-briefcase" style="font-size: 2rem; color: #6c5ce7; margin-bottom: 0.5rem;"></i>
                    <h3>Sou Profissional</h3>
                    <p>Quero oferecer serviços</p>
                </label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo <span class="required-mark">*</span></label>
                    <input type="text" id="nome" name="nome" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required-mark">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="senha">Senha <span class="required-mark">*</span></label>
                    <input type="password" id="senha" name="senha" required minlength="6" placeholder="Mínimo 6 caracteres">
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha <span class="required-mark">*</span></label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone / WhatsApp</label>
                    <input type="tel" id="telefone" name="telefone" class="phone-mask" value="<?= isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : '' ?>" placeholder="(XX) XXXXX-XXXX">
                </div>

                 <div class="form-group">
                    <label for="cidade_id_usuario">Sua Cidade <span class="required-mark">*</span></label>
                    <select id="cidade_id_usuario" name="cidade_id_usuario" required>
                        <option value="">Selecione sua cidade</option>
                        <?php if (!empty($cidades)): ?>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?= $cidade['id_cidade'] ?>"
                                        <?= (isset($_POST['cidade_id_usuario']) && $_POST['cidade_id_usuario'] == $cidade['id_cidade']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cidade['nome_cidade']) ?> - <?= htmlspecialchars($cidade['uf']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Erro ao carregar cidades</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                 <label for="endereco">Endereço Completo (Opcional)</label>
                 <input type="text" id="endereco" name="endereco" value="<?= isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : '' ?>" placeholder="Rua, Número, Bairro...">
            </div>


            <div class="professional-fields <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'prestador') ? 'active' : '' ?>">
                <h3 style="margin-bottom: 1.5rem; color: #2d3436; border-top: 1px solid #eee; padding-top: 1.5rem;">Informações Profissionais</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cpf">CPF <span class="required-mark">*</span></label>
                        <input type="text" id="cpf" name="cpf" class="cpf-mask" value="<?= isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : '' ?>" placeholder="000.000.000-00">
                    </div>

                    <div class="form-group">
                        <label for="id_categoria">Principal Categoria de Atuação <span class="required-mark">*</span></label>
                        <select id="id_categoria" name="id_categoria">
                            <option value="">Selecione uma categoria</option>
                            <?php if (!empty($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id_categoria'] ?>"
                                            <?= (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nome_categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                 <option value="" disabled>Erro ao carregar categorias</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="descricao_perfil">Descrição do Perfil (Opcional)</label>
                    <textarea id="descricao_perfil" name="descricao_perfil" placeholder="Conte um pouco sobre sua experiência, especialidades, diferenciais..."><?= isset($_POST['descricao_perfil']) ? htmlspecialchars($_POST['descricao_perfil']) : '' ?></textarea>
                </div>
            </div>

            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i> Criar Conta
            </button>

            <div class="register-links">
                <a href="login.php">Já tenho uma conta</a>
            </div>

            <div class="register-links" style="margin-top: 1rem;">
                <a href="index.php">← Voltar ao início</a>
            </div>
          <?php endif; // Fim do else que esconde o form após sucesso ?>
        </form>
    </div>

    <script>
        // Lógica para mostrar/esconder campos do profissional
        const userTypeRadios = document.querySelectorAll('input[name="tipo_usuario"]');
        const professionalFieldsDiv = document.querySelector('.professional-fields');
        const cpfInput = document.getElementById('cpf');
        const categorySelect = document.getElementById('id_categoria');
        const citySelectUser = document.getElementById('cidade_id_usuario'); // Select de cidade principal

        function toggleProfessionalFields() {
            const selectedType = document.querySelector('input[name="tipo_usuario"]:checked').value;
            if (selectedType === 'prestador') {
                professionalFieldsDiv.classList.add('active');
                cpfInput.required = true;
                categorySelect.required = true;
                // A cidade (cidade_id_usuario) já é required para todos
            } else {
                professionalFieldsDiv.classList.remove('active');
                cpfInput.required = false;
                categorySelect.required = false;
            }
            // Atualiza a aparência dos botões de seleção
             document.querySelectorAll('.user-type-option').forEach(label => {
                const radio = label.querySelector('input[type="radio"]');
                if (radio.value === selectedType) {
                    label.classList.add('active');
                } else {
                    label.classList.remove('active');
                }
            });
        }

        userTypeRadios.forEach(radio => {
            radio.addEventListener('change', toggleProfessionalFields);
        });

         // Adiciona listeners aos labels também para melhor usabilidade
         document.querySelectorAll('.user-type-option').forEach(label => {
            label.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    // Dispara o evento change manualmente para garantir que a lógica rode
                    const event = new Event('change');
                    radio.dispatchEvent(event);
                }
            });
        });

        // Chama a função ao carregar a página para garantir o estado correto
        document.addEventListener('DOMContentLoaded', toggleProfessionalFields);


        // Validação de confirmação de senha
        const senhaInput = document.getElementById('senha');
        const confirmarSenhaInput = document.getElementById('confirmar_senha');

        function validatePasswordMatch() {
            if (senhaInput.value !== confirmarSenhaInput.value) {
                confirmarSenhaInput.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenhaInput.setCustomValidity(''); // Limpa o erro
            }
        }

        senhaInput.addEventListener('input', validatePasswordMatch);
        confirmarSenhaInput.addEventListener('input', validatePasswordMatch);

        // --- MÁSCARAS (Exemplo usando jQuery Mask - precisa incluir jQuery e a lib) ---
        // Se não for usar jQuery Mask, remova ou adapte esta parte
        /*
        $(document).ready(function(){
             $('.phone-mask').mask('(00) 00000-0000');
             $('.cpf-mask').mask('000.000.000-00', {reverse: true});
        });
        */
         // Exemplo de máscara SIMPLES SEM jQuery (apenas para CPF como exemplo)
         const cpfField = document.querySelector('.cpf-mask');
         if(cpfField){
             cpfField.addEventListener('input', function (e) {
                 let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não for dígito
                 value = value.replace(/(\d{3})(\d)/, '$1.$2'); // Coloca ponto após 3 dígitos
                 value = value.replace(/(\d{3})(\d)/, '$1.$2'); // Coloca ponto após outros 3 dígitos
                 value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); // Coloca hífen antes dos últimos 2 dígitos
                 e.target.value = value.slice(0, 14); // Limita o tamanho
             });
         }
         // Adicione máscara similar para telefone se não usar lib externa

    </script>
    </body>
</html>
<?php
require_once 'includes/config.php';

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $preco = (float)($_POST['preco'] ?? 0);
        $prazo_execucao = (int)($_POST['prazo_execucao'] ?? 0);
        $tipo_preco = $_POST['tipo_preco'] ?? 'fixo';
        $disponibilidade = $_POST['disponibilidade'] ?? 'disponivel';
        
        // Validações
        if (empty($titulo) || empty($descricao)) {
            throw new Exception('Título e descrição são obrigatórios');
        }
        
        if ($categoria_id <= 0) {
            throw new Exception('Selecione uma categoria');
        }
        
        if ($preco <= 0) {
            throw new Exception('Preço deve ser maior que zero');
        }
        
        if ($prazo_execucao <= 0) {
            throw new Exception('Prazo de execução deve ser maior que zero');
        }
        
        // Buscar ID do profissional
        $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
        $stmt->execute([$user_id]);
        $prestador_id = $stmt->fetchColumn();
        
        if (!$prestador_id) {
            throw new Exception('Prestador não encontrado');
        }
        
        $pdo->beginTransaction();
        
        // Inserir serviço
        $stmt = $pdo->prepare("
            INSERT INTO servicos (
                id_profissional, id_categoria, titulo, descricao, preco, 
                prazo_execucao, tipo_preco, status_servico, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', NOW())
        ");
        $stmt->execute([
            $prestador_id, $categoria_id, $titulo, $descricao, $preco, 
            $prazo_execucao, $tipo_preco
        ]);
        
        $servico_id = $pdo->lastInsertId();
        
        // Processar upload de imagens se enviadas
        if (isset($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
            $upload_dir = 'uploads/servicos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_files = 5;
            
            for ($i = 0; $i < min(count($_FILES['imagens']['name']), $max_files); $i++) {
                if ($_FILES['imagens']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_extension = strtolower(pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION));
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = 'servico_' . $servico_id . '_' . ($i + 1) . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['imagens']['tmp_name'][$i], $upload_path)) {
                            $stmt = $pdo->prepare("
                                INSERT INTO servico_imagens (id_servico, caminho_imagem, ordem) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$servico_id, $upload_path, $i + 1]);
                        }
                    }
                }
            }
        }
        
        $pdo->commit();
        
        $success_message = 'Serviço cadastrado com sucesso!';
        
        // Redirecionar após 2 segundos
        header("refresh:2;url=meus-servicos.php");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

try {
    // Buscar categorias
    $stmt = $pdo->prepare("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
    $error_message = 'Erro ao carregar categorias';
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Serviço - Prestador</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cadastro-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .page-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #f0f0f0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .price-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .price-prefix {
            background: #f8f9fa;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .price-input-group input {
            border-radius: 0 10px 10px 0;
            border-left: none;
        }
        
        .tipo-preco-options {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
        }
        
        .image-upload {
            border: 2px dashed #e9ecef;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: border-color 0.3s ease;
        }
        
        .image-upload:hover {
            border-color: #3498db;
        }
        
        .image-upload.dragover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }
        
        .upload-text {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .file-input {
            display: none;
        }
        
        .btn-upload {
            background: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .btn-upload:hover {
            background: #2980b9;
        }
        
        .preview-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e9ecef;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f8f9fa;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .tipo-preco-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; ?>
    
    <div class="cadastro-container">
        <!-- Header da Página -->
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Cadastrar Novo Serviço</h1>
            <p>Adicione um novo serviço ao seu portfólio</p>
        </div>
        
        <div class="form-card">
            <!-- Alertas -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <!-- Informações Básicas -->
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label for="titulo">Título do Serviço <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" required maxlength="100" 
                               placeholder="Ex: Instalação de Ar Condicionado">
                        <div class="help-text">Seja claro e específico sobre o que você oferece</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria_id">Categoria <span class="required">*</span></label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id_categoria'] ?>">
                                    <?= htmlspecialchars($categoria['nome_categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="preco">Preço <span class="required">*</span></label>
                        <div class="price-input-group">
                            <span class="price-prefix">R$</span>
                            <input type="number" id="preco" name="preco" step="0.01" min="0.01" required 
                                   placeholder="0,00">
                        </div>
                        <div class="tipo-preco-options">
                            <div class="radio-option">
                                <input type="radio" id="fixo" name="tipo_preco" value="fixo" checked>
                                <label for="fixo">Preço fixo</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="negociavel" name="tipo_preco" value="negociavel">
                                <label for="negociavel">Negociável</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="prazo_execucao">Prazo de Execução (dias) <span class="required">*</span></label>
                        <input type="number" id="prazo_execucao" name="prazo_execucao" min="1" required 
                               placeholder="Ex: 3">
                        <div class="help-text">Tempo estimado para conclusão do serviço</div>
                    </div>
                </div>
                
                <!-- Descrição -->
                <div class="form-group form-group-full">
                    <label for="descricao">Descrição Detalhada <span class="required">*</span></label>
                    <textarea id="descricao" name="descricao" required maxlength="1000" 
                              placeholder="Descreva detalhadamente o que está incluído no serviço, materiais necessários, processo de execução, etc."></textarea>
                    <div class="help-text">Seja específico sobre o que está incluído e o que não está</div>
                </div>
                
                <!-- Upload de Imagens -->
                <div class="form-group form-group-full">
                    <label>Imagens do Serviço (opcional)</label>
                    <div class="image-upload" id="imageUpload">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">
                            Arraste e solte imagens aqui ou clique para selecionar
                        </div>
                        <button type="button" class="btn-upload" onclick="document.getElementById('imagens').click()">
                            <i class="fas fa-plus"></i> Selecionar Imagens
                        </button>
                        <input type="file" id="imagens" name="imagens[]" multiple accept="image/*" class="file-input">
                        <div class="help-text">Máximo 5 imagens. Formatos: JPG, PNG, GIF</div>
                    </div>
                    <div class="preview-images" id="previewImages"></div>
                </div>
                
                <!-- Ações -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cadastrar Serviço
                    </button>
                    <a href="meus-servicos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Preview de imagens
        const imageUpload = document.getElementById('imageUpload');
        const imageInput = document.getElementById('imagens');
        const previewContainer = document.getElementById('previewImages');
        let selectedFiles = [];
        
        // Drag and drop
        imageUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            imageUpload.classList.add('dragover');
        });
        
        imageUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            imageUpload.classList.remove('dragover');
        });
        
        imageUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            imageUpload.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
        
        imageInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        function handleFiles(files) {
            const maxFiles = 5;
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            
            files.forEach(file => {
                if (selectedFiles.length >= maxFiles) {
                    alert('Máximo de 5 imagens permitidas');
                    return;
                }
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Formato não permitido: ' + file.name);
                    return;
                }
                
                selectedFiles.push(file);
                createPreview(file, selectedFiles.length - 1);
            });
            
            updateFileInput();
        }
        
        function createPreview(file, index) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-image" onclick="removeImage(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }
        
        function removeImage(index) {
            selectedFiles.splice(index, 1);
            previewContainer.innerHTML = '';
            
            selectedFiles.forEach((file, newIndex) => {
                createPreview(file, newIndex);
            });
            
            updateFileInput();
        }
        
        function updateFileInput() {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            imageInput.files = dt.files;
        }
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value.trim();
            const descricao = document.getElementById('descricao').value.trim();
            const categoria = document.getElementById('categoria_id').value;
            const preco = document.getElementById('preco').value;
            const prazo = document.getElementById('prazo_execucao').value;
            
            if (!titulo || !descricao || !categoria || !preco || !prazo) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios');
                return;
            }
            
            if (parseFloat(preco) <= 0) {
                e.preventDefault();
                alert('O preço deve ser maior que zero');
                return;
            }
            
            if (parseInt(prazo) <= 0) {
                e.preventDefault();
                alert('O prazo de execução deve ser maior que zero');
                return;
            }
        });
        
        // Contador de caracteres para descrição
        const descricaoTextarea = document.getElementById('descricao');
        const maxLength = 1000;
        
        descricaoTextarea.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            const helpText = this.parentNode.querySelector('.help-text');
            helpText.textContent = `Seja específico sobre o que está incluído e o que não está (${remaining} caracteres restantes)`;
            
            if (remaining < 50) {
                helpText.style.color = '#e74c3c';
            } else {
                helpText.style.color = '#6c757d';
            }
        });
    </script>
</body>
</html>


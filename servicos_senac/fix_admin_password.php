<?php
require_once 'includes/config.php';

try {
    // Criptografar a senha do administrador
    $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Atualizar a senha no banco
    $stmt = $pdo->prepare("UPDATE administradores SET senha = ? WHERE email = 'admin@servicos.com'");
    $stmt->execute([$senha_hash]);
    
    echo "Senha do administrador atualizada com sucesso!<br>";
    echo "Email: admin@servicos.com<br>";
    echo "Senha: admin123<br>";
    echo "<br><a href='login.php'>Fazer login</a>";
    
} catch (PDOException $e) {
    echo "Erro ao atualizar senha: " . $e->getMessage();
}
?>


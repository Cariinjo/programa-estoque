<?php
// 1. Inicia a sessão para que possamos acessá-la.
session_start();

// 2. Remove todas as variáveis da sessão (limpa o array $_SESSION).
session_unset();

// 3. Destrói a sessão por completo no servidor.
session_destroy();

// 4. Redireciona o usuário para a página de login.
header('Location: entrada.php?msg=' . urlencode('Você saiu com sucesso.'));

// 5. Garante que nenhum outro código seja executado após o redirecionamento.
exit();
?>
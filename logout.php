<?php
require_once 'conexao.php';

// Limpar todas as variáveis de sessão
$_SESSION = [];

// Destruir o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Invalidar a sessão no servidor e gerar novo ID
session_destroy();
session_start();
session_regenerate_id(true);

// Redirecionar para login com mensagem
$_SESSION['flash_sucesso'] = 'Você saiu da conta com sucesso.';
header('Location: login.php');
exit;
?>

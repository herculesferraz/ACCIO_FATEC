<?php
// ============================================================
// CONEXÃO COM BANCO DE DADOS — PDO
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'biblioteca_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getConexao() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn     = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
        }
    }
    return $pdo;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Helpers de sessão ───────────────────────────────────────

function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

function requerLogin() {
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

function ehBibliotecario() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'bibliotecario';
}

function ehAluno() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'aluno';
}

/** Redireciona com mensagem de acesso negado se não for bibliotecário */
function requerBibliotecario() {
    requerLogin();
    if (!ehBibliotecario()) {
        $_SESSION['flash_erro'] = 'Acesso restrito a bibliotecários.';
        header('Location: index.php');
        exit;
    }
}

// ─── Helpers de output ────────────────────────────────────────

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Armazena uma mensagem flash na sessão para ser exibida na próxima página.
 * @param string $tipo  'sucesso' | 'erro' | 'info'
 */
function flash($tipo, $msg) {
    $_SESSION['flash_' . $tipo] = $msg;
}

/**
 * Lê e limpa todas as mensagens flash da sessão.
 * Retorna HTML pronto para inclusão.
 */
function exibirFlash() {
    $html = '';
    foreach (['sucesso', 'erro', 'info'] as $tipo) {
        $key = 'flash_' . $tipo;
        if (!empty($_SESSION[$key])) {
            $html .= '<div class="alert alert-' . $tipo . '">' . h($_SESSION[$key]) . '</div>';
            unset($_SESSION[$key]);
        }
    }
    return $html;
}
?>

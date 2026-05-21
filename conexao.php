<?php
// ============================================================
// CONEXÃO COM BANCO DE DADOS - PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'biblioteca_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getConexao() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: verificar se está logado
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

// Helper: redirecionar se não logado
function requerLogin() {
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

// Helper: sanitizar saída
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>

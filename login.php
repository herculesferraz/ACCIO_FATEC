<?php
require_once 'conexao.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $pdo = getConexao();
        $stmt = $pdo->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header('Location: index.php');
            exit;
        } else {
            $erro = 'Email ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACCIOTEK - Entrar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="book-cover" id="bookCover">
            <div class="book-spine"></div>
            <div class="book-front">
                <div class="book-ornament top"></div>
                <div class="book-title-area">
                    <div class="book-emblem"></div>
                    <h1 class="book-name">ACCIOTECK<br></h1>
                    <p class="book-subtitle">BIBLIOTECA PUBLICA</p>
                </div>
                <div class="book-ornament bottom"></div>
            </div>
        </div>

        <div class="auth-form-wrapper" id="authForm">
            <div class="form-header">
                <div class="form-icon">🔐</div>
                <h2>Bem-vindo de volta</h2>
                <p>Entre para acessar seu acervo</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-erro"><?= h($erro) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="field-group">
                    <label for="email">Email</label>
                    <div class="field-wrapper">
                        <span class="field-icon">✉</span>
                        <input type="email" id="email" name="email" placeholder="seu@email.edu" required value="<?= h($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="field-group">
                    <label for="senha">Senha</label>
                    <div class="field-wrapper">
                        <span class="field-icon"><i class="bi bi-key"></i></span>
                        <input type="password" id="senha" name="senha" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">
                    <span>Entrar na Biblioteca</span>
                </button>
            </form>

            <div class="auth-divider"><span>ou</span></div>

            <a href="cadastro.php" class="btn-secondary btn-full">
                <span>Criar nova conta</span>
            </a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>

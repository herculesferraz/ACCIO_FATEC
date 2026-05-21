<?php
require_once 'conexao.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $conf  = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter ao menos 6 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo = getConexao();
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = 'Este email já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $email, $hash]);
            $sucesso = 'Conta criada! Você pode fazer login agora.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACCIOTEK</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <h1 class="book-name">ACCIOTEK<br></h1>
                    <p class="book-subtitle">biblioteca</p>
                </div>
                <div class="book-ornament bottom"></div>
            </div>
        </div>

        <div class="auth-form-wrapper" id="authForm">
            <div class="form-header">
                <div class="form-icon">✍</div>
                <h2>Criar Conta</h2>
                <p>Junte-se à nossa biblioteca</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-erro"><?= h($erro) ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alert alert-sucesso"><?= h($sucesso) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="field-group">
                    <label for="nome">Nome completo</label>
                    <div class="field-wrapper">
                        <span class="field-icon">👤</span>
                        <input type="text" id="nome" name="nome" placeholder="Seu nome" required value="<?= h($_POST['nome'] ?? '') ?>">
                    </div>
                </div>

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
                        <span class="field-icon">🔑</span>
                        <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <div class="field-wrapper">
                        <span class="field-icon">🔒</span>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a senha" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary btn-full">
                    <span>Criar Conta</span>
                </button>
            </form>

            <div class="auth-divider"><span>ou</span></div>

            <a href="login.php" class="btn-secondary btn-full">
                <span>Já tenho conta — Entrar</span>
            </a>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>

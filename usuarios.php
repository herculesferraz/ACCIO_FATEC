<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

$totalFavs = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE id_usuario = ?");
$totalFavs->execute([$uid]);
$qtdFavs = $totalFavs->fetchColumn();

$totalLidos = $pdo->prepare("SELECT COUNT(*) FROM lidos WHERE id_usuario = ?");
$totalLidos->execute([$uid]);
$qtdLidos = $totalLidos->fetchColumn();

$totalEmp = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ?");
$totalEmp->execute([$uid]);
$qtdEmp = $totalEmp->fetchColumn();

$empAtivos = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status='ativo'");
$empAtivos->execute([$uid]);
$qtdAtivos = $empAtivos->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">Meu Perfil</h1>
            <p class="page-subtitle">Sua conta na biblioteca</p>
        </div>
    </div>

    <main class="container">
        <div class="profile-layout">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?= mb_strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
                </div>
                <h2 class="profile-name"><?= h($usuario['nome']) ?></h2>
                <p class="profile-email"><?= h($usuario['email']) ?></p>
                <p class="profile-since">Membro desde <?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">❤</div>
                    <div class="stat-num"><?= $qtdFavs ?></div>
                    <div class="stat-label">Favoritos</div>
                    <a href="favoritos.php" class="stat-link">Ver todos →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-num"><?= $qtdLidos ?></div>
                    <div class="stat-label">Livros Lidos</div>
                    <a href="lidos.php" class="stat-link">Ver todos →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📤</div>
                    <div class="stat-num"><?= $qtdEmp ?></div>
                    <div class="stat-label">Empréstimos</div>
                    <a href="emprestimos.php" class="stat-link">Ver todos →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📗</div>
                    <div class="stat-num"><?= $qtdAtivos ?></div>
                    <div class="stat-label">Em andamento</div>
                    <a href="emprestimos.php" class="stat-link">Gerenciar →</a>
                </div>
            </div>
        </div>

        <div style="text-align:center;margin-top:2rem">
            <a href="logout.php" class="btn-ghost" onclick="return confirm('Deseja sair da sua conta?')">
                Sair
            </a>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

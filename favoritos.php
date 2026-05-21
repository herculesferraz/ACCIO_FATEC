<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();

$stmt = $pdo->prepare("
    SELECT l.*, f.id as fav_id
    FROM livros l
    INNER JOIN favoritos f ON l.id = f.id_livro
    WHERE f.id_usuario = ?
    ORDER BY l.titulo ASC
");
$stmt->execute([$uid]);
$livros = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Favoritos — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">❤ Meus Favoritos</h1>
            <p class="page-subtitle"><?= count($livros) ?> obra(s) em sua lista de desejos</p>
        </div>
    </div>

    <main class="container">
        <?php if (empty($livros)): ?>
            <div class="empty-state">
                <div class="empty-icon">💔</div>
                <h3>Nenhum favorito ainda</h3>
                <p>Explore o catálogo e favorite os livros que mais gostar!</p>
                <a href="index.php" class="btn-primary" style="margin-top:1rem">Ver Catálogo</a>
            </div>
        <?php else: ?>
        <div class="books-grid">
            <?php foreach ($livros as $livro): ?>
            <div class="book-card" data-id="<?= $livro['id'] ?>">
                <div class="book-card-cover">
                    <?php if ($livro['capa_url']): ?>
                        <img src="<?= h($livro['capa_url']) ?>" alt="<?= h($livro['titulo']) ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="book-placeholder" style="display:none">📖</div>
                    <?php else: ?>
                        <div class="book-placeholder">📖</div>
                    <?php endif; ?>
                    <div class="book-card-overlay">
                        <a href="detalhes.php?id=<?= $livro['id'] ?>" class="btn-details">Ver Detalhes</a>
                    </div>
                </div>
                <div class="book-card-info">
                    <span class="book-category"><?= h($livro['categoria']) ?></span>
                    <h3 class="book-title"><?= h($livro['titulo']) ?></h3>
                    <p class="book-author">por <?= h($livro['autor']) ?></p>
                    <div class="book-actions">
                        <button class="btn-action btn-fav active"
                            onclick="toggleFavorito(<?= $livro['id'] ?>, this)" title="Remover favorito">❤</button>
                        <a href="detalhes.php?id=<?= $livro['id'] ?>" class="btn-action" title="Detalhes">👁</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

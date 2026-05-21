<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();

$stmt = $pdo->prepare("
    SELECT l.*, ld.data_lido
    FROM livros l
    INNER JOIN lidos ld ON l.id = ld.id_livro
    WHERE ld.id_usuario = ?
    ORDER BY ld.data_lido DESC
");
$stmt->execute([$uid]);
$livros = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livros Lidos — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">✅ Livros Lidos</h1>
            <p class="page-subtitle"><?= count($livros) ?> obra(s) concluída(s)</p>
        </div>
    </div>

    <main class="container">
        <?php if (empty($livros)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Nenhum livro marcado como lido</h3>
                <p>Marque os livros que você já leu para manter seu histórico!</p>
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
                    <div class="read-badge">✅ Lido</div>
                </div>
                <div class="book-card-info">
                    <span class="book-category"><?= h($livro['categoria']) ?></span>
                    <h3 class="book-title"><?= h($livro['titulo']) ?></h3>
                    <p class="book-author">por <?= h($livro['autor']) ?></p>
                    <p class="book-date">Lido em: <?= date('d/m/Y', strtotime($livro['data_lido'])) ?></p>
                    <div class="book-actions">
                        <button class="btn-action btn-lido active"
                            onclick="toggleLido(<?= $livro['id'] ?>, this)" title="Desmarcar como lido">✅</button>
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

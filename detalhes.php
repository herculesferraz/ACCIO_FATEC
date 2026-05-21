<?php
require_once 'conexao.php';
requerLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

$pdo = getConexao();
$stmt = $pdo->prepare("SELECT * FROM livros WHERE id = ?");
$stmt->execute([$id]);
$livro = $stmt->fetch();
if (!$livro) {
    header('Location: index.php');
    exit;
}

$uid = $_SESSION['usuario_id'];

// Status favorito
$fav = $pdo->prepare("SELECT id FROM favoritos WHERE id_usuario = ? AND id_livro = ?");
$fav->execute([$uid, $id]);
$isFav = (bool)$fav->fetch();

// Status lido
$lid = $pdo->prepare("SELECT id FROM lidos WHERE id_usuario = ? AND id_livro = ?");
$lid->execute([$uid, $id]);
$isLido = (bool)$lid->fetch();

// Empréstimo ativo
$emp = $pdo->prepare("SELECT * FROM emprestimos WHERE id_usuario = ? AND id_livro = ? AND status = 'ativo'");
$emp->execute([$uid, $id]);
$empAtivo = $emp->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($livro['titulo']) ?> — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container" style="padding-top:2rem">
        <a href="javascript:history.back()" class="btn-ghost" style="display:inline-flex;align-items:center;gap:.5rem;margin-bottom:2rem">
            ← Voltar ao catálogo
        </a>

        <div class="detail-layout">
            <!-- Capa -->
            <div class="detail-cover-col">
                <div class="detail-cover">
                    <?php if ($livro['capa_url']): ?>
                        <img src="<?= h($livro['capa_url']) ?>" alt="<?= h($livro['titulo']) ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="book-placeholder-lg" style="display:none">📖</div>
                    <?php else: ?>
                        <div class="book-placeholder-lg">📖</div>
                    <?php endif; ?>
                </div>

                <div class="detail-actions">
                    <button class="btn-action-lg btn-fav <?= $isFav ? 'active' : '' ?>"
                        onclick="toggleFavorito(<?= $livro['id'] ?>, this)" id="btnFav">
                        <?= $isFav ? '❤ Favoritado' : '🤍 Favoritar' ?>
                    </button>
                    <button class="btn-action-lg btn-lido <?= $isLido ? 'active' : '' ?>"
                        onclick="toggleLido(<?= $livro['id'] ?>, this)" id="btnLido">
                        <?= $isLido ? '✅ Lido' : '📌 Marcar como lido' ?>
                    </button>

                    <?php if ($empAtivo): ?>
                        <a href="emprestimos.php?action=devolver&id=<?= $empAtivo['id'] ?>" class="btn-action-lg btn-devolver">
                            📥 Devolver Livro
                        </a>
                    <?php elseif ($livro['quantidade'] > 0): ?>
                        <a href="emprestimos.php?action=novo&livro=<?= $livro['id'] ?>" class="btn-action-lg btn-emprestar">
                            📤 Emprestar
                        </a>
                    <?php else: ?>
                        <span class="btn-action-lg btn-disabled">📭 Sem exemplares</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="detail-info-col">
                <div class="detail-category"><?= h($livro['categoria']) ?></div>
                <h1 class="detail-title"><?= h($livro['titulo']) ?></h1>
                <p class="detail-author">por <strong><?= h($livro['autor']) ?></strong></p>

                <div class="detail-meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Ano</span>
                        <span class="meta-val"><?= h($livro['ano_publicacao'] ?? '—') ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Disponíveis</span>
                        <span class="meta-val <?= $livro['quantidade'] > 0 ? 'available' : 'unavailable' ?>">
                            <?= $livro['quantidade'] ?> exemplar(es)
                        </span>
                    </div>
                </div>

                <?php if ($livro['sinopse']): ?>
                    <div class="detail-synopsis">
                        <h3>Sinopse</h3>
                        <p><?= nl2br(h($livro['sinopse'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>

</html>
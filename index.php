<?php
require_once 'conexao.php';
requerLogin();

$pdo = getConexao();

// BUG CORRIGIDO: filtrar por status = 'ativo' em vez de data_devolucao IS NULL
$emprestimosStmt = $pdo->query(
    "SELECT id_livro FROM emprestimos WHERE status IN ('ativo','atrasado')"
);
$emprestadosSet = array_flip($emprestimosStmt->fetchAll(PDO::FETCH_COLUMN));

// Parâmetros de busca e filtro
$busca     = trim($_GET['busca'] ?? '');
$cat       = $_GET['categoria'] ?? '';
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 12;
$offset    = ($pagina - 1) * $porPagina;

$where  = [];
$params = [];

if ($busca) {
    $where[]  = "(titulo LIKE ? OR autor LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}
if ($cat) {
    $where[]  = "categoria = ?";
    $params[] = $cat;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM livros $whereStr");
$stmtTotal->execute($params);
$total       = $stmtTotal->fetchColumn();
$totalPaginas = ceil($total / $porPagina);

$params[] = $porPagina;
$params[] = $offset;
$stmt     = $pdo->prepare("SELECT * FROM livros $whereStr ORDER BY titulo ASC LIMIT ? OFFSET ?");
$stmt->execute($params);
$livros = $stmt->fetchAll();

$cats = $pdo->query("SELECT DISTINCT categoria FROM livros ORDER BY categoria")
             ->fetchAll(PDO::FETCH_COLUMN);

$uid = $_SESSION['usuario_id'];

$favs = $pdo->prepare("SELECT id_livro FROM favoritos WHERE id_usuario = ?");
$favs->execute([$uid]);
$favoritosSet = array_flip($favs->fetchAll(PDO::FETCH_COLUMN));

$lsStmt = $pdo->prepare("SELECT id_livro FROM lidos WHERE id_usuario = ?");
$lsStmt->execute([$uid]);
$lidosSet = array_flip($lsStmt->fetchAll(PDO::FETCH_COLUMN));

// Reservas do usuário (para exibir ícone)
$resStmt = $pdo->prepare("SELECT id_livro FROM reservas WHERE id_usuario = ? AND status = 'ativa'");
$resStmt->execute([$uid]);
$reservasSet = array_flip($resStmt->fetchAll(PDO::FETCH_COLUMN));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACCIO Tech — Livros</title>
    <link rel="icon" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title"><i class="bi bi-book-fill"></i> Catálogo de Livros</h1>
            <p class="page-subtitle">Explore nosso acervo de <?= $total ?> obras</p>
        </div>
    </div>

    <main class="container">
        <!-- Mensagens flash -->
        <?= exibirFlash() ?>

        <!-- Busca e filtros -->
        <form class="search-bar" method="GET">
            <div class="search-input-wrap">
                <span class="search-icon"><i class="bi bi-search"></i></span>
                <input type="text" name="busca"
                       placeholder="Buscar por título ou autor..."
                       value="<?= h($busca) ?>">
            </div>
            <select name="categoria">
                <option value="">Todas as categorias</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= h($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= h($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Buscar</button>
            <?php if ($busca || $cat): ?>
                <a href="index.php" class="btn-ghost">Limpar</a>
            <?php endif; ?>
        </form>

        <!-- Grid de livros -->
        <?php if (empty($livros)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Nenhum livro encontrado</h3>
                <p>Tente buscar com outros termos</p>
            </div>
        <?php else: ?>
            <div class="books-grid">
                <?php foreach ($livros as $livro): ?>
                    <div class="book-card" data-id="<?= $livro['id'] ?>">
                        <div class="book-card-cover">
                            <?php if ($livro['capa_url']): ?>
                                <img src="<?= h($livro['capa_url']) ?>"
                                     alt="<?= h($livro['titulo']) ?>"
                                     loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="book-placeholder" style="display:none">
                                    <i class="bi bi-book-fill"></i>
                                </div>
                            <?php else: ?>
                                <div class="book-placeholder"><i class="bi bi-book-fill"></i></div>
                            <?php endif; ?>
                            <div class="book-card-overlay">
                                <a href="detalhes.php?id=<?= $livro['id'] ?>" class="btn-details">Ver Detalhes</a>
                            </div>
                        </div>

                        <div class="book-card-info">
                            <span class="book-category"><?= h($livro['categoria']) ?></span>
                            <h3 class="book-title"><?= h($livro['titulo']) ?></h3>
                            <p class="book-author">por <?= h($livro['autor']) ?></p>

                            <div class="book-meta">
                                <span class="book-qty <?= $livro['quantidade'] > 0 ? 'available' : 'unavailable' ?>">
                                    <?= $livro['quantidade'] > 0
                                        ? "✓ {$livro['quantidade']} disponível(is)"
                                        : "✗ Indisponível" ?>
                                </span>
                            </div>

                            <div class="book-actions">
                                <button class="btn-action btn-fav <?= isset($favoritosSet[$livro['id']]) ? 'active' : '' ?>"
                                        onclick="toggleFavorito(<?= $livro['id'] ?>, this)"
                                        title="Favoritar">
                                    <?= isset($favoritosSet[$livro['id']])
                                        ? '<i class="bi bi-heart-fill"></i>'
                                        : '<i class="bi bi-heart"></i>' ?>
                                </button>

                                <button class="btn-action btn-lido <?= isset($lidosSet[$livro['id']]) ? 'active' : '' ?>"
                                        onclick="toggleLido(<?= $livro['id'] ?>, this)"
                                        title="Marcar como lido">
                                    <?= isset($lidosSet[$livro['id']])
                                        ? '<i class="bi bi-check-lg"></i>'
                                        : '<i class="bi bi-eyeglasses"></i>' ?>
                                </button>

                                <?php
                                // Ícone de empréstimo/solicitação adaptado ao papel
                                $jaEmprestado = isset($emprestadosSet[$livro['id']]);
                                $temReserva   = isset($reservasSet[$livro['id']]);

                                if ($temReserva): ?>
                                    <a href="reservas.php" class="btn-action" title="Reserva ativa">
                                        <i class="bi bi-bookmark-heart-fill" style="color:var(--accent)"></i>
                                    </a>
                                <?php elseif ($jaEmprestado): ?>
                                    <span class="btn-action" title="Exemplar emprestado" style="cursor:default;opacity:.5">
                                        <i class="bi bi-bookmark-check-fill"></i>
                                    </span>
                                <?php elseif ($livro['quantidade'] > 0 && !ehBibliotecario()): ?>
                                    <a href="emprestimos.php?action=solicitar&livro=<?= $livro['id'] ?>"
                                       class="btn-action btn-emprestimo"
                                       title="Solicitar empréstimo"
                                       onclick="return confirm('Solicitar empréstimo de \'<?= h(addslashes($livro['titulo'])) ?>\'?')">
                                        <i class="bi bi-bookmark-plus"></i>
                                    </a>
                                <?php elseif ($livro['quantidade'] <= 0 && !ehBibliotecario()): ?>
                                    <a href="reservas.php?action=reservar&livro=<?= $livro['id'] ?>"
                                       class="btn-action"
                                       title="Fazer reserva"
                                       onclick="return confirm('Fazer reserva para quando um exemplar ficar disponível?')">
                                        <i class="bi bi-bookmark-dash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="detalhes.php?id=<?= $livro['id'] ?>" class="btn-action" title="Detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>&categoria=<?= urlencode($cat) ?>"
                           class="page-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();
$msg = '';
$msgTipo = '';

// Novo empréstimo
$action = $_GET['action'] ?? '';

if ($action === 'novo' && isset($_GET['livro'])) {
    $lid = (int)$_GET['livro'];

    // Verificar se já tem empréstimo ativo
    $chk = $pdo->prepare("SELECT id FROM emprestimos WHERE id_usuario = ? AND id_livro = ? AND status = 'ativo'");
    $chk->execute([$uid, $lid]);
    if ($chk->fetch()) {
        $msg = 'Você já tem um empréstimo ativo deste livro!';
        $msgTipo = 'erro';
    } else {
        // Verificar disponibilidade
        $qtyStmt = $pdo->prepare("SELECT quantidade FROM livros WHERE id = ?");
        $qtyStmt->execute([$lid]);
        $livroQty = $qtyStmt->fetchColumn();

        if ($livroQty > 0) {
            $dataDev = date('Y-m-d', strtotime('+14 days'));
            $ins = $pdo->prepare("INSERT INTO emprestimos (id_usuario, id_livro, data_emprestimo, data_devolucao, status) VALUES (?, ?, CURDATE(), ?, 'ativo')");
            $ins->execute([$uid, $lid, $dataDev]);
            $pdo->prepare("UPDATE livros SET quantidade = quantidade - 1 WHERE id = ?")->execute([$lid]);
            $msg = 'Empréstimo registrado! Devolução prevista: ' . date('d/m/Y', strtotime($dataDev));
            $msgTipo = 'sucesso';
        } else {
            $msg = 'Livro indisponível no momento.';
            $msgTipo = 'erro';
        }
    }
}

if ($action === 'devolver' && isset($_GET['id'])) {
    $eid = (int)$_GET['id'];
    $empStmt = $pdo->prepare("SELECT * FROM emprestimos WHERE id = ? AND id_usuario = ?");
    $empStmt->execute([$eid, $uid]);
    $empData = $empStmt->fetch();
    if ($empData && $empData['status'] === 'ativo') {
        $pdo->prepare("UPDATE emprestimos SET status='devolvido', data_devolucao=CURDATE() WHERE id=?")->execute([$eid]);
        $pdo->prepare("UPDATE livros SET quantidade = quantidade + 1 WHERE id = ?")->execute([$empData['id_livro']]);
        $msg = 'Livro devolvido com sucesso!';
        $msgTipo = 'sucesso';
    }
}

// Listar empréstimos do usuário
$stmt = $pdo->prepare("
    SELECT e.*, l.titulo, l.autor, l.capa_url
    FROM emprestimos e
    INNER JOIN livros l ON e.id_livro = l.id
    WHERE e.id_usuario = ?
    ORDER BY e.data_emprestimo DESC
");
$stmt->execute([$uid]);
$emprestimos = $stmt->fetchAll();

// Atualizar status atrasados
$pdo->prepare("UPDATE emprestimos SET status='atrasado' WHERE status='ativo' AND data_devolucao < CURDATE()")->execute();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title"><i class="bi bi-book-fill"></i> Meus Empréstimos</h1>
            <p class="page-subtitle">Gerencie seus empréstimos de livros</p>
        </div>
    </div>

    <main class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgTipo ?>"><?= h($msg) ?></div>
        <?php endif; ?>

        <?php if (empty($emprestimos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Nenhum empréstimo registrado</h3>
                <p>Vá ao catálogo e solicite um empréstimo!</p>
                <a href="index.php" class="btn-primary" style="margin-top:1rem">Ver Catálogo</a>
            </div>
        <?php else: ?>
        <div class="loans-list">
            <?php foreach ($emprestimos as $e): ?>
            <div class="loan-card status-<?= $e['status'] ?>">
                <div class="loan-cover">
                    <?php if ($e['capa_url']): ?>
                        <img src="<?= h($e['capa_url']) ?>" alt="">
                    <?php else: ?>
                        <span>📖</span>
                    <?php endif; ?>
                </div>
                <div class="loan-info">
                    <h3><?= h($e['titulo']) ?></h3>
                    <p class="loan-author">por <?= h($e['autor']) ?></p>
                    <div class="loan-dates">
                        <span>📅 Emprestado: <?= date('d/m/Y', strtotime($e['data_emprestimo'])) ?></span>
                        <span>🗓 Devolução: <?= $e['data_devolucao'] ? date('d/m/Y', strtotime($e['data_devolucao'])) : '—' ?></span>
                    </div>
                    <span class="loan-status status-badge-<?= $e['status'] ?>">
                        <?= ['ativo'=>'📗 Ativo','devolvido'=>'📘 Devolvido','atrasado'=>'📕 Atrasado'][$e['status']] ?? $e['status'] ?>
                    </span>
                </div>
                <?php if ($e['status'] === 'ativo' || $e['status'] === 'atrasado'): ?>
                <div class="loan-action">
                    <a href="emprestimos.php?action=devolver&id=<?= $e['id'] ?>" class="btn-primary btn-sm-txt" onclick="return confirm('Confirmar devolução?')">
                        📥 Devolver
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

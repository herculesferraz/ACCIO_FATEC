<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();
$msg     = '';
$msgTipo = '';

$action = $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════
// ALUNO: Fazer reserva (só quando livro indisponível)
// ═══════════════════════════════════════════════════════════
if ($action === 'reservar' && isset($_GET['livro'])) {
    $lid = (int)$_GET['livro'];

    // Verificar disponibilidade — reserva só é permitida quando quantidade = 0
    $qtyStmt = $pdo->prepare("SELECT quantidade FROM livros WHERE id = ?");
    $qtyStmt->execute([$lid]);
    $qty = (int)$qtyStmt->fetchColumn();

    if ($qty > 0) {
        $msg     = 'Este livro possui exemplares disponíveis. Solicite um empréstimo diretamente.';
        $msgTipo = 'info';
    } else {
        // Verificar se já tem reserva ativa ou empréstimo pendente/ativo
        $chkRes = $pdo->prepare(
            "SELECT id FROM reservas WHERE id_usuario = ? AND id_livro = ? AND status = 'ativa'"
        );
        $chkRes->execute([$uid, $lid]);

        $chkEmp = $pdo->prepare(
            "SELECT id FROM emprestimos
             WHERE id_usuario = ? AND id_livro = ? AND status IN ('ativo','pendente','atrasado')"
        );
        $chkEmp->execute([$uid, $lid]);

        if ($chkRes->fetch()) {
            $msg     = 'Você já possui uma reserva ativa para este livro.';
            $msgTipo = 'erro';
        } elseif ($chkEmp->fetch()) {
            $msg     = 'Você já possui um empréstimo ativo ou pendente deste livro.';
            $msgTipo = 'erro';
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO reservas (id_usuario, id_livro, data_reserva, status)
                 VALUES (?, ?, CURDATE(), 'ativa')"
            );
            $ins->execute([$uid, $lid]);
            $msg     = 'Reserva registrada! Você será notificado quando um exemplar ficar disponível.';
            $msgTipo = 'sucesso';
        }
    }
}

// ═══════════════════════════════════════════════════════════
// ALUNO / BIBLIOTECÁRIO: Cancelar reserva
// ═══════════════════════════════════════════════════════════
if ($action === 'cancelar' && isset($_GET['id'])) {
    $rid = (int)$_GET['id'];

    // Aluno só pode cancelar a própria; bibliotecário pode cancelar qualquer uma
    if (ehBibliotecario()) {
        $del = $pdo->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
        $del->execute([$rid]);
    } else {
        $del = $pdo->prepare(
            "UPDATE reservas SET status = 'cancelada' WHERE id = ? AND id_usuario = ?"
        );
        $del->execute([$rid, $uid]);
    }
    $msg     = 'Reserva cancelada.';
    $msgTipo = 'info';
}

// ═══════════════════════════════════════════════════════════
// BIBLIOTECÁRIO: Converter reserva em empréstimo (pendente)
-- quando houver devoluções e exemplar ficar disponível
// ═══════════════════════════════════════════════════════════
if ($action === 'converter' && isset($_GET['id']) && ehBibliotecario()) {
    $rid = (int)$_GET['id'];

    $resStmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ? AND status = 'ativa'");
    $resStmt->execute([$rid]);
    $res = $resStmt->fetch();

    if ($res) {
        $qtyStmt = $pdo->prepare("SELECT quantidade FROM livros WHERE id = ?");
        $qtyStmt->execute([$res['id_livro']]);
        $qty = (int)$qtyStmt->fetchColumn();

        if ($qty > 0) {
            $dataDev = date('Y-m-d', strtotime('+14 days'));
            $ins = $pdo->prepare(
                "INSERT INTO emprestimos (id_usuario, id_livro, data_emprestimo, data_devolucao, status)
                 VALUES (?, ?, CURDATE(), ?, 'pendente')"
            );
            $ins->execute([$res['id_usuario'], $res['id_livro'], $dataDev]);

            // Marcar reserva como convertida
            $pdo->prepare("UPDATE reservas SET status = 'convertida' WHERE id = ?")
                ->execute([$rid]);

            $msg     = 'Reserva convertida em solicitação de empréstimo com sucesso!';
            $msgTipo = 'sucesso';
        } else {
            $msg     = 'Ainda não há exemplares disponíveis para converter esta reserva.';
            $msgTipo = 'erro';
        }
    }
}

// ═══════════════════════════════════════════════════════════
// Listar reservas
// ═══════════════════════════════════════════════════════════
if (ehBibliotecario()) {
    $stmt = $pdo->prepare("
        SELECT r.*, l.titulo, l.autor, l.capa_url, l.quantidade,
               u.nome AS nome_usuario
        FROM reservas r
        INNER JOIN livros   l ON r.id_livro   = l.id
        INNER JOIN usuarios u ON r.id_usuario = u.id
        WHERE r.status = 'ativa'
        ORDER BY r.data_reserva ASC
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, l.titulo, l.autor, l.capa_url, l.quantidade
        FROM reservas r
        INNER JOIN livros l ON r.id_livro = l.id
        WHERE r.id_usuario = ? AND r.status = 'ativa'
        ORDER BY r.data_reserva DESC
    ");
    $stmt->execute([$uid]);
}
$reservas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">🔖 <?= ehBibliotecario() ? 'Gerenciar Reservas' : 'Minhas Reservas' ?></h1>
            <p class="page-subtitle">
                <?= ehBibliotecario()
                    ? 'Gerencie e converta reservas em empréstimos'
                    : 'Acompanhe suas reservas de livros indisponíveis' ?>
            </p>
        </div>
    </div>

    <main class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgTipo ?>"><?= $msg ?></div>
        <?php endif; ?>

        <?php if (empty($reservas)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔖</div>
                <h3>Nenhuma reserva ativa</h3>
                <p>
                    <?= ehBibliotecario()
                        ? 'Nenhum usuário possui reservas ativas no momento.'
                        : 'Reservas aparecem aqui quando você reserva um livro sem exemplares disponíveis.' ?>
                </p>
                <a href="index.php" class="btn-primary" style="margin-top:1rem">Ver Catálogo</a>
            </div>
        <?php else: ?>
        <div class="loans-list">
            <?php foreach ($reservas as $r): ?>
            <div class="loan-card status-ativo">
                <div class="loan-cover">
                    <?php if ($r['capa_url']): ?>
                        <img src="<?= h($r['capa_url']) ?>" alt="">
                    <?php else: ?>
                        <span>📖</span>
                    <?php endif; ?>
                </div>

                <div class="loan-info">
                    <h3><?= h($r['titulo']) ?></h3>
                    <p class="loan-author">por <?= h($r['autor']) ?></p>

                    <?php if (ehBibliotecario()): ?>
                        <p class="loan-author">👤 Reservado por: <strong><?= h($r['nome_usuario']) ?></strong></p>
                    <?php endif; ?>

                    <div class="loan-dates">
                        <span>📅 Reservado em: <?= date('d/m/Y', strtotime($r['data_reserva'])) ?></span>
                        <span class="<?= $r['quantidade'] > 0 ? 'available' : 'unavailable' ?>">
                            <?= $r['quantidade'] > 0
                                ? "✓ {$r['quantidade']} exemplar(es) disponível(is)"
                                : '✗ Sem exemplares disponíveis' ?>
                        </span>
                    </div>

                    <span class="loan-status status-badge-ativo">🔖 Reserva ativa</span>
                </div>

                <div class="loan-action" style="display:flex;flex-direction:column;gap:.5rem">
                    <?php if (ehBibliotecario() && $r['quantidade'] > 0): ?>
                        <a href="reservas.php?action=converter&id=<?= $r['id'] ?>"
                           class="btn-primary btn-sm-txt"
                           onclick="return confirm('Converter esta reserva em solicitação de empréstimo?')">
                            ✅ Converter em empréstimo
                        </a>
                    <?php endif; ?>

                    <a href="reservas.php?action=cancelar&id=<?= $r['id'] ?>"
                       class="btn-ghost btn-sm-txt"
                       onclick="return confirm('Cancelar esta reserva?')">
                        🗑 Cancelar reserva
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

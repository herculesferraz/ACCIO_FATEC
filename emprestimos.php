<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();
$msg     = '';
$msgTipo = '';

$action = $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════
// ALUNO: Solicitar empréstimo (status = 'pendente')
// ═══════════════════════════════════════════════════════════
if ($action === 'solicitar' && isset($_GET['livro'])) {
    $lid = (int)$_GET['livro'];

    // Verificar empréstimo ativo ou pendente já existente
    $chkEmp = $pdo->prepare(
        "SELECT id FROM emprestimos
         WHERE id_usuario = ? AND id_livro = ? AND status IN ('ativo','pendente','atrasado')"
    );
    $chkEmp->execute([$uid, $lid]);

    // Verificar reserva ativa já existente
    $chkRes = $pdo->prepare(
        "SELECT id FROM reservas WHERE id_usuario = ? AND id_livro = ? AND status = 'ativa'"
    );
    $chkRes->execute([$uid, $lid]);

    if ($chkEmp->fetch()) {
        $msg     = 'Você já possui um empréstimo ativo ou pendente deste livro.';
        $msgTipo = 'erro';
    } elseif ($chkRes->fetch()) {
        $msg     = 'Você já possui uma reserva ativa para este livro.';
        $msgTipo = 'erro';
    } else {
        // Verificar disponibilidade
        $qtyStmt = $pdo->prepare("SELECT quantidade FROM livros WHERE id = ?");
        $qtyStmt->execute([$lid]);
        $qty = (int)$qtyStmt->fetchColumn();

        if ($qty > 0) {
            // Há exemplares → solicitar empréstimo (pendente)
            $dataDev = date('Y-m-d', strtotime('+14 days'));
            $ins = $pdo->prepare(
                "INSERT INTO emprestimos (id_usuario, id_livro, data_emprestimo, data_devolucao, status)
                 VALUES (?, ?, CURDATE(), ?, 'pendente')"
            );
            $ins->execute([$uid, $lid, $dataDev]);
            $msg     = 'Solicitação de empréstimo enviada! Aguarde a confirmação do bibliotecário.';
            $msgTipo = 'sucesso';
        } else {
            // Sem exemplares → oferecer reserva
            $msg     = 'Não há exemplares disponíveis. <a href="reservas.php?action=reservar&livro=' . $lid . '">Clique aqui para fazer uma reserva.</a>';
            $msgTipo = 'info';
        }
    }
}

// ═══════════════════════════════════════════════════════════
// BIBLIOTECÁRIO: Confirmar solicitação (pendente → ativo)
// ═══════════════════════════════════════════════════════════
if ($action === 'confirmar' && isset($_GET['id']) && ehBibliotecario()) {
    $eid = (int)$_GET['id'];

    $empStmt = $pdo->prepare("SELECT * FROM emprestimos WHERE id = ? AND status = 'pendente'");
    $empStmt->execute([$eid]);
    $emp = $empStmt->fetch();

    if ($emp) {
        // Reconfirmar disponibilidade no momento da confirmação
        $qtyStmt = $pdo->prepare("SELECT quantidade FROM livros WHERE id = ?");
        $qtyStmt->execute([$emp['id_livro']]);
        $qty = (int)$qtyStmt->fetchColumn();

        if ($qty > 0) {
            $pdo->prepare(
                "UPDATE emprestimos
                 SET status = 'ativo', confirmado_por = ?
                 WHERE id = ?"
            )->execute([$uid, $eid]);
            $pdo->prepare("UPDATE livros SET quantidade = quantidade - 1 WHERE id = ?")
                ->execute([$emp['id_livro']]);
            $msg     = 'Empréstimo confirmado com sucesso!';
            $msgTipo = 'sucesso';
        } else {
            $msg     = 'Sem exemplares disponíveis no momento. Empréstimo não pode ser confirmado.';
            $msgTipo = 'erro';
        }
    } else {
        $msg     = 'Solicitação não encontrada ou já processada.';
        $msgTipo = 'erro';
    }
}

// ═══════════════════════════════════════════════════════════
// BIBLIOTECÁRIO: Rejeitar solicitação (pendente → cancelado)
// ═══════════════════════════════════════════════════════════
if ($action === 'rejeitar' && isset($_GET['id']) && ehBibliotecario()) {
    $eid = (int)$_GET['id'];
    $pdo->prepare(
        "UPDATE emprestimos SET status = 'cancelado' WHERE id = ? AND status = 'pendente'"
    )->execute([$eid]);
    $msg     = 'Solicitação rejeitada.';
    $msgTipo = 'info';
}

// ═══════════════════════════════════════════════════════════
// BIBLIOTECÁRIO: Registrar devolução
// ═══════════════════════════════════════════════════════════
if ($action === 'devolver' && isset($_GET['id']) && ehBibliotecario()) {
    $eid     = (int)$_GET['id'];
    $empStmt = $pdo->prepare(
        "SELECT * FROM emprestimos WHERE id = ? AND status IN ('ativo','atrasado')"
    );
    $empStmt->execute([$eid]);
    $empData = $empStmt->fetch();

    if ($empData) {
        $pdo->prepare(
            "UPDATE emprestimos SET status = 'devolvido', data_devolucao = CURDATE() WHERE id = ?"
        )->execute([$eid]);
        $pdo->prepare("UPDATE livros SET quantidade = quantidade + 1 WHERE id = ?")
            ->execute([$empData['id_livro']]);

        // Verificar se há reserva ativa para este livro e notificar (marcar para aviso)
        $resStmt = $pdo->prepare(
            "SELECT r.id, u.nome FROM reservas r
             INNER JOIN usuarios u ON r.id_usuario = u.id
             WHERE r.id_livro = ? AND r.status = 'ativa'
             ORDER BY r.data_reserva ASC LIMIT 1"
        );
        $resStmt->execute([$empData['id_livro']]);
        $proximaReserva = $resStmt->fetch();

        $msg     = 'Devolução registrada com sucesso!';
        $msgTipo = 'sucesso';

        if ($proximaReserva) {
            $msg .= ' Há uma reserva ativa para este livro do usuário <strong>' . h($proximaReserva['nome']) . '</strong>.';
        }
    } else {
        $msg     = 'Empréstimo não encontrado ou já devolvido.';
        $msgTipo = 'erro';
    }
}

// ═══════════════════════════════════════════════════════════
// Atualizar status atrasados
// ═══════════════════════════════════════════════════════════
$pdo->prepare(
    "UPDATE emprestimos SET status = 'atrasado'
     WHERE status = 'ativo' AND data_devolucao < CURDATE()"
)->execute();

// ═══════════════════════════════════════════════════════════
// Listar empréstimos
// ═══════════════════════════════════════════════════════════
if (ehBibliotecario()) {
    // Bibliotecário vê todos, incluindo pendentes
    $stmt = $pdo->prepare("
        SELECT e.*, l.titulo, l.autor, l.capa_url,
               u.nome AS nome_usuario,
               c.nome AS nome_confirmador
        FROM emprestimos e
        INNER JOIN livros l    ON e.id_livro   = l.id
        INNER JOIN usuarios u  ON e.id_usuario = u.id
        LEFT  JOIN usuarios c  ON e.confirmado_por = c.id
        ORDER BY
            FIELD(e.status, 'pendente', 'atrasado', 'ativo', 'devolvido', 'cancelado'),
            e.data_emprestimo DESC
    ");
    $stmt->execute();
} else {
    // Aluno vê apenas os seus
    $stmt = $pdo->prepare("
        SELECT e.*, l.titulo, l.autor, l.capa_url,
               c.nome AS nome_confirmador
        FROM emprestimos e
        INNER JOIN livros l   ON e.id_livro = l.id
        LEFT  JOIN usuarios c ON e.confirmado_por = c.id
        WHERE e.id_usuario = ?
        ORDER BY e.data_emprestimo DESC
    ");
    $stmt->execute([$uid]);
}
$emprestimos = $stmt->fetchAll();

// Labels e estilos por status
$statusLabel = [
    'pendente'   => '⏳ Aguardando confirmação',
    'ativo'      => '📗 Ativo',
    'devolvido'  => '📘 Devolvido',
    'atrasado'   => '📕 Atrasado',
    'cancelado'  => '🚫 Cancelado',
];
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
            <h1 class="page-title">
                <i class="bi bi-book-fill"></i>
                <?= ehBibliotecario() ? 'Gerenciar Empréstimos' : 'Meus Empréstimos' ?>
            </h1>
            <p class="page-subtitle">
                <?= ehBibliotecario()
                    ? 'Confirme solicitações e registre devoluções'
                    : 'Acompanhe suas solicitações e empréstimos' ?>
            </p>
        </div>
    </div>

    <main class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgTipo ?>"><?= $msg ?></div>
        <?php endif; ?>

        <?php if (empty($emprestimos)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>Nenhum empréstimo registrado</h3>
                <p>
                    <?= ehBibliotecario()
                        ? 'Nenhuma solicitação no sistema ainda.'
                        : 'Vá ao catálogo e solicite um empréstimo!' ?>
                </p>
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

                    <?php if (ehBibliotecario()): ?>
                        <p class="loan-author">👤 Solicitante: <strong><?= h($e['nome_usuario']) ?></strong></p>
                    <?php endif; ?>

                    <div class="loan-dates">
                        <span>📅 Solicitado: <?= date('d/m/Y', strtotime($e['data_emprestimo'])) ?></span>
                        <?php if ($e['data_devolucao']): ?>
                            <span>🗓 Devolução: <?= date('d/m/Y', strtotime($e['data_devolucao'])) ?></span>
                        <?php endif; ?>
                        <?php if ($e['nome_confirmador']): ?>
                            <span>✅ Confirmado por: <?= h($e['nome_confirmador']) ?></span>
                        <?php endif; ?>
                    </div>

                    <span class="loan-status status-badge-<?= $e['status'] ?>">
                        <?= $statusLabel[$e['status']] ?? $e['status'] ?>
                    </span>
                </div>

                <!-- Ações por papel e status -->
                <div class="loan-action" style="display:flex;flex-direction:column;gap:.5rem">

                    <?php if (ehBibliotecario() && $e['status'] === 'pendente'): ?>
                        <a href="emprestimos.php?action=confirmar&id=<?= $e['id'] ?>"
                           class="btn-primary btn-sm-txt"
                           onclick="return confirm('Confirmar este empréstimo?')">
                            ✅ Confirmar
                        </a>
                        <a href="emprestimos.php?action=rejeitar&id=<?= $e['id'] ?>"
                           class="btn-ghost btn-sm-txt"
                           onclick="return confirm('Rejeitar esta solicitação?')">
                            🚫 Rejeitar
                        </a>
                    <?php endif; ?>

                    <?php if (ehBibliotecario() && in_array($e['status'], ['ativo','atrasado'])): ?>
                        <a href="emprestimos.php?action=devolver&id=<?= $e['id'] ?>"
                           class="btn-primary btn-sm-txt"
                           onclick="return confirm('Registrar devolução?')">
                            📥 Devolver
                        </a>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

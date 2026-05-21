<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();

$msg     = '';
$msgTipo = '';

// ─── Bibliotecário: alterar tipo de outro usuário ────────────
if (ehBibliotecario() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $targetId = (int)($_POST['usuario_id'] ?? 0);

    if ($action === 'alterar_tipo' && $targetId && $targetId !== $uid) {
        $novoTipo = $_POST['tipo'] ?? 'aluno';
        if (!in_array($novoTipo, ['aluno', 'bibliotecario'])) $novoTipo = 'aluno';
        $pdo->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?")
            ->execute([$novoTipo, $targetId]);
        $msg     = 'Tipo de usuário atualizado.';
        $msgTipo = 'sucesso';
    }
}

// ─── Dados do usuário logado ─────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

// Estatísticas do usuário logado
$totalFavs = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE id_usuario = ?");
$totalFavs->execute([$uid]);
$qtdFavs = $totalFavs->fetchColumn();

$totalLidos = $pdo->prepare("SELECT COUNT(*) FROM lidos WHERE id_usuario = ?");
$totalLidos->execute([$uid]);
$qtdLidos = $totalLidos->fetchColumn();

$totalEmp = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ?");
$totalEmp->execute([$uid]);
$qtdEmp = $totalEmp->fetchColumn();

$empAtivos = $pdo->prepare(
    "SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status IN ('ativo','atrasado')"
);
$empAtivos->execute([$uid]);
$qtdAtivos = $empAtivos->fetchColumn();

$empPendentes = $pdo->prepare(
    "SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status = 'pendente'"
);
$empPendentes->execute([$uid]);
$qtdPendentes = $empPendentes->fetchColumn();

// ─── Bibliotecário: listar todos os usuários ─────────────────
$todosUsuarios = [];
if (ehBibliotecario()) {
    $todosUsuarios = $pdo->query(
        "SELECT u.id, u.nome, u.email, u.tipo, u.criado_em,
                COUNT(DISTINCT e.id) AS total_emp
         FROM usuarios u
         LEFT JOIN emprestimos e ON u.id = e.id_usuario AND e.status IN ('ativo','pendente','atrasado')
         GROUP BY u.id
         ORDER BY u.nome ASC"
    )->fetchAll();
}
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
            <h1 class="page-title">
                <?= ehBibliotecario() ? '🏛 Painel do Bibliotecário' : 'Meu Perfil' ?>
            </h1>
            <p class="page-subtitle">
                <?= ehBibliotecario() ? 'Gerencie usuários e acompanhe o sistema' : 'Sua conta na biblioteca' ?>
            </p>
        </div>
    </div>

    <main class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgTipo ?>"><?= h($msg) ?></div>
        <?php endif; ?>

        <div class="profile-layout">
            <!-- Card do perfil -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <?= mb_strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
                </div>
                <h2 class="profile-name"><?= h($usuario['nome']) ?></h2>
                <p class="profile-email"><?= h($usuario['email']) ?></p>
                <p class="profile-since">
                    <?= $usuario['tipo'] === 'bibliotecario' ? '🏛 Bibliotecário' : '🎓 Aluno' ?><br>
                    Membro desde <?= date('d/m/Y', strtotime($usuario['criado_em'])) ?>
                </p>
            </div>

            <!-- Estatísticas -->
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
                <?php if (ehAluno()): ?>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-num"><?= $qtdPendentes ?></div>
                    <div class="stat-label">Pendentes</div>
                    <a href="emprestimos.php" class="stat-link">Acompanhar →</a>
                </div>
                <?php else: ?>
                <div class="stat-card">
                    <div class="stat-icon">📗</div>
                    <div class="stat-num"><?= $qtdAtivos ?></div>
                    <div class="stat-label">Em andamento</div>
                    <a href="emprestimos.php" class="stat-link">Gerenciar →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Painel de usuários (só bibliotecário) ──────────── -->
        <?php if (ehBibliotecario() && !empty($todosUsuarios)): ?>
        <div class="card" style="margin-top:2rem">
            <h2 style="margin-bottom:1.5rem">👥 Usuários Cadastrados (<?= count($todosUsuarios) ?>)</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Emp. ativos</th>
                            <th>Desde</th>
                            <th>Alterar tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todosUsuarios as $u): ?>
                        <tr>
                            <td><strong><?= h($u['nome']) ?></strong></td>
                            <td><?= h($u['email']) ?></td>
                            <td>
                                <span class="tag">
                                    <?= $u['tipo'] === 'bibliotecario' ? '🏛 Bibliotecário' : '🎓 Aluno' ?>
                                </span>
                            </td>
                            <td><?= $u['total_emp'] ?></td>
                            <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
                            <td>
                                <?php if ($u['id'] !== $uid): ?>
                                <form method="POST" style="display:flex;gap:.5rem;align-items:center">
                                    <input type="hidden" name="action"     value="alterar_tipo">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <select name="tipo"
                                            style="padding:.3rem .6rem;border:1px solid var(--border);border-radius:6px;background:var(--bg);color:var(--text);font-size:.85rem">
                                        <option value="aluno"         <?= $u['tipo']==='aluno'         ? 'selected':'' ?>>Aluno</option>
                                        <option value="bibliotecario" <?= $u['tipo']==='bibliotecario' ? 'selected':'' ?>>Bibliotecário</option>
                                    </select>
                                    <button type="submit" class="btn-sm btn-edit"
                                            onclick="return confirm('Alterar tipo deste usuário?')">✔</button>
                                </form>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:.85rem">(você)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:2rem">
            <a href="logout.php" class="btn-ghost"
               onclick="return confirm('Deseja sair da sua conta?')">
                Sair
            </a>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

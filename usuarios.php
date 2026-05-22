<?php
require_once 'conexao.php';
requerLogin();

$uid = $_SESSION['usuario_id'];
$pdo = getConexao();
$msg     = '';
$msgTipo = '';

// ─── Bibliotecário: alterar tipo de usuário ──────────────────
if (ehBibliotecario() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $targetId = (int)($_POST['usuario_id'] ?? 0);

    if ($action === 'alterar_tipo' && $targetId && $targetId !== $uid) {
        $novoTipo = in_array($_POST['tipo'] ?? '', ['aluno','bibliotecario'])
                    ? $_POST['tipo'] : 'aluno';
        $pdo->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?")
            ->execute([$novoTipo, $targetId]);
        $msg     = 'Tipo de usuário atualizado com sucesso.';
        $msgTipo = 'sucesso';
    }
}

// ─── Dados do usuário logado ─────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$usuario = $stmt->fetch();

// ─── Estatísticas pessoais ───────────────────────────────────
$qtdFavs = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE id_usuario = ?");
$qtdFavs->execute([$uid]);
$qtdFavs = (int)$qtdFavs->fetchColumn();

$qtdLidos = $pdo->prepare("SELECT COUNT(*) FROM lidos WHERE id_usuario = ?");
$qtdLidos->execute([$uid]);
$qtdLidos = (int)$qtdLidos->fetchColumn();

$qtdEmp = $pdo->prepare("SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ?");
$qtdEmp->execute([$uid]);
$qtdEmp = (int)$qtdEmp->fetchColumn();

$qtdAtivos = $pdo->prepare(
    "SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status IN ('ativo','atrasado')"
);
$qtdAtivos->execute([$uid]);
$qtdAtivos = (int)$qtdAtivos->fetchColumn();

$qtdPendentes = $pdo->prepare(
    "SELECT COUNT(*) FROM emprestimos WHERE id_usuario = ? AND status = 'pendente'"
);
$qtdPendentes->execute([$uid]);
$qtdPendentes = (int)$qtdPendentes->fetchColumn();

// ─── Dados globais (só bibliotecário) ────────────────────────
if (ehBibliotecario()) {

    // Contadores do sistema
    $totalUsuarios   = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalLivros     = (int)$pdo->query("SELECT COUNT(*) FROM livros")->fetchColumn();
    $totalEstoque    = (int)$pdo->query("SELECT SUM(quantidade) FROM livros")->fetchColumn();
    $totalPendentes  = (int)$pdo->query("SELECT COUNT(*) FROM emprestimos WHERE status='pendente'")->fetchColumn();
    $totalAtivos     = (int)$pdo->query("SELECT COUNT(*) FROM emprestimos WHERE status='ativo'")->fetchColumn();
    $totalAtrasados  = (int)$pdo->query("SELECT COUNT(*) FROM emprestimos WHERE status='atrasado'")->fetchColumn();
    $totalReservas   = (int)$pdo->query("SELECT COUNT(*) FROM reservas WHERE status='ativa'")->fetchColumn();

    // Lista de todos os usuários — GROUP BY correto (listar colunas explicitamente)
    $todosUsuarios = $pdo->query("
        SELECT
            u.id,
            u.nome,
            u.email,
            u.tipo,
            u.criado_em,
            (SELECT COUNT(*) FROM emprestimos e
             WHERE e.id_usuario = u.id
               AND e.status IN ('ativo','pendente','atrasado')) AS emp_ativos
        FROM usuarios u
        ORDER BY u.nome ASC
    ")->fetchAll();

    // Empréstimos atrasados (para alerta)
    $atrasados = $pdo->query("
        SELECT e.id, e.data_devolucao, l.titulo, u.nome AS nome_usuario, u.email
        FROM emprestimos e
        INNER JOIN livros   l ON e.id_livro   = l.id
        INNER JOIN usuarios u ON e.id_usuario = u.id
        WHERE e.status = 'atrasado'
        ORDER BY e.data_devolucao ASC
    ")->fetchAll();

    // Livros com estoque zero
    $semEstoque = $pdo->query("
        SELECT id, titulo, autor, categoria
        FROM livros
        WHERE quantidade = 0
        ORDER BY titulo ASC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ehBibliotecario() ? 'Painel Admin' : 'Meu Perfil' ?> — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">
                <?= ehBibliotecario() ? '🏛 Painel do Bibliotecário' : '👤 Meu Perfil' ?>
            </h1>
            <p class="page-subtitle">
                <?= ehBibliotecario()
                    ? 'Visão geral do sistema, usuários e alertas'
                    : 'Sua conta na biblioteca' ?>
            </p>
        </div>
    </div>

    <main class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgTipo ?>"><?= h($msg) ?></div>
        <?php endif; ?>

        <?= exibirFlash() ?>

        <!-- ── Perfil do usuário logado ── -->
        <div class="profile-layout">
            <div class="profile-card">
                <div class="profile-avatar">
                    <?= mb_strtoupper(mb_substr($usuario['nome'], 0, 1)) ?>
                </div>
                <h2 class="profile-name"><?= h($usuario['nome']) ?></h2>
                <p class="profile-email"><?= h($usuario['email']) ?></p>
                <p class="profile-since">
                    <strong><?= $usuario['tipo'] === 'bibliotecario' ? '🏛 Bibliotecário' : '🎓 Aluno' ?></strong><br>
                    Membro desde <?= date('d/m/Y', strtotime($usuario['criado_em'])) ?>
                </p>
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
                    <a href="emprestimos.php" class="stat-link">Ver histórico →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-num"><?= $qtdPendentes ?></div>
                    <div class="stat-label">Pendentes</div>
                    <a href="emprestimos.php" class="stat-link">
                        <?= ehBibliotecario() ? 'Confirmar →' : 'Acompanhar →' ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════
             ÁREA EXCLUSIVA DO BIBLIOTECÁRIO
        ════════════════════════════════════════════════════ -->
        <?php if (ehBibliotecario()): ?>

        <!-- Painel de números globais -->
        <div class="card" style="margin-top:2rem">
            <h2 style="margin-bottom:1.5rem">📊 Visão Geral do Sistema</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-num"><?= $totalUsuarios ?></div>
                    <div class="stat-label">Usuários</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-num"><?= $totalLivros ?></div>
                    <div class="stat-label">Títulos</div>
                    <a href="livros.php" class="stat-link">Gerenciar →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🗃</div>
                    <div class="stat-num"><?= $totalEstoque ?></div>
                    <div class="stat-label">Exemplares</div>
                </div>
                <div class="stat-card" style="<?= $totalPendentes > 0 ? 'border-color:#e67e22' : '' ?>">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-num" style="<?= $totalPendentes > 0 ? 'color:#e67e22' : '' ?>">
                        <?= $totalPendentes ?>
                    </div>
                    <div class="stat-label">Aguardando</div>
                    <a href="emprestimos.php" class="stat-link">Confirmar →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📗</div>
                    <div class="stat-num"><?= $totalAtivos ?></div>
                    <div class="stat-label">Ativos</div>
                    <a href="emprestimos.php" class="stat-link">Ver →</a>
                </div>
                <div class="stat-card" style="<?= $totalAtrasados > 0 ? 'border-color:#c0392b' : '' ?>">
                    <div class="stat-icon">📕</div>
                    <div class="stat-num" style="<?= $totalAtrasados > 0 ? 'color:#c0392b' : '' ?>">
                        <?= $totalAtrasados ?>
                    </div>
                    <div class="stat-label">Atrasados</div>
                    <a href="emprestimos.php" class="stat-link">Resolver →</a>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔖</div>
                    <div class="stat-num"><?= $totalReservas ?></div>
                    <div class="stat-label">Reservas</div>
                    <a href="reservas.php" class="stat-link">Gerenciar →</a>
                </div>
                <div class="stat-card" style="<?= count($semEstoque) > 0 ? 'border-color:#95a5a6' : '' ?>">
                    <div class="stat-icon">📭</div>
                    <div class="stat-num"><?= count($semEstoque) ?></div>
                    <div class="stat-label">Sem estoque</div>
                    <a href="livros.php" class="stat-link">Ver títulos →</a>
                </div>
            </div>
        </div>

        <!-- Alerta de atrasos -->
        <?php if (!empty($atrasados)): ?>
        <div class="card" style="margin-top:2rem;border-left:4px solid #c0392b">
            <h2 style="margin-bottom:1rem;color:#c0392b">📕 Empréstimos Atrasados (<?= count($atrasados) ?>)</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Email</th>
                            <th>Devolver até</th>
                            <th>Atraso</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atrasados as $a): ?>
                        <?php
                            $dias = (int)floor((time() - strtotime($a['data_devolucao'])) / 86400);
                        ?>
                        <tr>
                            <td><strong><?= h($a['titulo']) ?></strong></td>
                            <td><?= h($a['nome_usuario']) ?></td>
                            <td><?= h($a['email']) ?></td>
                            <td style="color:#c0392b">
                                <?= date('d/m/Y', strtotime($a['data_devolucao'])) ?>
                            </td>
                            <td>
                                <span style="background:#c0392b;color:#fff;padding:.2rem .6rem;border-radius:12px;font-size:.8rem;font-weight:600">
                                    <?= $dias ?> dia<?= $dias !== 1 ? 's' : '' ?>
                                </span>
                            </td>
                            <td>
                                <a href="emprestimos.php?action=devolver&id=<?= $a['id'] ?>"
                                   class="btn-sm btn-edit"
                                   onclick="return confirm('Registrar devolução deste livro?')">
                                    📥 Devolver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Livros sem estoque -->
        <?php if (!empty($semEstoque)): ?>
        <div class="card" style="margin-top:2rem;border-left:4px solid #7f8c8d">
            <h2 style="margin-bottom:1rem;color:#7f8c8d">📭 Títulos sem Exemplares (<?= count($semEstoque) ?>)</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Título</th><th>Autor</th><th>Categoria</th><th>Ação</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($semEstoque as $l): ?>
                        <tr>
                            <td><strong><?= h($l['titulo']) ?></strong></td>
                            <td><?= h($l['autor']) ?></td>
                            <td><span class="tag"><?= h($l['categoria']) ?></span></td>
                            <td>
                                <a href="livros.php?editar=<?= $l['id'] ?>" class="btn-sm btn-edit">
                                    ✏ Editar estoque
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gestão de usuários -->
        <div class="card" style="margin-top:2rem">
            <h2 style="margin-bottom:1.5rem">👥 Usuários Cadastrados (<?= count($todosUsuarios) ?>)</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Emp. em aberto</th>
                            <th>Cadastrado em</th>
                            <th>Alterar tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todosUsuarios as $u): ?>
                        <tr>
                            <td><strong><?= h($u['nome']) ?></strong></td>
                            <td><?= h($u['email']) ?></td>
                            <td>
                                <span class="tag" style="<?= $u['tipo']==='bibliotecario' ? 'background:var(--accent,#c8a96e);color:#fff' : '' ?>">
                                    <?= $u['tipo'] === 'bibliotecario' ? '🏛 Bibliotecário' : '🎓 Aluno' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['emp_ativos'] > 0): ?>
                                    <span style="background:#e67e22;color:#fff;padding:.2rem .5rem;border-radius:10px;font-size:.8rem">
                                        <?= $u['emp_ativos'] ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted)">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['criado_em'])) ?></td>
                            <td>
                                <?php if ($u['id'] !== $uid): ?>
                                <form method="POST" style="display:flex;gap:.4rem;align-items:center">
                                    <input type="hidden" name="action"     value="alterar_tipo">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <select name="tipo"
                                            style="padding:.25rem .5rem;border:1px solid var(--border);
                                                   border-radius:6px;background:var(--bg);
                                                   color:var(--text);font-size:.82rem">
                                        <option value="aluno"         <?= $u['tipo']==='aluno'         ? 'selected':'' ?>>Aluno</option>
                                        <option value="bibliotecario" <?= $u['tipo']==='bibliotecario' ? 'selected':'' ?>>Bibliotecário</option>
                                    </select>
                                    <button type="submit" class="btn-sm btn-edit"
                                            onclick="return confirm('Alterar tipo deste usuário?')">✔</button>
                                </form>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:.82rem">(sua conta)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; /* fim bloco bibliotecário */ ?>

        <div style="text-align:center;margin-top:2rem">
            <a href="logout.php" class="btn-ghost"
               onclick="return confirm('Deseja sair da conta de <?= h(addslashes($_SESSION['usuario_nome'] ?? '')) ?>?')">
                ⎋ Sair da conta
            </a>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

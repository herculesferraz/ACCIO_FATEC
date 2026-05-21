<?php
require_once 'conexao.php';
requerBibliotecario(); // Apenas bibliotecários

$pdo     = getConexao();
$msg     = '';
$msgTipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'criar' || $action === 'editar') {
        $titulo    = trim($_POST['titulo']    ?? '');
        $autor     = trim($_POST['autor']     ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $sinopse   = trim($_POST['sinopse']   ?? '');
        $qtd       = max(0, (int)($_POST['quantidade']     ?? 1));
        $ano       = (int)($_POST['ano_publicacao'] ?? 0) ?: null;
        $capa      = trim($_POST['capa_url']  ?? '');
        $pdf       = trim($_POST['pdf_url']   ?? ''); // campo novo

        if (empty($titulo) || empty($autor)) {
            $msg     = 'Título e autor são obrigatórios.';
            $msgTipo = 'erro';
        } else {
            if ($action === 'criar') {
                $stmt = $pdo->prepare(
                    "INSERT INTO livros (titulo, autor, categoria, sinopse, quantidade, ano_publicacao, capa_url, pdf_url)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                $stmt->execute([$titulo, $autor, $categoria, $sinopse, $qtd, $ano, $capa, $pdf]);
                $msg = 'Livro cadastrado com sucesso!';
            } else {
                $eid  = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare(
                    "UPDATE livros
                     SET titulo=?, autor=?, categoria=?, sinopse=?, quantidade=?,
                         ano_publicacao=?, capa_url=?, pdf_url=?
                     WHERE id=?"
                );
                $stmt->execute([$titulo, $autor, $categoria, $sinopse, $qtd, $ano, $capa, $pdf, $eid]);
                $msg = 'Livro atualizado com sucesso!';
            }
            $msgTipo = 'sucesso';
        }
    }

    if ($action === 'excluir') {
        $eid = (int)($_POST['id'] ?? 0);

        // Verificar empréstimos ativos antes de excluir
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM emprestimos WHERE id_livro = ? AND status IN ('ativo','pendente','atrasado')"
        );
        $chk->execute([$eid]);
        if ($chk->fetchColumn() > 0) {
            $msg     = 'Não é possível excluir um livro com empréstimos ativos ou pendentes.';
            $msgTipo = 'erro';
        } else {
            $pdo->prepare("DELETE FROM livros WHERE id=?")->execute([$eid]);
            $msg     = 'Livro excluído.';
            $msgTipo = 'info';
        }
    }
}

$editando = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM livros WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $editando = $stmt->fetch();
}

$busca  = trim($_GET['busca'] ?? '');
$where  = $busca ? "WHERE titulo LIKE ? OR autor LIKE ?" : '';
$params = $busca ? ["%$busca%", "%$busca%"] : [];
$stmt   = $pdo->prepare("SELECT * FROM livros $where ORDER BY titulo ASC");
$stmt->execute($params);
$livros = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Livros — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">⚙ Gerenciar Livros</h1>
            <p class="page-subtitle">Cadastre, edite e gerencie o acervo</p>
        </div>
    </div>

    <main class="container">
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgTipo ?>"><?= h($msg) ?></div>
        <?php endif; ?>

        <div class="card form-card">
            <h2><?= $editando ? '✏ Editar Livro' : '➕ Cadastrar Novo Livro' ?></h2>
            <form method="POST" class="grid-form">
                <input type="hidden" name="action" value="<?= $editando ? 'editar' : 'criar' ?>">
                <?php if ($editando): ?>
                    <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                <?php endif; ?>

                <div class="field-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" required value="<?= h($editando['titulo'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>Autor *</label>
                    <input type="text" name="autor" required value="<?= h($editando['autor'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>Categoria</label>
                    <input type="text" name="categoria" value="<?= h($editando['categoria'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade" min="0" value="<?= h($editando['quantidade'] ?? 1) ?>">
                </div>
                <div class="field-group">
                    <label>Ano de Publicação</label>
                    <input type="number" name="ano_publicacao" min="1000" max="2099"
                           value="<?= h($editando['ano_publicacao'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>URL da Capa</label>
                    <input type="url" name="capa_url" placeholder="https://..."
                           value="<?= h($editando['capa_url'] ?? '') ?>">
                </div>
                <div class="field-group">
                    <label>URL do PDF</label>
                    <input type="url" name="pdf_url" placeholder="https://..."
                           value="<?= h($editando['pdf_url'] ?? '') ?>">
                </div>
                <div class="field-group full-width">
                    <label>Sinopse</label>
                    <textarea name="sinopse" rows="4"><?= h($editando['sinopse'] ?? '') ?></textarea>
                </div>

                <div class="form-buttons full-width">
                    <button type="submit" class="btn-primary"><?= $editando ? 'Salvar Alterações' : 'Cadastrar Livro' ?></button>
                    <?php if ($editando): ?>
                        <a href="livros.php" class="btn-ghost">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top:2rem">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
                <h2>📚 Livros Cadastrados (<?= count($livros) ?>)</h2>
                <form method="GET" style="display:flex;gap:.5rem">
                    <input type="text" name="busca" placeholder="Buscar..."
                           value="<?= h($busca) ?>"
                           style="padding:.5rem 1rem;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text)">
                    <button class="btn-primary" type="submit">🔍</button>
                </form>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Capa</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Categoria</th>
                            <th>Qtd</th>
                            <th>PDF</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($livros as $l): ?>
                        <tr>
                            <td>
                                <?php if ($l['capa_url']): ?>
                                    <img src="<?= h($l['capa_url']) ?>" alt=""
                                         style="width:40px;height:56px;object-fit:cover;border-radius:4px">
                                <?php else: ?>
                                    <span style="font-size:1.5rem">📖</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= h($l['titulo']) ?></strong></td>
                            <td><?= h($l['autor']) ?></td>
                            <td><span class="tag"><?= h($l['categoria']) ?></span></td>
                            <td><?= $l['quantidade'] ?></td>
                            <td>
                                <?php if (!empty($l['pdf_url'])): ?>
                                    <a href="<?= h($l['pdf_url']) ?>" target="_blank" class="btn-sm">📄 PDF</a>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-size:.85rem">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:.5rem">
                                    <a href="livros.php?editar=<?= $l['id'] ?>" class="btn-sm btn-edit">✏</a>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Excluir este livro?')">
                                        <input type="hidden" name="action" value="excluir">
                                        <input type="hidden" name="id"     value="<?= $l['id'] ?>">
                                        <button type="submit" class="btn-sm btn-del">🗑</button>
                                    </form>
                                    <a href="detalhes.php?id=<?= $l['id'] ?>" class="btn-sm">👁</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>

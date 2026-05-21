<?php
require_once 'conexao.php';
requerLogin();

$pdo = getConexao();
$cats = $pdo->query("SELECT DISTINCT categoria FROM livros ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

// AJAX: retornar livro aleatório
if (isset($_GET['ajax']) && $_GET['ajax'] === 'sortear') {
    $cat = $_GET['categoria'] ?? '';
    if ($cat) {
        $stmt = $pdo->prepare("SELECT * FROM livros WHERE categoria = ? AND quantidade > 0 ORDER BY RAND() LIMIT 1");
        $stmt->execute([$cat]);
    } else {
        $stmt = $pdo->query("SELECT * FROM livros WHERE quantidade > 0 ORDER BY RAND() LIMIT 1");
    }
    $livro = $stmt->fetch();
    header('Content-Type: application/json');
    echo json_encode($livro ?: null);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roleta de Sugestões — Biblioteca</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=EB+Garamond:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="page-hero">
        <div class="hero-content">
            <h1 class="page-title">🎲 Roleta Mágica</h1>
            <p class="page-subtitle">Deixe o destino escolher seu próximo livro</p>
        </div>
    </div>

    <main class="container">
        <div class="roulette-container">
            <!-- Controles -->
            <div class="roulette-controls card">
                <h2>✨ Configurar Sorteio</h2>
                <p>Escolha uma categoria ou deixe a magia decidir por você</p>

                <div class="cat-grid">
                    <label class="cat-option">
                        <input type="radio" name="roleta_cat" value="" checked>
                        <span class="cat-label">🌟 Qualquer categoria</span>
                    </label>
                    <?php foreach ($cats as $c): ?>
                    <label class="cat-option">
                        <input type="radio" name="roleta_cat" value="<?= h($c) ?>">
                        <span class="cat-label"><?= h($c) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <button id="btnSortear" class="btn-primary btn-roulette" onclick="sortearLivro()">
                    <span class="roulette-dice" id="rouletteDice">🎲</span>
                    <span>Sortear Livro</span>
                </button>
            </div>

            <!-- Resultado -->
            <div class="roulette-result" id="rouletteResult" style="display:none">
                <div class="result-card card">
                    <div class="result-header">
                        <span class="result-badge">✨ Seu próximo livro é...</span>
                    </div>
                    <div class="result-content">
                        <div class="result-cover">
                            <img id="resultCover" src="" alt="" onerror="this.style.display='none';document.getElementById('resultCoverFallback').style.display='flex'">
                            <div id="resultCoverFallback" class="book-placeholder-lg" style="display:none">📖</div>
                        </div>
                        <div class="result-info">
                            <div id="resultCategory" class="book-category"></div>
                            <h2 id="resultTitle" class="detail-title"></h2>
                            <p id="resultAuthor" class="detail-author"></p>
                            <p id="resultSinopse" class="result-sinopse"></p>
                            <div id="resultMeta" class="detail-meta-grid"></div>
                            <div id="resultActions" class="result-actions"></div>
                        </div>
                    </div>
                </div>

                <div style="text-align:center;margin-top:1.5rem">
                    <button class="btn-ghost" onclick="sortearLivro()">🔄 Sortear outro</button>
                </div>
            </div>

            <!-- Vazio -->
            <div id="rouletteEmpty" style="display:none">
                <div class="empty-state">
                    <div class="empty-icon">😔</div>
                    <h3>Nenhum livro disponível nesta categoria</h3>
                    <p>Tente outra categoria ou aguarde a reposição do acervo.</p>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/script.js"></script>
    <script>
    async function sortearLivro() {
        const cat = document.querySelector('input[name="roleta_cat"]:checked')?.value || '';
        const btn = document.getElementById('btnSortear');
        const dice = document.getElementById('rouletteDice');
        const result = document.getElementById('rouletteResult');
        const empty = document.getElementById('rouletteEmpty');

        // Animação
        btn.disabled = true;
        dice.style.animation = 'spinDice 0.8s linear infinite';
        result.style.display = 'none';
        empty.style.display = 'none';

        await new Promise(r => setTimeout(r, 1200));

        try {
            const resp = await fetch(`roleta.php?ajax=sortear&categoria=${encodeURIComponent(cat)}`);
            const livro = await resp.json();

            dice.style.animation = '';
            btn.disabled = false;

            if (!livro) {
                empty.style.display = 'block';
                return;
            }

            // Preencher resultado
            document.getElementById('resultCategory').textContent = livro.categoria || '';
            document.getElementById('resultTitle').textContent = livro.titulo;
            document.getElementById('resultAuthor').textContent = 'por ' + livro.autor;
            document.getElementById('resultSinopse').textContent = livro.sinopse ? livro.sinopse.substring(0, 200) + '...' : '';

            const coverEl = document.getElementById('resultCover');
            const fallback = document.getElementById('resultCoverFallback');
            if (livro.capa_url) {
                coverEl.src = livro.capa_url;
                coverEl.style.display = '';
                fallback.style.display = 'none';
            } else {
                coverEl.style.display = 'none';
                fallback.style.display = 'flex';
            }

            document.getElementById('resultMeta').innerHTML = `
                <div class="meta-item"><span class="meta-label">Ano</span><span class="meta-val">${livro.ano_publicacao || '—'}</span></div>
                <div class="meta-item"><span class="meta-label">Disponíveis</span><span class="meta-val ${livro.quantidade > 0 ? 'available' : 'unavailable'}">${livro.quantidade} exemplar(es)</span></div>
            `;
            document.getElementById('resultActions').innerHTML = `
                <a href="detalhes.php?id=${livro.id}" class="btn-primary">📖 Ver Detalhes</a>
                ${livro.quantidade > 0 ? `<a href="emprestimos.php?action=novo&livro=${livro.id}" class="btn-secondary">📤 Emprestar</a>` : ''}
            `;

            result.style.display = 'block';
            result.scrollIntoView({ behavior: 'smooth', block: 'start' });

        } catch(e) {
            dice.style.animation = '';
            btn.disabled = false;
            console.error(e);
        }
    }
    </script>
</body>
</html>

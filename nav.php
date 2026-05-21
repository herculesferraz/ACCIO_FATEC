<?php
// includes/nav.php
// Requer que conexao.php já tenha sido incluído na página pai
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <span class="brand-icon">📚</span>
            <span class="brand-name">ACCIOTEK</span>
            <?php if (ehBibliotecario()): ?>
                <span style="font-size:.65rem;background:var(--accent);color:#fff;padding:.1rem .4rem;border-radius:4px;margin-left:.3rem;vertical-align:middle">
                    Bibliotecário
                </span>
            <?php endif; ?>
        </a>

        <div class="nav-links">
            <a href="index.php"       class="nav-link">📖 Catálogo</a>
            <a href="favoritos.php"   class="nav-link">❤ Favoritos</a>
            <a href="lidos.php"       class="nav-link">✅ Lidos</a>
            <a href="emprestimos.php" class="nav-link">
                <?= ehBibliotecario() ? '⚙ Empréstimos' : '📤 Empréstimos' ?>
            </a>
            <a href="reservas.php"    class="nav-link">🔖 Reservas</a>
            <a href="roleta.php"      class="nav-link">🎲 Roleta</a>

            <?php if (ehBibliotecario()): ?>
                <a href="livros.php"   class="nav-link">📋 Livros</a>
            <?php endif; ?>

            <a href="usuarios.php" class="nav-link">👤 <?= h($_SESSION['usuario_nome'] ?? 'Perfil') ?></a>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

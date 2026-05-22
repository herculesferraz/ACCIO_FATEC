<?php
// includes/nav.php — barra de navegação global
// Requer conexao.php já incluído na página pai
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <span class="brand-icon">📚</span>
            <span class="brand-name">ACCIOTEK</span>
            <?php if (ehBibliotecario()): ?>
                <span style="font-size:.62rem;background:var(--accent,#c8a96e);color:#fff;
                             padding:.15rem .45rem;border-radius:4px;margin-left:.4rem;
                             vertical-align:middle;letter-spacing:.03em">
                    BIBLIOTECÁRIO
                </span>
            <?php endif; ?>
        </a>

        <div class="nav-links" id="navLinks">
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
                <a href="usuarios.php" class="nav-link">👥 Usuários</a>
            <?php endif; ?>

            <!-- Perfil + Sair sempre visíveis -->
            <div class="nav-user-group" style="display:inline-flex;align-items:center;gap:.5rem">
                <a href="usuarios.php" class="nav-link" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    👤 <?= h($_SESSION['usuario_nome'] ?? 'Perfil') ?>
                </a>
                <a href="logout.php" class="nav-link nav-link-sair"
                   title="Sair da conta"
                   onclick="return confirm('Deseja sair da conta de <?= h(addslashes($_SESSION['usuario_nome'] ?? '')) ?>?')"
                   style="color:#c0392b;font-weight:600">
                    ⎋ Sair
                </a>
            </div>
        </div>

        <button class="nav-toggle" id="navToggle" aria-label="Abrir menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

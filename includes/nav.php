<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <span class="brand-text">ACCIOTEK</span>
        </a>

        <div class="nav-links" id="navLinks">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                Catálogo
            </a>
            <a href="favoritos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'favoritos.php' ? 'active' : '' ?>">
                Favoritos
            </a>
            <a href="lidos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'lidos.php' ? 'active' : '' ?>">
                Lidos
            </a>
            <a href="emprestimos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'emprestimos.php' ? 'active' : '' ?>">
                Empréstimos
            </a>
            <a href="roleta.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'roleta.php' ? 'active' : '' ?>">
                Roleta
            </a>
            <a href="livros.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'livros.php' ? 'active' : '' ?>">
                Gerenciar
            </a>
        </div>

        <div class="nav-user">
            <button class="theme-toggle" id="themeToggle" title="Alternar tema">🌙</button>
            <div class="user-menu">
                <button class="user-avatar" id="userMenuBtn">
                    <?= mb_strtoupper(mb_substr($_SESSION['usuario_nome'] ?? 'U', 0, 1)) ?>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <strong><?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?></strong>
                    </div>
                    <a href="usuarios.php" class="dropdown-item">👤 Perfil</a>
                    <a href="logout.php" class="dropdown-item logout">🚪 Sair</a>
                </div>
            </div>
            <button class="nav-toggle" id="navToggle">☰</button>
        </div>
    </div>
</nav>

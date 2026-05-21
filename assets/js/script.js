/* ============================================================
   BIBLIOTECA UNIVERSITÁRIA — JAVASCRIPT
   ============================================================ */

// ─── THEME ────────────────────────────────────────────────────
(function initTheme() {
    const saved = localStorage.getItem('biblioteca-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
    updateThemeIcon(saved);
})();

function updateThemeIcon(theme) {
    const btn = document.getElementById('themeToggle');
    if (btn) btn.textContent = theme === 'dark' ? '☀' : '🌙';
}

document.addEventListener('DOMContentLoaded', () => {
    // ─── THEME TOGGLE ───────────────────────────────────────────
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('biblioteca-theme', next);
            updateThemeIcon(next);
        });
    }

    // ─── NAV TOGGLE (MOBILE) ────────────────────────────────────
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
        });
    }

    // ─── USER DROPDOWN ──────────────────────────────────────────
    const userBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', () => {
            userDropdown.classList.remove('open');
        });
    }

    // ─── ANIMATE CARDS IN ───────────────────────────────────────
    const cards = document.querySelectorAll('.book-card, .loan-card, .stat-card');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = `${i * 0.06}s`;
                    entry.target.classList.add('card-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            observer.observe(card);
        });
    }

    // ─── ALERTS AUTO-DISMISS ────────────────────────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ─── AUTH BOOK ANIMATION ────────────────────────────────────
    const bookCover = document.getElementById('bookCover');
    if (bookCover) {
        // Pages effect on hover
        bookCover.addEventListener('mouseenter', () => {
            bookCover.style.transform = 'perspective(1000px) rotateY(-5deg)';
        });
        bookCover.addEventListener('mouseleave', () => {
            bookCover.style.transform = '';
        });
    }
});

// ─── CARD ANIMATION CSS ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const style = document.createElement('style');
    style.textContent = `
        .card-visible {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    `;
    document.head.appendChild(style);
});

// ─── TOGGLE FAVORITO ─────────────────────────────────────────
async function toggleFavorito(livroId, btn) {
    try {
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_favorito&livro_id=${livroId}`
        });
        const data = await resp.json();

        if (data.ok) {
            const isAdded = data.estado === 'adicionado';

            btn.innerHTML = isAdded
                ? '<i class="bi bi-heart-fill text-danger"></i>'
                : '<i class="bi bi-heart"></i>';

            btn.classList.toggle('active', isAdded);

            showToast(
                isAdded
                    ? 'Adicionado aos favoritos!'
                    : 'Removido dos favoritos!'
            );
        }
    } catch (e) {
        showToast('❌ Erro ao processar. Tente novamente.', 'erro');
    }
}

// ─── TOGGLE LIDO ─────────────────────────────────────────────
async function toggleLido(livroId, btn) {
    try {
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_lido&livro_id=${livroId}`
        });

        const data = await resp.json();

        if (data.ok) {
            const isAdded = data.estado === 'adicionado';

            if (btn.classList.contains('btn-action-lg')) {
                btn.innerHTML = isAdded
                    ? '<i class="bi bi-check-lg text-success"></i> Lido'
                    : '<i class="bi bi-eyeglasses"></i> Marcar como lido';
            } else {
                btn.innerHTML = isAdded
                    ? '<i class="bi bi-check-lg text-success"></i>'
                    : '<i class="bi bi-eyeglasses"></i>';
            }

            btn.classList.toggle('active', isAdded);

            showToast(
                isAdded
                    ? '<i class="bi bi-check-lg text-success"></i> Marcado como lido!'
                    : '<i class="bi bi-eyeglasses"></i> Desmarcado como lido'
            );

            // Se na página de lidos, remover card
            if (window.location.pathname.includes('lidos') && !isAdded) {
                const card = btn.closest('.book-card');

                if (card) {
                    card.style.transition = 'opacity 0.4s, transform 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';

                    setTimeout(() => card.remove(), 400);
                }
            }
        }

    } catch (e) {
        showToast(
            '<i class="bi bi-x-circle-fill text-danger"></i> Erro ao processar. Tente novamente.',
            'erro'
        );
    }
}


// ─── TOGGLE EMPRESTAR  ─────────────────────────────────────
async function toggleEmprestado(livroId, btn) {
    try {
        const resp = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_emprestado&livro_id=${livroId}`
        });

        const data = await resp.json();

        if (data.ok) {
            const isAdded = data.estado === 'adicionado';

            btn.innerHTML = isAdded
                ? '<i class="bi bi-bookmark-heart-fill text-success"></i>'
                : '<i class="bi bi-bookmark-plus"></i>';

            btn.classList.toggle('active', isAdded);

            showToast(
                isAdded
                    ? '<i class="bi bi-bookmark-heart-fill text-success"></i> Livro emprestado!'
                    : '<i class="bi bi-bookmark-plus"></i> Empréstimo removido!'
            );
        }

    } catch (e) {
        showToast(
            '<i class="bi bi-x-circle-fill text-danger"></i> Erro ao processar. Tente novamente.',
            'erro'
        );
    }
}
// ─── TOAST NOTIFICATIONS ─────────────────────────────────────
function showToast(msg, tipo = 'sucesso') {
    // Remover toast existente
    const existing = document.getElementById('toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.innerHTML = msg;

    const colors = {
        sucesso: { bg: 'rgba(39,174,96,0.95)', border: '#27ae60' },
        erro: { bg: 'rgba(192,57,43,0.95)', border: '#c0392b' },
        info: { bg: 'rgba(41,128,185,0.95)', border: '#2980b9' }
    };
    const c = colors[tipo] || colors.sucesso;

    Object.assign(toast.style, {
        position: 'fixed',
        bottom: '2rem',
        right: '2rem',
        background: c.bg,
        borderLeft: `4px solid ${c.border}`,
        color: 'white',
        padding: '.9rem 1.5rem',
        borderRadius: '10px',
        boxShadow: '0 8px 30px rgba(0,0,0,0.3)',
        fontFamily: 'EB Garamond, serif',
        fontSize: '1rem',
        zIndex: 9999,
        backdropFilter: 'blur(10px)',
        transform: 'translateX(120%)',
        transition: 'transform 0.4s cubic-bezier(0.34,1.56,0.64,1)',
        maxWidth: '320px',
    });

    document.body.appendChild(toast);
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });
    });

    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// ─── BOOK CARD RIPPLE EFFECT ─────────────────────────────────
document.addEventListener('click', (e) => {
    const card = e.target.closest('.book-card');
    if (!card || e.target.closest('button, a')) return;

    const ripple = document.createElement('div');
    const rect = card.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;

    Object.assign(ripple.style, {
        position: 'absolute',
        width: size + 'px',
        height: size + 'px',
        left: x + 'px',
        top: y + 'px',
        background: 'rgba(252,191,107,0.2)',
        borderRadius: '50%',
        transform: 'scale(0)',
        animation: 'rippleAnim 0.6s ease-out forwards',
        pointerEvents: 'none',
        zIndex: 10,
    });

    card.style.position = 'relative';
    card.style.overflow = 'hidden';
    card.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
});

// Add ripple keyframe
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
@keyframes rippleAnim {
    to { transform: scale(2.5); opacity: 0; }
}
`;
document.head.appendChild(rippleStyle);

// ─── SEARCH DEBOUNCE ─────────────────────────────────────────
const searchInput = document.querySelector('.search-input-wrap input');
if (searchInput) {
    let timeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            // Visual feedback only — form submits on button click
            searchInput.style.borderColor = searchInput.value ? 'var(--gold)' : '';
        }, 200);
    });
}

// ─── SMOOTH PAGE TRANSITIONS ─────────────────────────────────
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript') || link.target === '_blank') return;
    if (href.startsWith('http') && !href.includes(window.location.hostname)) return;

    e.preventDefault();
    document.body.style.transition = 'opacity 0.2s ease';
    document.body.style.opacity = '0';
    setTimeout(() => { window.location.href = href; }, 200);
});

window.addEventListener('pageshow', () => {
    document.body.style.opacity = '1';
});

-- ============================================================
-- MIGRAÇÃO ETAPA 2 — ACCIOTEKA
-- Compatível com MySQL 5.7+ e MySQL 8.0+ (XAMPP)
-- Execute via phpMyAdmin > Importar, ou MySQL CLI
-- ============================================================

USE biblioteca_db;

-- ─────────────────────────────────────────────────────────────
-- 1. Coluna 'tipo' em usuarios
-- ─────────────────────────────────────────────────────────────
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS tipo ENUM('aluno','bibliotecario')
        NOT NULL DEFAULT 'aluno'
        AFTER email;

-- ─────────────────────────────────────────────────────────────
-- 2. Promover conta admin padrão a bibliotecário
-- ─────────────────────────────────────────────────────────────
UPDATE usuarios
    SET tipo = 'bibliotecario'
    WHERE email = 'admin@biblioteca.edu';

-- ─────────────────────────────────────────────────────────────
-- 3. Ampliar ENUM status em emprestimos
-- ─────────────────────────────────────────────────────────────
ALTER TABLE emprestimos
    MODIFY COLUMN status
        ENUM('pendente','ativo','devolvido','atrasado','cancelado')
        NOT NULL DEFAULT 'pendente';

-- ─────────────────────────────────────────────────────────────
-- 4. Coluna confirmado_por em emprestimos
-- ─────────────────────────────────────────────────────────────
ALTER TABLE emprestimos
    ADD COLUMN IF NOT EXISTS confirmado_por INT NULL DEFAULT NULL
        AFTER status;

-- ─────────────────────────────────────────────────────────────
-- 4b. FK fk_confirmado_por — remove se já existir, depois recria
--     Isso evita o erro #1005 "Duplicate key on write or update"
-- ─────────────────────────────────────────────────────────────
SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME        = 'emprestimos'
      AND CONSTRAINT_NAME   = 'fk_confirmado_por'
      AND CONSTRAINT_TYPE   = 'FOREIGN KEY'
);

SET @sql_drop = IF(
    @fk_exists > 0,
    'ALTER TABLE emprestimos DROP FOREIGN KEY fk_confirmado_por',
    'SELECT 1 -- noop'
);
PREPARE stmt_drop FROM @sql_drop;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;

ALTER TABLE emprestimos
    ADD CONSTRAINT fk_confirmado_por
        FOREIGN KEY (confirmado_por) REFERENCES usuarios(id)
        ON DELETE SET NULL;

-- ─────────────────────────────────────────────────────────────
-- 5. Campo pdf_url em livros
-- ─────────────────────────────────────────────────────────────
ALTER TABLE livros
    ADD COLUMN IF NOT EXISTS pdf_url VARCHAR(512) NULL DEFAULT NULL
        AFTER capa_url;

-- ─────────────────────────────────────────────────────────────
-- 6. Tabela de reservas
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reservas (
    id           INT  AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT  NOT NULL,
    id_livro     INT  NOT NULL,
    data_reserva DATE NOT NULL,
    status       ENUM('ativa','cancelada','convertida') NOT NULL DEFAULT 'ativa',
    CONSTRAINT fk_res_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_res_livro   FOREIGN KEY (id_livro)   REFERENCES livros(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índice único para evitar reserva duplicada ativa por usuário/livro
-- (CREATE UNIQUE INDEX IF NOT EXISTS só existe no MySQL 8+;
--  usando ALTER IGNORE para compatibilidade com 5.7)
ALTER IGNORE TABLE reservas
    ADD UNIQUE INDEX uq_reserva_ativa (id_usuario, id_livro, status);

-- ─────────────────────────────────────────────────────────────
-- 7. Conta bibliotecário padrão (se não existir)
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO usuarios (nome, email, senha, tipo)
VALUES (
    'Bibliotecário Admin',
    'biblioteca@acciotek.edu',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'bibliotecario'
);

-- ─────────────────────────────────────────────────────────────
-- Credenciais de acesso padrão após a migração
-- ─────────────────────────────────────────────────────────────
-- Bibliotecário novo : biblioteca@acciotek.edu  | senha: password
-- Admin original     : admin@biblioteca.edu     | senha: password
-- ─────────────────────────────────────────────────────────────

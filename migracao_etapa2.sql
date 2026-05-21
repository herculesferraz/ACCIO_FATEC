-- ============================================================
-- MIGRAÇÃO ETAPA 2 — ACCIOTEKA
-- Execute este arquivo no phpMyAdmin (Importar) ou via MySQL CLI
-- ============================================================

USE biblioteca_db;

-- 1. Adicionar coluna 'tipo' na tabela usuarios
--    'aluno'          = usuário padrão (consulta, solicita empréstimo)
--    'bibliotecario'  = administrador (confirma, devolve, cadastra livros)
ALTER TABLE usuarios
    ADD COLUMN tipo ENUM('aluno', 'bibliotecario') NOT NULL DEFAULT 'aluno'
    AFTER email;

-- 2. Promover o admin padrão a bibliotecário
UPDATE usuarios
    SET tipo = 'bibliotecario'
    WHERE email = 'admin@biblioteca.edu';

-- 3. Adicionar coluna 'status_emprestimo' para o fluxo de aprovação
--    'pendente'   = aluno solicitou, aguarda bibliotecário
--    'ativo'      = confirmado pelo bibliotecário
--    'devolvido'  = devolução registrada
--    'atrasado'   = ativo com data_devolucao vencida
ALTER TABLE emprestimos
    ADD COLUMN confirmado_por INT NULL DEFAULT NULL
    AFTER status,
    ADD CONSTRAINT fk_confirmado_por FOREIGN KEY (confirmado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Atualizar status existentes: empréstimos sem confirmado_por são tratados como 'ativo'
UPDATE emprestimos SET status = 'ativo' WHERE status = 'ativo' AND confirmado_por IS NULL;

-- 4. Criar tabela de reservas
CREATE TABLE IF NOT EXISTS reservas (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario       INT NOT NULL,
    id_livro         INT NOT NULL,
    data_reserva     DATE NOT NULL DEFAULT (CURDATE()),
    status           ENUM('ativa', 'cancelada', 'convertida') NOT NULL DEFAULT 'ativa',
    CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_reserva_livro   FOREIGN KEY (id_livro)   REFERENCES livros(id)   ON DELETE CASCADE,
    UNIQUE KEY uq_reserva_ativa (id_usuario, id_livro, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Garantir que DELETE de livro não quebra integridade
--    (caso não existam as FKs — seguro rodar mesmo que já existam com IF EXISTS)
-- Se quiser adicionar ON DELETE CASCADE nas FKs existentes, faça manualmente via phpMyAdmin.

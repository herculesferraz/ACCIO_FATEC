# 📚 ACCIOTEKA — Sistema Web Completo

**3. Configurar conexão**

```php
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');
```

**4. Acessar o sistema**
```
ANALIZAR!!!
```

**5. Login padrão ADMINISTRADOR/BIBLIOTECARIO**

| Email | Senha |
|--------|-----------|
| admin@biblioteca.edu | password |
---

> **Nota:** Após o primeiro acesso, crie sua própria conta em Cadastro.

---

## 📁 Estrutura do Projeto

```
/biblioteca
│── index.php           ← Catálogo principal
│── login.php           ← Tela de login
│── cadastro.php        ← Cadastro de usuários
│── logout.php          ← Encerrar sessão
│── conexao.php         ← Config banco de dados
│── api.php             ← AJAX (favoritos/lidos)
│── detalhes.php        ← Página detalhada do livro
│── livros.php          ← CRUD completo de livros
│── favoritos.php       ← Lista de favoritos
│── lidos.php           ← Histórico de lidos
│── emprestimos.php     ← Gestão de empréstimos
│── roleta.php          ← Roleta de sugestões
│── usuarios.php        ← Perfil do usuário
│
├── assets/
│   ├── css/style.css   ← Estilos completos
│   └── js/script.js    ← JavaScript interativo
│
├── includes/
│   └── nav.php         ← Barra de navegação
│
└── sql/
    └── biblioteca_db.sql ← Schema + dados de exemplo

```

---

## ✨ Funcionalidades

| Módulo | Descrição |
|--------|-----------|
| 🔐 Login/Cadastro | Autenticação segura com `password_hash()` |
| 📖 Catálogo | Grid de livros com busca e filtros |
| ❤ Favoritos | Adicionar/remover via AJAX |
| ✅ Lidos | Histórico de leitura |
| 📤 Empréstimos | Registrar e devolver com controle de estoque |
| 🎲 Roleta | Sugestão aleatória por categoria |
| ⚙ CRUD Livros | Cadastrar, editar, excluir livros |
| 👤 Perfil | Estatísticas do usuário |
| 🌙 Tema | Alternância claro/escuro persistente |

---

## 🛡 Segurança
- PDO + Prepared Statements (anti-SQL Injection)
- `password_hash()` + `password_verify()`
- `htmlspecialchars()` em todas as saídas
- Controle de sessão PHP
- Validação server-side

---

## 🎨 Design
- **Paleta:** `#FCBF6B` `#A9AD94` `#42302E` `#F6DAAB` `#DABD7B`
- **Fontes:** Cinzel (display) + EB Garamond (corpo)
- **Tema:** Biblioteca clássica / livro mágico medieval
- **Efeitos:** Animação de abertura de livro, hover suave, toasts, ripple

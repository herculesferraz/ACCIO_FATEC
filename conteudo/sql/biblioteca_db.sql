-- ============================================================
-- BIBLIOTECA UNIVERSITÁRIA - SCHEMA COMPLETO
-- ============================================================

CREATE DATABASE IF NOT EXISTS biblioteca_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_db;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Livros
CREATE TABLE IF NOT EXISTS livros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(300) NOT NULL,
    autor VARCHAR(200) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    sinopse TEXT,
    quantidade INT DEFAULT 1,
    ano_publicacao INT,
    capa_url VARCHAR(500),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Empréstimos
CREATE TABLE IF NOT EXISTS emprestimos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_livro INT NOT NULL,
    data_emprestimo DATE NOT NULL,
    data_devolucao DATE,
    status ENUM('ativo','devolvido','atrasado') DEFAULT 'ativo',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_livro) REFERENCES livros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Favoritos
CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_livro INT NOT NULL,
    UNIQUE KEY unique_favorito (id_usuario, id_livro),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_livro) REFERENCES livros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Livros Lidos
CREATE TABLE IF NOT EXISTS lidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_livro INT NOT NULL,
    data_lido DATE NOT NULL,
    UNIQUE KEY unique_lido (id_usuario, id_livro),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_livro) REFERENCES livros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DADOS DE EXEMPLO
-- ============================================================

-- Usuário administrador (senha: admin123)
INSERT INTO usuarios (nome, email, senha) VALUES
('Administrador', 'admin@biblioteca.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Livros de exemplo
INSERT INTO livros (titulo, autor, categoria, sinopse, quantidade, ano_publicacao, capa_url) VALUES
('Dom Casmurro', 'Machado de Assis', 'Literatura Brasileira', 'Narrado pelo próprio Bentinho, que ficou conhecido como Dom Casmurro, o romance conta a história de seu amor por Capitu, a famosa "Capitu dos olhos de ressaca". A obra é considerada um dos maiores clássicos da literatura brasileira.', 5, 1899, 'https://covers.openlibrary.org/b/id/8231992-L.jpg'),
('O Senhor dos Anéis', 'J.R.R. Tolkien', 'Fantasia', 'A épica jornada de Frodo Baggins e seus companheiros para destruir o Um Anel e derrotar o Senhor das Trevas Sauron. Uma das maiores obras da literatura fantástica mundial.', 3, 1954, 'https://covers.openlibrary.org/b/id/8406786-L.jpg'),
('1984', 'George Orwell', 'Ficção Científica', 'Em um futuro distópico, Winston Smith vive sob o regime totalitário do Grande Irmão. A obra é uma das mais importantes reflexões sobre liberdade, controle e resistência já escritas.', 4, 1949, 'https://covers.openlibrary.org/b/id/8575708-L.jpg'),
('O Alquimista', 'Paulo Coelho', 'Filosofia', 'A jornada de Santiago, um jovem pastor andaluz, em busca de um tesouro e do seu "destino pessoal". Uma das obras brasileiras mais lidas no mundo inteiro.', 6, 1988, 'https://covers.openlibrary.org/b/id/8228691-L.jpg'),
('Cem Anos de Solidão', 'Gabriel García Márquez', 'Realismo Mágico', 'A saga da família Buendía ao longo de sete gerações na cidade fictícia de Macondo. Uma das obras mais importantes do realismo mágico e da literatura hispano-americana.', 2, 1967, 'https://covers.openlibrary.org/b/id/8231654-L.jpg'),
('Crime e Castigo', 'Fiódor Dostoiévski', 'Clássico', 'Raskólnikov, um estudante pobre, comete um assassinato e enfrenta a culpa psicológica. Um dos maiores romances da literatura universal sobre moral, culpa e redenção.', 3, 1866, 'https://covers.openlibrary.org/b/id/8228751-L.jpg'),
('Harry Potter e a Pedra Filosofal', 'J.K. Rowling', 'Fantasia', 'Harry Potter descobre que é um bruxo no dia de seu aniversário de 11 anos e inicia sua jornada na Escola de Magia e Bruxaria de Hogwarts. Início de uma das séries mais amadas da história.', 8, 1997, 'https://covers.openlibrary.org/b/id/10110415-L.jpg'),
('A Metamorfose', 'Franz Kafka', 'Clássico', 'Gregor Samsa acorda uma manhã transformado em um gigantesco inseto. A obra explora temas de alienação, identidade e as tensões familiares de forma magistral.', 4, 1915, 'https://covers.openlibrary.org/b/id/8231955-L.jpg'),
('O Pequeno Príncipe', 'Antoine de Saint-Exupéry', 'Filosofia', 'Um piloto perdido no deserto encontra um menino misterioso que lhe conta sobre sua jornada por vários planetas. Uma obra poética sobre amor, amizade e o olhar das crianças.', 7, 1943, 'https://covers.openlibrary.org/b/id/8479715-L.jpg'),
('Memórias Póstumas de Brás Cubas', 'Machado de Assis', 'Literatura Brasileira', 'Narrado por um defunto-autor, o romance é uma reflexão irônica e cínica sobre a vida, a sociedade e a natureza humana. Marco do Realismo brasileiro.', 3, 1881, 'https://covers.openlibrary.org/b/id/8232190-L.jpg'),
('Duna', 'Frank Herbert', 'Ficção Científica', 'No planeta desértico Arrakis, Paul Atreides se torna líder dos Fremen e luta contra forças que controlam a substância mais valiosa do universo. Épico da ficção científica.', 3, 1965, 'https://covers.openlibrary.org/b/id/8388484-L.jpg'),
('A Revolução dos Bichos', 'George Orwell', 'Ficção', 'Uma alegoria política sobre uma revolução de animais contra seus donos humanos que acaba reproduzindo as mesmas estruturas de poder que pretendia abolir.', 5, 1945, 'https://covers.openlibrary.org/b/id/8406995-L.jpg'),
('Orgulho e Preconceito', 'Jane Austen', 'Romance', 'A história do relacionamento entre Elizabeth Bennet e o aristocrático Sr. Darcy, explorando questões de classe social, casamento e moral na Inglaterra do século XIX.', 4, 1813, 'https://covers.openlibrary.org/b/id/8479715-L.jpg'),
('O Hobbit', 'J.R.R. Tolkien', 'Fantasia', 'Bilbo Bolseiro, um hobbit pacato, é convencido pelo mago Gandalf a se juntar a uma companhia de anões em uma aventura épica para reconquistar um tesouro guardado por um dragão.', 5, 1937, 'https://covers.openlibrary.org/b/id/8406779-L.jpg'),
('A Montanha Mágica', 'Thomas Mann', 'Clássico', 'Hans Castorp visita um sanatório nas montanhas suíças e acaba ficando por sete anos. Uma meditação sobre tempo, doença, e as ideias europeias antes da Primeira Guerra Mundial.', 2, 1924, 'https://covers.openlibrary.org/b/id/8479715-L.jpg'),
('Admirável Mundo Novo', 'Aldous Huxley', 'Ficção Científica', 'Em um futuro onde os seres humanos são condicionados desde o nascimento, Bernard Marx questiona os fundamentos dessa sociedade aparentemente perfeita mas profundamente desumana.', 4, 1932, 'https://covers.openlibrary.org/b/id/8575706-L.jpg'),
('O Mestre e Margarida', 'Mikhail Bulgákov', 'Realismo Mágico', 'O diabo visita Moscou soviética acompanhado de séquito bizarro, enquanto dois enredos paralelos — um em Moscou e outro na antiga Jerusalém — se entrelaçam magistralmente.', 3, 1967, 'https://covers.openlibrary.org/b/id/8479715-L.jpg'),
('Grandes Esperanças', 'Charles Dickens', 'Clássico', 'Pip, um órfão de origem humilde, recebe uma herança misteriosa e vai a Londres tentar se tornar um cavalheiro. Uma exploração da ambição, identidade e redenção.', 3, 1861, 'https://covers.openlibrary.org/b/id/8479715-L.jpg');

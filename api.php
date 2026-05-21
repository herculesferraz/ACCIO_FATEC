<?php
require_once 'conexao.php';
requerLogin();

header('Content-Type: application/json');

$uid    = $_SESSION['usuario_id'];
$pdo    = getConexao();
$action = $_POST['action'] ?? '';
$livro  = (int)($_POST['livro_id'] ?? 0);

if (!$livro) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

switch ($action) {
    case 'toggle_favorito':
        $chk = $pdo->prepare("SELECT id FROM favoritos WHERE id_usuario = ? AND id_livro = ?");
        $chk->execute([$uid, $livro]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_livro = ?")->execute([$uid, $livro]);
            echo json_encode(['ok' => true, 'estado' => 'removido']);
        } else {
            $pdo->prepare("INSERT INTO favoritos (id_usuario, id_livro) VALUES (?, ?)")->execute([$uid, $livro]);
            echo json_encode(['ok' => true, 'estado' => 'adicionado']);
        }
        break;

    case 'toggle_lido':
        $chk = $pdo->prepare("SELECT id FROM lidos WHERE id_usuario = ? AND id_livro = ?");
        $chk->execute([$uid, $livro]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM lidos WHERE id_usuario = ? AND id_livro = ?")->execute([$uid, $livro]);
            echo json_encode(['ok' => true, 'estado' => 'removido']);
        } else {
            $pdo->prepare("INSERT INTO lidos (id_usuario, id_livro, data_lido) VALUES (?, ?, CURDATE())")->execute([$uid, $livro]);
            echo json_encode(['ok' => true, 'estado' => 'adicionado']);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Ação inválida']);
}
?>

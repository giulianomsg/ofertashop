<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: categorias.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt->execute([$id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$categoria) {
    header('Location: categorias.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $id]);
        header('Location: categorias.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Categoria</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h2>Editar Categoria</h2>
    <form method="POST">
      <div class="mb-3">
        <label>Nome da Categoria</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($categoria['nome']) ?>" required>
      </div>
      <button type="submit" class="btn btn-primary">Atualizar</button>
      <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>

<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    if (!empty($nome)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
        $stmt->execute([$nome]);
        header('Location: categorias.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Nova Categoria</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h2>Nova Categoria</h2>
    <form method="POST">
      <div class="mb-3">
        <label>Nome da Categoria</label>
        <input type="text" name="nome" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-success">Salvar</button>
      <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>

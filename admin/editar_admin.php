<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: administradores.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: administradores.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($nome) && !empty($email)) {
        if (!empty($senha)) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET nome = ?, email = ?, senha_hash = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET nome = ?, email = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $id]);
        }

        header('Location: administradores.php');
        exit;
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Editar Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h2>Editar Administrador</h2>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= $erro ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label>Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($admin['nome']) ?>" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
      </div>
      <div class="mb-3">
        <label>Nova Senha (deixe em branco para manter)</label>
        <input type="password" name="senha" class="form-control">
      </div>
      <button type="submit" class="btn btn-primary">Atualizar</button>
      <a href="administradores.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>

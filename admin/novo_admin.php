<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$erro = '';

function uploadAdminAvatar(array $file, ?string $existing = null, ?string &$erro = null): ?string
{
    if (empty($file) || empty($file['name'])) {
        return $existing;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Falha no upload do avatar. Tente novamente.';
        return $existing;
    }

    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas, true)) {
        $erro = 'Tipo de arquivo não permitido. Utilize uma imagem JPG, PNG, GIF ou WEBP.';
        return $existing;
    }

    if (!is_dir('../uploads/admins')) {
        mkdir('../uploads/admins', 0755, true);
    }

    $novoNome = uniqid('admin_', true) . '.' . $extensao;
    $destinoFisico = '../uploads/admins/' . $novoNome;

    if (!move_uploaded_file($file['tmp_name'], $destinoFisico)) {
        $erro = 'Não foi possível salvar o avatar enviado.';
        return $existing;
    }

    if ($existing) {
        $caminhoAntigo = dirname(__DIR__) . '/' . ltrim($existing, '/');
        if (is_file($caminhoAntigo)) {
            @unlink($caminhoAntigo);
        }
    }

    return 'uploads/admins/' . $novoNome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($nome) && !empty($email) && !empty($senha)) {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $avatarPath = uploadAdminAvatar($_FILES['avatar'] ?? [], null, $erro); // uploadAdminAvatar cuida de vazio

        if (empty($erro)) {
            $stmt = $pdo->prepare('INSERT INTO admins (nome, email, senha_hash, avatar) VALUES (?, ?, ?, ?)');
            try {
                $stmt->execute([$nome, $email, $hash, $avatarPath]);
                header('Location: administradores.php');
                exit;
            } catch (PDOException $e) {
                $erro = 'Erro ao cadastrar administrador: ' . $e->getMessage();
            }
        }
    } else {
        $erro = 'Preencha todos os campos obrigatórios.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Novo Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h2>Novo Administrador</h2>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Avatar (opcional)</label>
        <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
        <small class="text-muted">Formatos permitidos: JPG, PNG, GIF ou WEBP.</small>
      </div>
      <button type="submit" class="btn btn-success">Salvar</button>
      <a href="administradores.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>

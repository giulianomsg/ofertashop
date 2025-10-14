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

$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: administradores.php');
    exit;
}

$erro = '';

function uploadAdminAvatar(array $file, ?string $existing, ?string &$erro): ?string
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
    $removerAvatar = isset($_POST['remover_avatar']);

    if (!empty($nome) && !empty($email)) {
        $avatarAtual = $admin['avatar'];

        if ($removerAvatar && $avatarAtual) {
            $caminhoFisico = dirname(__DIR__) . '/' . ltrim($avatarAtual, '/');
            if (is_file($caminhoFisico)) {
                @unlink($caminhoFisico);
            }
            $avatarAtual = null;
        }

        $avatarAtual = uploadAdminAvatar($_FILES['avatar'] ?? [], $avatarAtual, $erro);

        if (empty($erro)) {
            if (!empty($senha)) {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE admins SET nome = ?, email = ?, senha_hash = ?, avatar = ? WHERE id = ?');
                $stmt->execute([$nome, $email, $hash, $avatarAtual, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE admins SET nome = ?, email = ?, avatar = ? WHERE id = ?');
                $stmt->execute([$nome, $email, $avatarAtual, $id]);
            }

            header('Location: administradores.php');
            exit;
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
  <title>Editar Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <h2>Editar Administrador</h2>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($admin['nome']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nova Senha (deixe em branco para manter)</label>
        <input type="password" name="senha" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Avatar</label>
        <?php if (!empty($admin['avatar'])): ?>
          <div class="d-flex align-items-center gap-3 mb-2">
            <img src="<?= htmlspecialchars($admin['avatar']) ?>" alt="Avatar atual" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remover_avatar" id="remover_avatar">
              <label class="form-check-label" for="remover_avatar">Remover avatar atual</label>
            </div>
          </div>
        <?php endif; ?>
        <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
        <small class="text-muted">Formatos permitidos: JPG, PNG, GIF ou WEBP.</small>
      </div>
      <button type="submit" class="btn btn-primary">Atualizar</button>
      <a href="administradores.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>
</body>
</html>

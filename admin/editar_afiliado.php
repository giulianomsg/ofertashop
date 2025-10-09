<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM programas_afiliados WHERE id = :id");
$stmt->execute([':id' => $id]);
$afiliado = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$afiliado) {
    echo "Afiliado não encontrado.";
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $icone_path = $afiliado['icone_url'];

    if (!empty($_FILES['icone']['name'])) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $extensao = strtolower(pathinfo($_FILES['icone']['name'], PATHINFO_EXTENSION));

        if (in_array($extensao, $extensoes_permitidas)) {
            $novo_nome = uniqid() . '.' . $extensao;
            $destino = '../uploads/afiliados/' . $novo_nome;

            if (!is_dir('../uploads/afiliados')) {
                mkdir('../uploads/afiliados', 0755, true);
            }

            if (move_uploaded_file($_FILES['icone']['tmp_name'], $destino)) {
                // Remove ícone antigo
                if (!empty($afiliado['icone_url']) && file_exists('../' . $afiliado['icone_url'])) {
                    unlink('../' . $afiliado['icone_url']);
                }
                $icone_path = 'uploads/afiliados/' . $novo_nome;
            } else {
                $msg = "Erro ao enviar novo ícone.";
            }
        } else {
            $msg = "Tipo de arquivo inválido.";
        }
    }

    if ($nome) {
        $stmt = $pdo->prepare("UPDATE programas_afiliados SET nome = :nome, icone_url = :icone WHERE id = :id");
        $stmt->execute([
            ':nome' => $nome,
            ':icone' => $icone_path,
            ':id' => $id
        ]);

        header('Location: afiliados.php');
        exit;
    } else {
        $msg = "O nome é obrigatório.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Afiliado</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .icone-preview {
      width: 80px;
      height: 80px;
      object-fit: contain;
      margin-top: 10px;
      border: 1px solid #ccc;
      padding: 4px;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <h2>Editar Afiliado</h2>

  <?php if ($msg): ?>
    <div class="alert alert-warning"><?= $msg ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="mt-4">
    <div class="mb-3">
      <label for="nome" class="form-label">Nome do Programa de Afiliado</label>
      <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($afiliado['nome']) ?>" required>
    </div>

    <div class="mb-3">
      <label for="icone" class="form-label">Ícone do Site</label>
      <input type="file" name="icone" id="icone" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.svg">
      <?php if ($afiliado['icone_url']): ?>
        <img src="../<?= htmlspecialchars($afiliado['icone_url']) ?>" alt="Ícone atual" class="icone-preview">
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    <a href="afiliados.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>
</body>
</html>

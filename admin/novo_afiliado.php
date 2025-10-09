<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $icone_path = '';

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
                $icone_path = 'uploads/afiliados/' . $novo_nome;
            } else {
                $msg = "Erro ao mover o arquivo enviado.";
            }
        } else {
            $msg = "Tipo de arquivo não permitido. Envie uma imagem válida.";
        }
    }

    if ($nome && $icone_path) {
        $stmt = $pdo->prepare("INSERT INTO programas_afiliados (nome, icone_url) VALUES (:nome, :icone)");
        $stmt->execute([
            ':nome' => $nome,
            ':icone' => $icone_path
        ]);

        header('Location: afiliados.php');
        exit;
    } elseif (!$msg) {
        $msg = "Preencha todos os campos obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Novo Afiliado</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
  <h2>Cadastrar Novo Afiliado</h2>
  
  <?php if ($msg): ?>
    <div class="alert alert-warning"><?= $msg ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="mt-4">
    <div class="mb-3">
      <label for="nome" class="form-label">Nome do Programa de Afiliado</label>
      <input type="text" name="nome" id="nome" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="icone" class="form-label">Ícone do Site (png, jpg, svg, etc)</label>
      <input type="file" name="icone" id="icone" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.svg" required>
    </div>

    <button type="submit" class="btn btn-success">Salvar</button>
    <a href="afiliados.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>
</body>
</html>

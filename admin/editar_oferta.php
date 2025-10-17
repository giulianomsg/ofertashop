<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$adminId = null;
if (!empty($_SESSION['admin_id'])) {
    $adminId = (int) $_SESSION['admin_id'];
} elseif (!empty($_SESSION['admin_nome'])) {
    $adminLookup = $pdo->prepare('SELECT id FROM admins WHERE nome = ? LIMIT 1');
    $adminLookup->execute([$_SESSION['admin_nome']]);
    $adminId = $adminLookup->fetchColumn() ?: null;
    if ($adminId) {
        $_SESSION['admin_id'] = (int) $adminId;
    }
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ofertas.php');
    exit;
}

$id = intval($_GET['id']);
$erro = '';
$sucesso = '';

// Busca oferta
$stmt = $pdo->prepare("SELECT * FROM ofertas WHERE id = ?");
$stmt->execute([$id]);
$oferta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oferta) {
    header('Location: ofertas.php');
    exit;
}

// Busca categorias e afiliados
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$afiliados = $pdo->query("SELECT id, nome FROM programas_afiliados ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Busca imagens atuais
$stmtImgs = $pdo->prepare("SELECT id, caminho FROM imagens_produto WHERE oferta_id = ?");
$stmtImgs->execute([$id]);
$imagens = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao_resumida = trim($_POST['descricao_resumida']);
    $descricao = trim($_POST['descricao']);
    $preco_atual = floatval($_POST['preco_atual']);
    $preco_original = floatval($_POST['preco_original']);
    $link_afiliado = trim($_POST['link_afiliado']);
    $categoria_id = intval($_POST['categoria_id']);
    $programa_id = !empty($_POST['programa_id']) ? intval($_POST['programa_id']) : null;

    if ($titulo && $descricao_resumida && $descricao && $preco_atual && $link_afiliado) {
        $adminIdToSave = $adminId ?? $oferta['admin_id'];

        $stmt = $pdo->prepare("UPDATE ofertas SET
            titulo = ?, descricao_resumida = ?, descricao = ?, preco_atual = ?, preco_original = ?,
            link_afiliado = ?, categoria_id = ?, programa_id = ?, admin_id = ?
            WHERE id = ?");
        $stmt->execute([
            $titulo, $descricao_resumida, $descricao, $preco_atual, $preco_original,
            $link_afiliado, $categoria_id, $programa_id, $adminIdToSave, $id
        ]);

        $oferta['admin_id'] = $adminIdToSave;

        $sucesso = "Oferta atualizada com sucesso!";

        // Upload de novas imagens (se tiver menos de 5)
        if (!empty($_FILES['novas_imagens']['name'][0])) {
            $totalExistentes = count($imagens);
            $restantes = 5 - $totalExistentes;

            $novas = $_FILES['novas_imagens'];
            $permitidos = ['image/jpeg', 'image/png', 'image/webp'];

            $uploadDir = '../uploads/produtos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            for ($i = 0; $i < count($novas['name']) && $restantes > 0; $i++) {
                $tmp = $novas['tmp_name'][$i];
                $type = mime_content_type($tmp);
                if (in_array($type, $permitidos)) {
                    $ext = pathinfo($novas['name'][$i], PATHINFO_EXTENSION);
                    $nome = uniqid('img_', true) . '.' . $ext;
                    $destino = $uploadDir . $nome;
                    if (move_uploaded_file($tmp, $destino)) {
                        $relPath = 'uploads/produtos/' . $nome;
                        $pdo->prepare("INSERT INTO imagens_produto (oferta_id, caminho) VALUES (?, ?)")
                            ->execute([$id, $relPath]);

                        if (!$oferta['imagem_url']) {
                            $pdo->prepare("UPDATE ofertas SET imagem_url = ? WHERE id = ?")
                                ->execute([$relPath, $id]);
                        }

                        $restantes--;
                    }
                }
            }
        }

        // Recarregar imagens atualizadas
        $stmtImgs->execute([$id]);
        $imagens = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}

// Excluir imagem
if (isset($_GET['excluir_img']) && is_numeric($_GET['excluir_img'])) {
    $img_id = intval($_GET['excluir_img']);
    $stmt = $pdo->prepare("SELECT caminho FROM imagens_produto WHERE id = ? AND oferta_id = ?");
    $stmt->execute([$img_id, $id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($img) {
        unlink('../' . $img['caminho']);
        $pdo->prepare("DELETE FROM imagens_produto WHERE id = ?")->execute([$img_id]);

        // Se imagem principal for removida, atualiza
        if ($oferta['imagem_url'] === $img['caminho']) {
            $nova = $pdo->prepare("SELECT caminho FROM imagens_produto WHERE oferta_id = ? ORDER BY id ASC LIMIT 1");
            $nova->execute([$id]);
            $novaImg = $nova->fetchColumn();
            $pdo->prepare("UPDATE ofertas SET imagem_url = ? WHERE id = ?")->execute([$novaImg, $id]);
        }

        header("Location: editar_oferta.php?id=$id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Editar Oferta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .img-thumb {
      height: 100px;
      object-fit: cover;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <h2>Editar Oferta</h2>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= $erro ?></div>
  <?php elseif ($sucesso): ?>
    <div class="alert alert-success"><?= $sucesso ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Título do Produto</label>
      <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($oferta['titulo']) ?>" required>
    </div>

    <div class="mb-3">
      <label>Descrição Resumida</label>
      <textarea name="descricao_resumida" class="form-control" rows="2" maxlength="250" required><?= htmlspecialchars($oferta['descricao_resumida']) ?></textarea>
    </div>

    <div class="mb-3">
      <label>Descrição Completa</label>
      <textarea name="descricao" class="form-control" rows="5" required><?= htmlspecialchars($oferta['descricao']) ?></textarea>
    </div>

    <div class="mb-3">
      <label>Imagens Cadastradas</label><br>
      <div class="d-flex flex-wrap gap-3">
        <?php foreach ($imagens as $img): ?>
          <div class="text-center">
            <img src="../<?= $img['caminho'] ?>" class="img-thumb rounded border" alt="Imagem">
            <br>
            <a href="?id=<?= $id ?>&excluir_img=<?= $img['id'] ?>" class="btn btn-sm btn-outline-danger mt-1"
               onclick="return confirm('Deseja excluir esta imagem?')">Excluir</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (count($imagens) < 5): ?>
      <div class="mb-3">
        <label>Adicionar Novas Imagens (máx. <?= 5 - count($imagens) ?>)</label>
        <input type="file" name="novas_imagens[]" class="form-control" accept="image/*" multiple>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label>Preço Atual</label>
        <input type="number" step="0.01" name="preco_atual" class="form-control" value="<?= $oferta['preco_atual'] ?>" required>
      </div>
      <div class="col-md-6 mb-3">
        <label>Preço Original</label>
        <input type="number" step="0.01" name="preco_original" class="form-control" value="<?= $oferta['preco_original'] ?>">
      </div>
    </div>

    <div class="mb-3">
      <label>Link Afiliado</label>
      <input type="url" name="link_afiliado" class="form-control" value="<?= htmlspecialchars($oferta['link_afiliado']) ?>" required>
    </div>

    <div class="mb-3">
      <label>Categoria</label>
      <select name="categoria_id" class="form-select" required>
        <option value="">Selecione</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $oferta['categoria_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label>Programa de Afiliado</label>
      <select name="programa_id" class="form-select">
        <option value="">Nenhum</option>
        <?php foreach ($afiliados as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id'] == $oferta['programa_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($a['nome']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    <a href="ofertas.php" class="btn btn-secondary">Voltar</a>
  </form>
</div>
</body>
</html>

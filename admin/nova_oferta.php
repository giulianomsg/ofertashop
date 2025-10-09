<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$erro = '';
$sucesso = '';

// Busca categorias e afiliados
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$afiliados = $pdo->query("SELECT id, nome FROM programas_afiliados ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

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
        // Validação básica de imagens
        $imagens = $_FILES['imagens'];
        $imagensValidas = [];
        $permitidos = ['image/jpeg', 'image/png', 'image/webp'];

        if (!empty($imagens['name'][0])) {
            if (count($imagens['name']) > 5) {
                $erro = "Você pode enviar no máximo 5 imagens.";
            } else {
                for ($i = 0; $i < count($imagens['name']); $i++) {
                    $tmp = $imagens['tmp_name'][$i];
                    $type = mime_content_type($tmp);
                    if (in_array($type, $permitidos)) {
                        $imagensValidas[] = [
                            'tmp_name' => $tmp,
                            'name' => $imagens['name'][$i],
                            'type' => $type
                        ];
                    }
                }
            }
        }

        if (!$erro) {
            // Inserir a oferta sem imagem ainda
            $stmt = $pdo->prepare("INSERT INTO ofertas 
                (titulo, descricao_resumida, descricao, imagem_url, preco_atual, preco_original, link_afiliado, categoria_id, programa_id, ativo, created_at)
                VALUES (?, ?, ?, '', ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([
                $titulo, $descricao_resumida, $descricao,
                $preco_atual, $preco_original, $link_afiliado,
                $categoria_id, $programa_id
            ]);

            $oferta_id = $pdo->lastInsertId();

            // Criar pasta de uploads
            $uploadDir = '../uploads/produtos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $caminhos = [];
            foreach ($imagensValidas as $img) {
                $ext = pathinfo($img['name'], PATHINFO_EXTENSION);
                $nomeArquivo = uniqid('img_', true) . '.' . $ext;
                $destino = $uploadDir . $nomeArquivo;

                if (move_uploaded_file($img['tmp_name'], $destino)) {
                    $relPath = 'uploads/produtos/' . $nomeArquivo;
                    $pdo->prepare("INSERT INTO imagens_produto (oferta_id, caminho) VALUES (?, ?)")
                        ->execute([$oferta_id, $relPath]);
                    $caminhos[] = $relPath;
                }
            }

            // Atualizar a imagem principal da oferta
            if (!empty($caminhos[0])) {
                $pdo->prepare("UPDATE ofertas SET imagem_url = ? WHERE id = ?")
                    ->execute([$caminhos[0], $oferta_id]);
            }

            $sucesso = "Oferta cadastrada com sucesso!";
        }
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Nova Oferta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h2>Nova Oferta</h2>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= $erro ?></div>
  <?php elseif ($sucesso): ?>
    <div class="alert alert-success"><?= $sucesso ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Título do Produto</label>
      <input type="text" name="titulo" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Descrição Resumida</label>
      <textarea name="descricao_resumida" class="form-control" rows="2" maxlength="250" required></textarea>
    </div>

    <div class="mb-3">
      <label>Descrição Completa</label>
      <textarea name="descricao" class="form-control" rows="5" required></textarea>
    </div>

    <div class="mb-3">
      <label>Imagens do Produto (máx. 5)</label>
      <input type="file" name="imagens[]" class="form-control" accept="image/*" multiple required>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3">
        <label>Preço Atual</label>
        <input type="number" step="0.01" name="preco_atual" class="form-control" required>
      </div>
      <div class="col-md-6 mb-3">
        <label>Preço Original</label>
        <input type="number" step="0.01" name="preco_original" class="form-control">
      </div>
    </div>

    <div class="mb-3">
      <label>Link Afiliado</label>
      <input type="url" name="link_afiliado" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Categoria</label>
      <select name="categoria_id" class="form-select" required>
        <option value="">Selecione</option>
        <?php foreach ($categorias as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-3">
      <label>Programa de Afiliado</label>
      <select name="programa_id" class="form-select">
        <option value="">Nenhum</option>
        <?php foreach ($afiliados as $a): ?>
          <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Salvar</button>
    <a href="ofertas.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>
</body>
</html>

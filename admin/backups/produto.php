<?php
require 'admin/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

// Busca os dados da oferta
$stmt = $pdo->prepare("
    SELECT o.*, c.nome AS categoria, p.nome AS afiliado_nome, p.icone_url AS afiliado_icone
    FROM ofertas o
    LEFT JOIN categorias c ON o.categoria_id = c.id
    LEFT JOIN programas_afiliados p ON o.programa_id = p.id
    WHERE o.id = ? AND o.ativo = 1
");
$stmt->execute([$id]);
$oferta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oferta) {
    header('Location: index.php');
    exit;
}

// Busca as imagens adicionais
$stmtImgs = $pdo->prepare("SELECT caminho FROM imagens_produto WHERE oferta_id = ?");
$stmtImgs->execute([$id]);
$imagens = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

// Desconto
$precoAtual = floatval($oferta['preco_atual']);
$precoOriginal = floatval($oferta['preco_original']);
$desconto = $precoOriginal > 0 ? round((($precoOriginal - $precoAtual) / $precoOriginal) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($oferta['titulo']) ?> - Oferta Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .produto-container {
      background: #fff;
      border-radius: 8px;
      padding: 2rem;
      box-shadow: 0 0 15px rgba(0,0,0,0.08);
    }
    .preco {
      font-size: 1.8rem;
      color: #28a745;
      font-weight: bold;
    }
    .preco-antigo {
      font-size: 1.1rem;
      color: #999;
      text-decoration: line-through;
    }
    .desconto {
      font-size: 1rem;
      color: #dc3545;
      font-weight: bold;
    }
    .btn-afiliado {
      background-color: #ff5722;
      border-color: #ff5722;
      font-weight: bold;
    }
    .btn-afiliado:hover {
      background-color: #e64a19;
      border-color: #e64a19;
    }
    .afiliado-info {
      margin-top: 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .afiliado-info img {
      width: 30px;
      height: 30px;
      object-fit: contain;
    }
    .carousel-inner img {
      object-fit: contain;
      max-height: 400px;
      cursor: zoom-in;
    }
    .modal-img {
      max-width: 100%;
      height: auto;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">🛒 Oferta Shop</a>
  </div>
</nav>

<main class="container py-5">
  <div class="produto-container row g-4">
    <div class="col-md-6 text-center">
      <?php if ($imagens): ?>
        <div id="carouselProduto" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner">
            <?php foreach ($imagens as $i => $img): ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= htmlspecialchars($img) ?>" class="d-block w-100 rounded" alt="Imagem <?= $i + 1 ?>" onclick="abrirModal(this.src)">
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (count($imagens) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselProduto" data-bs-slide="prev">
              <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselProduto" data-bs-slide="next">
              <span class="carousel-control-next-icon"></span>
            </button>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <img src="<?= htmlspecialchars($oferta['imagem_url']) ?>" class="img-fluid rounded" alt="Produto">
      <?php endif; ?>
    </div>
    <div class="col-md-6">
      <h2><?= htmlspecialchars($oferta['titulo']) ?></h2>
      <p class="mt-3"><?= nl2br(htmlspecialchars($oferta['descricao'])) ?></p>

      <div class="mt-4">
        <div class="preco">R$ <?= number_format($precoAtual, 2, ',', '.') ?></div>
        <?php if ($precoOriginal > $precoAtual): ?>
          <div class="preco-antigo">R$ <?= number_format($precoOriginal, 2, ',', '.') ?></div>
          <div class="desconto">Desconto de <?= $desconto ?>%</div>
        <?php endif; ?>
      </div>

      <div class="mt-4">
        <a href="<?= htmlspecialchars($oferta['link_afiliado']) ?>" target="_blank" class="btn btn-lg btn-afiliado w-100">
          Ver no site parceiro
        </a>

        <?php if ($oferta['afiliado_nome'] && $oferta['afiliado_icone']): ?>
          <div class="afiliado-info mt-3">
            <img src="<?= htmlspecialchars($oferta['afiliado_icone']) ?>" alt="Afiliado">
            <span>Produto via <strong><?= htmlspecialchars($oferta['afiliado_nome']) ?></strong></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<!-- Modal para Zoom -->
<div class="modal fade" id="zoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-white text-center">
      <div class="modal-body">
        <img src="" id="imgZoom" class="modal-img" alt="Zoom">
      </div>
    </div>
  </div>
</div>

<footer class="text-center py-4 mt-5 bg-dark text-white">
  <div class="container">
    <p>&copy; <?= date('Y') ?> Oferta Shop. Todos os direitos reservados.</p>
  </div>
</footer>

<script>
function abrirModal(src) {
  const modal = new bootstrap.Modal(document.getElementById('zoomModal'));
  document.getElementById('imgZoom').src = src;
  modal.show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

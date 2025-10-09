<?php
require 'admin/config.php';

$stmt = $pdo->query("
  SELECT o.*, c.nome AS categoria, p.nome AS afiliado_nome, p.icone_url AS afiliado_icone
  FROM ofertas o
  LEFT JOIN categorias c ON o.categoria_id = c.id
  LEFT JOIN programas_afiliados p ON o.programa_id = p.id
  WHERE o.ativo = 1
  ORDER BY o.created_at DESC
");
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Oferta Shop - As Melhores Ofertas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background-color: #f5f5f5;
      font-family: 'Segoe UI', sans-serif;
    }

    .product-card {
      perspective: 1000px;
      margin-bottom: 30px;
    }

    .product-inner {
      position: relative;
      width: 100%;
      min-height: 400px;
      transition: transform 0.8s;
      transform-style: preserve-3d;
    }

    .product-card:hover .product-inner {
      transform: rotateY(180deg);
    }

    .product-front,
    .product-back {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      backface-visibility: hidden;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      background: #ffffff;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      overflow: hidden;
    }

    .product-back {
      transform: rotateY(180deg);
      background: #f8f9fa;
      font-size: 0.95rem;
    }

    .price {
      font-size: 1.4rem;
      font-weight: 600;
      color: #28a745;
    }

    .old-price {
      text-decoration: line-through;
      color: #888;
      font-size: 0.9rem;
    }

    .discount {
      font-size: 0.9rem;
      color: #dc3545;
    }

    .product-title {
      font-size: 1.1rem;
      font-weight: 600;
      min-height: 2.6rem;
    }

    .btn-success {
      background-color: #ff5722;
      border-color: #ff5722;
    }

    .btn-success:hover {
      background-color: #e64a19;
      border-color: #e64a19;
    }

    .badge-afiliado {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 32px;
      height: 32px;
      background: #fff;
      border-radius: 50%;
      overflow: hidden;
      box-shadow: 0 0 6px rgba(0,0,0,0.1);
    }

    .badge-afiliado img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    footer {
      background: #343a40;
      color: #fff;
    }

    footer a {
      color: #f8f9fa;
      text-decoration: none;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="#">🛒 Oferta Shop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link active" href="#">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Categorias</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Contato</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container py-5">
  <h2 class="text-center mb-4">Ofertas em Destaque</h2>
  <div class="row g-4">
    <?php foreach ($ofertas as $oferta): ?>
      <?php
        $precoAtual = floatval($oferta['preco_atual']);
        $precoOriginal = floatval($oferta['preco_original']);
        $desconto = $precoOriginal > 0 ? round((($precoOriginal - $precoAtual) / $precoOriginal) * 100) : 0;
        $url_produto = 'produto.php?id=' . $oferta['id'];
      ?>
      <div class="col-md-4 col-sm-6">
        <div class="product-card">
          <div class="product-inner">

            <!-- Frente -->
            <div class="product-front position-relative">
              <?php if ($oferta['afiliado_icone']): ?>
                <div class="badge-afiliado">
                  <img src="<?= htmlspecialchars($oferta['afiliado_icone']) ?>" alt="Afiliado">
                </div>
              <?php endif; ?>

              <img src="<?= htmlspecialchars($oferta['imagem_url']) ?>" class="img-fluid mb-3 rounded" alt="Imagem do produto">
              <div class="product-title"><?= htmlspecialchars($oferta['titulo']) ?></div>
              <div class="price">R$ <?= number_format($precoAtual, 2, ',', '.') ?></div>
              <?php if ($precoOriginal > $precoAtual): ?>
                <div class="old-price">R$ <?= number_format($precoOriginal, 2, ',', '.') ?></div>
                <div class="discount">-<?= $desconto ?>%</div>
              <?php endif; ?>
            </div>

            <!-- Verso -->
            <div class="product-back">
              <p><?= nl2br(htmlspecialchars($oferta['descricao_resumida'])) ?></p>
              <a href="<?= $url_produto ?>" class="btn btn-success w-100">Ver Oferta</a>
            </div>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<footer class="text-center py-4">
  <div class="container">
    <p>&copy; <?= date('Y') ?> Oferta Shop. Todos os direitos reservados.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

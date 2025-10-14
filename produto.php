<?php
require 'admin/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);

// Busca a oferta
$stmt = $pdo->prepare("
    SELECT o.*, 
           c.nome AS categoria, 
           p.nome AS afiliado_nome, 
           p.icone_url AS afiliado_icone,
           a.nome AS admin_nome,
           a.avatar_url AS admin_avatar
    FROM ofertas o
    LEFT JOIN categorias c ON o.categoria_id = c.id
    LEFT JOIN programas_afiliados p ON o.programa_id = p.id
    LEFT JOIN admins a ON o.admin_id = a.id
    WHERE o.id = ? AND o.ativo = 1
");
$stmt->execute([$id]);
$oferta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$oferta) {
    header('Location: index.php');
    exit;
}

// Imagens extras
$stmtImgs = $pdo->prepare("SELECT caminho FROM imagens_produto WHERE oferta_id = ?");
$stmtImgs->execute([$id]);
$imagens = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);

// Comentários aprovados
$stmtCom = $pdo->prepare("SELECT nome, comentario, created_at FROM comentarios WHERE aprovado = 1 AND oferta_id = ? ORDER BY created_at DESC");
$stmtCom->execute([$id]);
$comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

// Cálculo de desconto
$precoAtual = floatval($oferta['preco_atual']);
$precoOriginal = floatval($oferta['preco_original']);
$desconto = $precoOriginal > 0 ? round((($precoOriginal - $precoAtual) / $precoOriginal) * 100) : 0;

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$currentUrl = $scheme . '://' . $host . $requestUri;
$descricaoBase = $oferta['descricao_resumida'] ?? '';
$descricaoCompleta = strip_tags($oferta['descricao']);
$metaDescription = $descricaoBase !== '' ? $descricaoBase : $descricaoCompleta;
$metaDescription = trim(preg_replace('/\s+/', ' ', $metaDescription));
if (mb_strlen($metaDescription) > 160) {
    $metaDescription = mb_substr($metaDescription, 0, 157) . '...';
}
$imagemPrincipal = count($imagens) ? $imagens[0] : $oferta['imagem_url'];
if (!preg_match('#^https?://#i', $imagemPrincipal)) {
    $imagemPrincipal = $scheme . '://' . $host . $imagemPrincipal;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <meta name="robots" content="index, follow" />
  <title><?= htmlspecialchars($oferta['titulo']) ?> | Oferta Shop</title>
  <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>" />
  <meta property="og:type" content="product" />
  <meta property="og:title" content="<?= htmlspecialchars($oferta['titulo']) ?> | Oferta Shop" />
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>" />
  <meta property="og:site_name" content="Oferta Shop" />
  <meta property="og:image" content="<?= htmlspecialchars($imagemPrincipal) ?>" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= htmlspecialchars($oferta['titulo']) ?> | Oferta Shop" />
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <meta name="twitter:image" content="<?= htmlspecialchars($imagemPrincipal) ?>" />
  <meta name="theme-color" content="#ff5722" />
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
    .afiliado-info,
    .admin-info {
      margin-top: 1rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .afiliado-info img, .admin-info img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
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
    .comentario {
      background: #fff;
      padding: 1rem;
      border-radius: 5px;
      margin-bottom: 1rem;
      box-shadow: 0 0 5px rgba(0,0,0,0.05);
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
      <?php if (count($imagens)): ?>
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
        <a href="<?= htmlspecialchars($oferta['link_afiliado']) ?>" target="_blank" rel="nofollow noopener" class="btn btn-lg btn-afiliado w-100">
          Ver no site parceiro
        </a>

        <?php if ($oferta['afiliado_nome'] && $oferta['afiliado_icone']): ?>
          <div class="afiliado-info">
            <img src="<?= htmlspecialchars($oferta['afiliado_icone']) ?>" alt="Afiliado">
            <span>Produto via <strong><?= htmlspecialchars($oferta['afiliado_nome']) ?></strong></span>
          </div>
        <?php endif; ?>

        <?php if ($oferta['admin_nome']): ?>
          <div class="admin-info">
            <img src="<?= htmlspecialchars($oferta['admin_avatar']) ?>" alt="Admin">
            <span>Publicado por <strong><?= htmlspecialchars($oferta['admin_nome']) ?></strong></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <hr class="my-5">

  <div class="row">
    <div class="col-lg-8">
      <h4>Comentários (<?= count($comentarios) ?>)</h4>

      <?php foreach ($comentarios as $c): ?>
        <div class="comentario">
          <strong><?= htmlspecialchars($c['nome']) ?></strong> <small class="text-muted"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></small>
          <p><?= nl2br(htmlspecialchars($c['comentario'])) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="col-lg-4">
      <h5>Deixe seu comentário</h5>
      <form action="avaliar.php" method="post">
        <input type="hidden" name="oferta_id" value="<?= $id ?>">
        <div class="mb-2">
          <input type="text" name="nome" class="form-control" placeholder="Seu nome" required>
        </div>
        <div class="mb-2">
          <input type="email" name="email" class="form-control" placeholder="Seu e-mail (opcional)">
        </div>
        <div class="mb-2">
          <textarea name="comentario" class="form-control" rows="4" placeholder="Seu comentário" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100">Enviar comentário</button>
      </form>
      <small class="text-muted">* Seu comentário será publicado após aprovação.</small>
    </div>
  </div>
</main>

<!-- Modal de Zoom -->
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

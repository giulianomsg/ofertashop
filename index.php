<?php
require 'admin/config.php';
require_once __DIR__ . '/admin/includes/price_verification.php';

ensurePriceVerificationSchema($pdo);

function resolvePublicAvatar(?string $path): ?string
{
  if (empty($path)) {
    return null;
  }

  if (preg_match('#^https?://#i', $path)) {
    return $path;
  }

  return '/' . ltrim($path, '/');
}

// Listas auxiliares para filtros
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$programas = $pdo->query('SELECT id, nome FROM programas_afiliados ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);

// Faixa de preços cadastrados
$faixaStmt = $pdo->query('SELECT MIN(preco_atual) AS min_preco, MAX(preco_atual) AS max_preco FROM ofertas WHERE ativo = 1');
$faixaPrecos = $faixaStmt->fetch(PDO::FETCH_ASSOC) ?: ['min_preco' => 0, 'max_preco' => 0];

$filtros = [
  'categoria' => isset($_GET['categoria']) ? (int) $_GET['categoria'] : null,
  'programa' => isset($_GET['programa']) ? (int) $_GET['programa'] : null,
  'preco_min' => isset($_GET['preco_min']) ? (float) str_replace(',', '.', $_GET['preco_min']) : null,
  'preco_max' => isset($_GET['preco_max']) ? (float) str_replace(',', '.', $_GET['preco_max']) : null,
  'desconto_min' => isset($_GET['desconto_min']) ? (int) $_GET['desconto_min'] : null,
  'busca' => isset($_GET['busca']) ? trim($_GET['busca']) : null,
];

// Buscar ofertas com filtros dinâmicos
$sql = "
  SELECT o.*, c.nome AS categoria, p.nome AS afiliado_nome, p.icone_url AS afiliado_icone,
         a.nome AS admin_nome, a.avatar AS admin_avatar,
         COALESCE(vp.melhor_preco, NULL) AS melhor_preco
  FROM ofertas o
  LEFT JOIN categorias c ON o.categoria_id = c.id
  LEFT JOIN programas_afiliados p ON o.programa_id = p.id
  LEFT JOIN admins a ON o.admin_id = a.id
  LEFT JOIN (
      SELECT oferta_id, MIN(preco_encontrado) AS melhor_preco
      FROM verificacoes_preco
      WHERE status = 'ok' AND preco_encontrado IS NOT NULL
      GROUP BY oferta_id
  ) vp ON vp.oferta_id = o.id
  WHERE o.ativo = 1
";

$params = [];

if ($filtros['categoria']) {
  $sql .= ' AND o.categoria_id = :categoria';
  $params[':categoria'] = $filtros['categoria'];
}

if ($filtros['programa']) {
  $sql .= ' AND o.programa_id = :programa';
  $params[':programa'] = $filtros['programa'];
}

if ($filtros['preco_min'] !== null && $filtros['preco_min'] >= 0) {
  $sql .= ' AND o.preco_atual >= :preco_min';
  $params[':preco_min'] = $filtros['preco_min'];
}

if ($filtros['preco_max'] !== null && $filtros['preco_max'] > 0) {
  $sql .= ' AND o.preco_atual <= :preco_max';
  $params[':preco_max'] = $filtros['preco_max'];
}

if ($filtros['busca']) {
  $sql .= ' AND (o.titulo LIKE :busca OR o.descricao LIKE :busca)';
  $params[':busca'] = '%' . $filtros['busca'] . '%';
}

if ($filtros['desconto_min'] !== null && $filtros['desconto_min'] >= 0) {
  $sql .= ' AND (
    CASE WHEN o.preco_original > 0 THEN ((o.preco_original - o.preco_atual) / o.preco_original) * 100 ELSE 0 END
  ) >= :desconto_min';
  $params[':desconto_min'] = $filtros['desconto_min'];
}

$sql .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$currentUrl = $scheme . '://' . $host . $requestUri;
$metaDescription = 'Descubra ofertas imperdíveis e descontos exclusivos em tecnologia, casa, lazer e muito mais no Oferta Shop.';
$ogImagePath = (!empty($ofertas) && !empty($ofertas[0]['imagem_url']))
  ? $ofertas[0]['imagem_url']
  : 'https://via.placeholder.com/1200x630.png?text=Oferta+Shop';

if (!preg_match('#^https?://#i', $ogImagePath)) {
  $ogImagePath = $scheme . '://' . $host . $ogImagePath;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <meta name="robots" content="index, follow" />
  <title>Oferta Shop - As Melhores Ofertas</title>
  <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="Oferta Shop - As Melhores Ofertas" />
  <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>" />
  <meta property="og:site_name" content="Oferta Shop" />
  <meta property="og:image" content="<?= htmlspecialchars($ogImagePath) ?>" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Oferta Shop - As Melhores Ofertas" />
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>" />
  <meta name="twitter:image" content="<?= htmlspecialchars($ogImagePath) ?>" />
  <meta name="theme-color" content="#ff5722" />
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
      height: 100%;
    }

    .product-inner {
      position: relative;
      width: 100%;
      min-height: 430px;
      transition: transform 0.8s;
      transform-style: preserve-3d;
    }

    .product-card:hover .product-inner,
    .product-card:focus-within .product-inner {
      transform: rotateY(180deg);
    }

    .product-front, .product-back {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
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

    .sticky-top.top-5 {
      top: 5.5rem;
    }

    @media (max-width: 991.98px) {
      .sticky-top.top-5 {
        position: static !important;
      }
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

    .product-front {
      padding-bottom: 4.5rem;
    }

    .admin-chip {
      position: absolute;
      bottom: 1rem;
      right: 1rem;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: #ffffff;
      box-shadow: 0 6px 18px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid rgba(255, 255, 255, 0.7);
      z-index: 5;
    }

    .admin-chip img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
    }

    .admin-chip .avatar-placeholder {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, #ff784e, #ff5722);
      font-size: 1.1rem;
    }

    .admin-chip .avatar-placeholder i {
      font-size: 1.35rem;
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
  <div class="row mb-4 align-items-center">
    <div class="col-lg-8">
      <h2 class="mb-1">Ofertas em Destaque</h2>
      <p class="text-muted mb-0">Use os filtros ao lado para encontrar exatamente o que procura.</p>
    </div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">Limpar filtros</a>
    </div>
  </div>

  <div class="row g-4">
    <aside class="col-lg-3">
      <form class="card shadow-sm sticky-top top-5" method="get">
        <div class="card-body">
          <h5 class="card-title">Filtrar ofertas</h5>
          <div class="mb-3">
            <label for="busca" class="form-label">Busca</label>
            <input type="text" id="busca" name="busca" value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>" class="form-control" placeholder="Palavra-chave">
          </div>

          <div class="mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <select id="categoria" name="categoria" class="form-select">
              <option value="">Todas</option>
              <?php foreach ($categorias as $categoria): ?>
                <option value="<?= $categoria['id'] ?>" <?= ($filtros['categoria'] === (int) $categoria['id']) ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="programa" class="form-label">Programa afiliado</label>
            <select id="programa" name="programa" class="form-select">
              <option value="">Todos</option>
              <?php foreach ($programas as $programa): ?>
                <option value="<?= $programa['id'] ?>" <?= ($filtros['programa'] === (int) $programa['id']) ? 'selected' : '' ?>><?= htmlspecialchars($programa['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Faixa de preço (R$)</label>
            <div class="input-group mb-2">
              <span class="input-group-text">mín</span>
              <input type="number" step="0.01" min="0" name="preco_min" class="form-control" value="<?= $filtros['preco_min'] !== null ? htmlspecialchars(number_format($filtros['preco_min'], 2, '.', '')) : '' ?>">
            </div>
            <div class="input-group">
              <span class="input-group-text">máx</span>
              <input type="number" step="0.01" min="0" name="preco_max" class="form-control" value="<?= $filtros['preco_max'] !== null ? htmlspecialchars(number_format($filtros['preco_max'], 2, '.', '')) : '' ?>">
            </div>
            <small class="text-muted d-block mt-1">Intervalo cadastrado: R$ <?= number_format((float) $faixaPrecos['min_preco'], 2, ',', '.') ?> — R$ <?= number_format((float) $faixaPrecos['max_preco'], 2, ',', '.') ?></small>
          </div>

          <div class="mb-3">
            <label for="desconto_min" class="form-label">Desconto mínimo (%)</label>
            <input type="number" id="desconto_min" name="desconto_min" min="0" max="90" step="5" class="form-control" value="<?= $filtros['desconto_min'] !== null ? (int) $filtros['desconto_min'] : '' ?>">
          </div>

          <button type="submit" class="btn btn-success w-100">Aplicar filtros</button>
        </div>
      </form>
    </aside>

    <section class="col-lg-9">
      <?php if (empty($ofertas)): ?>
        <div class="alert alert-info shadow-sm">Nenhuma oferta encontrada com os filtros selecionados. Ajuste os parâmetros e tente novamente.</div>
      <?php else: ?>
        <div class="row g-4">
        <?php foreach ($ofertas as $oferta): ?>
          <?php
            $precoAtual = floatval($oferta['preco_atual']);
            $precoOriginal = floatval($oferta['preco_original']);
            $desconto = $precoOriginal > 0 ? round((($precoOriginal - $precoAtual) / $precoOriginal) * 100) : 0;
            $url_produto = 'produto.php?id=' . $oferta['id'];
            $adminAvatarUrl = resolvePublicAvatar($oferta['admin_avatar'] ?? null);
            $adminInitial = '';
            if (!empty($oferta['admin_nome'])) {
              $firstChar = function_exists('mb_substr')
                ? mb_substr($oferta['admin_nome'], 0, 1, 'UTF-8')
                : substr($oferta['admin_nome'], 0, 1);
              if ($firstChar !== false) {
                $adminInitial = function_exists('mb_strtoupper')
                  ? mb_strtoupper($firstChar, 'UTF-8')
                  : strtoupper($firstChar);
              }
            }
          ?>
          <div class="col-xl-4 col-md-6">
            <div class="product-card h-100">
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

                  <!-- Admin -->
                  <?php if (!empty($oferta['admin_nome'])): ?>
                    <div class="admin-chip" data-bs-toggle="tooltip" data-bs-placement="top" title="Adicionado por <?= htmlspecialchars($oferta['admin_nome']) ?>">
                      <?php if (!empty($adminAvatarUrl)): ?>
                        <img src="<?= htmlspecialchars($adminAvatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($oferta['admin_nome']) ?>">
                      <?php elseif ($adminInitial !== ''): ?>
                        <div class="avatar-placeholder" aria-hidden="true"><?= htmlspecialchars($adminInitial) ?></div>
                      <?php else: ?>
                        <div class="avatar-placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($oferta['melhor_preco']) && $oferta['melhor_preco'] < $precoAtual): ?>
                    <div class="mt-3 alert alert-success py-2 px-3 small">
                      Melhor preço verificado: <strong>R$ <?= number_format((float) $oferta['melhor_preco'], 2, ',', '.') ?></strong>
                    </div>
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
      <?php endif; ?>
    </section>
  </div>
</main>

<footer class="text-center py-4">
  <div class="container">
    <p>&copy; <?= date('Y') ?> Oferta Shop. Todos os direitos reservados.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>
</body>
</html>

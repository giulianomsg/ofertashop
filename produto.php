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

function getAdminInitial(?string $name): string
{
    if (empty($name)) {
        return '';
    }

    $firstChar = function_exists('mb_substr')
        ? mb_substr($name, 0, 1, 'UTF-8')
        : substr($name, 0, 1);

    if ($firstChar === false || $firstChar === '') {
        return '';
    }

    return function_exists('mb_strtoupper')
        ? mb_strtoupper($firstChar, 'UTF-8')
        : strtoupper($firstChar);
}

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
           a.avatar AS admin_avatar
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
$galeriaImagens = $imagens;
if (!$galeriaImagens) {
    $galeriaImagens = [$oferta['imagem_url']];
}
$totalImagens = count($galeriaImagens);

// Comentários aprovados
$stmtCom = $pdo->prepare("SELECT nome, comentario, created_at FROM comentarios WHERE aprovado = 1 AND oferta_id = ? ORDER BY created_at DESC");
$stmtCom->execute([$id]);
$comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

$ultimaVerificacao = getLastPriceVerification($pdo, $id);
$melhorPrecoHistorico = getBestPriceForOffer($pdo, $id);

$comentarioEnviado = isset($_GET['comentario']) && $_GET['comentario'] === 'enviado';

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
$imagemPrincipal = $galeriaImagens[0];
if (!preg_match('#^https?://#i', $imagemPrincipal)) {
    $imagemPrincipal = $scheme . '://' . $host . $imagemPrincipal;
}
$adminInitial = getAdminInitial($oferta['admin_nome'] ?? null);
$adminAvatarUrl = resolvePublicAvatar($oferta['admin_avatar'] ?? null);

$relatedOffers = [];
$categoriaId = $oferta['categoria_id'] ?? null;

if (!empty($categoriaId)) {
    $stmtRelacionadas = $pdo->prepare("SELECT o.id, o.titulo, o.preco_atual, o.preco_original, o.imagem_url, o.link_afiliado, c.nome AS categoria, a.nome AS admin_nome, a.avatar AS admin_avatar FROM ofertas o LEFT JOIN categorias c ON o.categoria_id = c.id LEFT JOIN admins a ON o.admin_id = a.id WHERE o.categoria_id = ? AND o.id <> ? AND o.ativo = 1 ORDER BY o.created_at DESC LIMIT 4");
    $stmtRelacionadas->execute([$categoriaId, $id]);
    $relatedOffers = $stmtRelacionadas->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

if (!$relatedOffers) {
    $stmtRelacionadas = $pdo->prepare("SELECT o.id, o.titulo, o.preco_atual, o.preco_original, o.imagem_url, o.link_afiliado, c.nome AS categoria, a.nome AS admin_nome, a.avatar AS admin_avatar FROM ofertas o LEFT JOIN categorias c ON o.categoria_id = c.id LEFT JOIN admins a ON o.admin_id = a.id WHERE o.id <> ? AND o.ativo = 1 ORDER BY o.created_at DESC LIMIT 4");
    $stmtRelacionadas->execute([$id]);
    $relatedOffers = $stmtRelacionadas->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
    }

    .categoria-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(255, 87, 34, 0.1);
      color: #ff5722;
      padding: 0.35rem 0.75rem;
      border-radius: 999px;
      font-weight: 600;
      margin-bottom: 0.75rem;
      font-size: 0.85rem;
    }

    .categoria-badge i {
      font-size: 1rem;
    }

    .admin-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      margin-bottom: 1rem;
      padding: 0.35rem 0.75rem 0.35rem 0.35rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 8px 18px rgba(0,0,0,0.1);
      border: 1px solid rgba(0,0,0,0.06);
    }

    .admin-badge[data-admin-name] {
      position: relative;
    }

    .admin-badge[data-admin-name]::after {
      content: attr(data-admin-name);
      position: absolute;
      left: 50%;
      bottom: calc(100% + 8px);
      transform: translateX(-50%) translateY(6px);
      background: rgba(33, 37, 41, 0.92);
      color: #fff;
      padding: 0.35rem 0.6rem;
      border-radius: 999px;
      font-size: 0.75rem;
      white-space: nowrap;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease, transform 0.2s ease;
      box-shadow: 0 8px 18px rgba(0,0,0,0.18);
      z-index: 5;
    }

    .admin-badge[data-admin-name]:hover::after,
    .admin-badge[data-admin-name]:focus-visible::after {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .admin-badge .admin-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      overflow: hidden;
      background: linear-gradient(135deg, #ff784e, #ff5722);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 600;
      flex-shrink: 0;
    }

    .admin-badge .admin-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .admin-badge .admin-name {
      font-weight: 600;
      color: #343a40;
      font-size: 0.92rem;
    }
    .produto-galeria {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .galeria-principal {
      position: relative;
      min-height: 320px;
      background: #fff;
      border-radius: 0.75rem;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .galeria-imagem {
      display: none;
      max-height: 420px;
      width: 100%;
      object-fit: contain;
      cursor: zoom-in;
      border-radius: 0.5rem;
      transition: opacity 0.25s ease;
    }

    .galeria-imagem.ativo {
      display: block;
    }

    .galeria-controles {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1.25rem;
    }

    .galeria-controles button {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 1px solid #dee2e6;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
      cursor: pointer;
      transition: background 0.2s ease, transform 0.2s ease;
      color: #ff5722;
      font-size: 1.2rem;
    }

    .galeria-controles button:hover {
      background: #f8f9fa;
      transform: translateY(-2px);
    }

    .galeria-status {
      font-weight: 600;
      color: #495057;
      font-size: 0.95rem;
    }

    .galeria-miniaturas {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      justify-content: center;
    }

    .galeria-miniaturas button {
      border: 2px solid transparent;
      border-radius: 0.6rem;
      padding: 0;
      background: transparent;
      cursor: pointer;
      overflow: hidden;
      transition: border-color 0.2s ease, transform 0.2s ease;
    }

    .galeria-miniaturas button img {
      width: 72px;
      height: 72px;
      object-fit: cover;
      display: block;
    }

    .galeria-miniaturas button.ativo {
      border-color: #ff5722;
      transform: translateY(-2px);
    }

    .zoom-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      z-index: 1080;
    }

    .zoom-overlay.is-visible {
      display: flex;
    }

    .zoom-overlay img {
      max-width: 90vw;
      max-height: 90vh;
      border-radius: 0.75rem;
      box-shadow: 0 25px 60px rgba(0,0,0,0.4);
    }

    .zoom-close {
      position: absolute;
      top: 1.5rem;
      right: 1.5rem;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: none;
      background: rgba(0,0,0,0.7);
      color: #fff;
      font-size: 1.5rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s ease;
    }

    .zoom-close:hover {
      background: rgba(0,0,0,0.85);
    }

    body.no-scroll {
      overflow: hidden;
    }

    .toast-container {
      z-index: 1080;
    }

    .toast {
      opacity: 0;
      transform: translateY(-10px);
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .toast.is-visible {
      opacity: 1;
      transform: translateY(0);
    }

    .toast .btn-close {
      opacity: 0.6;
      transition: opacity 0.2s ease;
    }

    .toast .btn-close:hover {
      opacity: 1;
    }

    .comentario {
      background: #fff;
      padding: 1rem;
      border-radius: 5px;
      margin-bottom: 1rem;
      box-shadow: 0 0 5px rgba(0,0,0,0.05);
    }

    .relacionadas-card {
      background: #ffffff;
      border-radius: 0.75rem;
      overflow: hidden;
      box-shadow: 0 18px 40px rgba(0,0,0,0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .relacionadas-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 22px 50px rgba(0,0,0,0.12);
    }

    .relacionadas-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .relacionadas-card .card-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      flex: 1;
    }

    .relacionadas-card .card-title {
      font-size: 1rem;
      font-weight: 600;
      color: #212529;
    }

    .relacionadas-card .card-price {
      font-weight: 700;
      color: #28a745;
    }

    .relacionadas-card .card-price-old {
      color: #adb5bd;
      text-decoration: line-through;
      font-size: 0.9rem;
    }

    .relacionadas-card .card-discount {
      font-size: 0.85rem;
      color: #dc3545;
      font-weight: 600;
    }

    .relacionadas-card .card-footer {
      padding: 1rem 1.25rem;
      border-top: 1px solid rgba(0,0,0,0.05);
      background: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
    }

    .relacionadas-card .admin-badge {
      margin-bottom: 0;
      box-shadow: none;
      background: rgba(248, 249, 250, 0.95);
      border: 1px solid rgba(0,0,0,0.04);
    }

    @media (max-width: 991.98px) {
      .relacionadas-card img {
        height: 200px;
      }
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
    <div class="col-md-6">
      <div class="produto-galeria">
        <div class="galeria-principal">
          <?php foreach ($galeriaImagens as $i => $img): ?>
            <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" class="galeria-imagem <?= $i === 0 ? 'ativo' : '' ?>" data-index="<?= $i ?>" data-zoom-src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="Imagem <?= $i + 1 ?>" aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
          <?php endforeach; ?>
        </div>
        <?php if ($totalImagens > 1): ?>
          <div class="galeria-controles">
            <button type="button" class="galeria-prev" aria-label="Imagem anterior">
              <i class="bi bi-chevron-left"></i>
            </button>
            <div class="galeria-status"><span class="galeria-atual">1</span> / <?= $totalImagens ?></div>
            <button type="button" class="galeria-next" aria-label="Próxima imagem">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
          <div class="galeria-miniaturas">
            <?php foreach ($galeriaImagens as $i => $img): ?>
              <button type="button" class="miniatura <?= $i === 0 ? 'ativo' : '' ?>" data-index="<?= $i ?>" aria-label="Mostrar imagem <?= $i + 1 ?>">
                <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="Miniatura <?= $i + 1 ?>">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-md-6">
      <?php if (!empty($oferta['categoria'])): ?>
        <div class="categoria-badge"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($oferta['categoria']) ?></div>
      <?php endif; ?>

      <?php if (!empty($oferta['admin_nome'])): ?>
        <div class="admin-badge" data-admin-name="<?= htmlspecialchars($oferta['admin_nome']) ?>" title="Oferta cadastrada por <?= htmlspecialchars($oferta['admin_nome']) ?>" aria-label="Oferta cadastrada por <?= htmlspecialchars($oferta['admin_nome']) ?>">
          <div class="admin-avatar">
            <?php if (!empty($adminAvatarUrl)): ?>
              <img src="<?= htmlspecialchars($adminAvatarUrl) ?>" alt="Avatar de <?= htmlspecialchars($oferta['admin_nome']) ?>">
            <?php elseif ($adminInitial !== ''): ?>
              <?= htmlspecialchars($adminInitial) ?>
            <?php else: ?>
              <i class="bi bi-person-fill"></i>
            <?php endif; ?>
          </div>
          <span class="admin-name"><?= htmlspecialchars($oferta['admin_nome']) ?></span>
        </div>
      <?php endif; ?>
      <h2 class="mb-3"><?= htmlspecialchars($oferta['titulo']) ?></h2>
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
      </div>

      <div class="mt-3">
        <?php if ($ultimaVerificacao): ?>
          <?php $diferenca = $ultimaVerificacao['diferenca'] !== null ? (float) $ultimaVerificacao['diferenca'] : null; ?>
          <div class="alert <?= $ultimaVerificacao['status'] === 'ok' ? 'alert-success' : 'alert-warning' ?> mb-0">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>Verificação de preço</strong>
              <small class="text-muted"><?= date('d/m/Y H:i', strtotime($ultimaVerificacao['verificado_em'])) ?></small>
            </div>
            <?php if ($ultimaVerificacao['status'] === 'ok' && $ultimaVerificacao['preco_encontrado'] !== null): ?>
              <p class="mb-1">Preço encontrado: <strong>R$ <?= number_format((float) $ultimaVerificacao['preco_encontrado'], 2, ',', '.') ?></strong></p>
              <?php if ($diferenca !== null && $diferenca !== 0.0): ?>
                <p class="mb-1">Diferença em relação ao cadastro: <strong><?= $diferenca > 0 ? '+' : '' ?>R$ <?= number_format($diferenca, 2, ',', '.') ?></strong></p>
              <?php endif; ?>
            <?php endif; ?>
            <p class="mb-0 text-muted"><?= htmlspecialchars($ultimaVerificacao['mensagem'] ?? 'Verificação registrada.') ?></p>
            <?php if ($melhorPrecoHistorico !== null): ?>
              <p class="mb-0 mt-2">Melhor preço registrado: <strong>R$ <?= number_format($melhorPrecoHistorico, 2, ',', '.') ?></strong></p>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-info mb-0">
            Nenhuma verificação automática de preço foi realizada para esta oferta até o momento.
          </div>
        <?php endif; ?>
      </div>

      <?php if ($oferta['afiliado_nome'] && $oferta['afiliado_icone']): ?>
        <div class="afiliado-info mt-3">
          <img src="<?= htmlspecialchars($oferta['afiliado_icone']) ?>" alt="Afiliado">
          <span>Produto via <strong><?= htmlspecialchars($oferta['afiliado_nome']) ?></strong></span>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <?php if (!empty($relatedOffers)): ?>
    <section class="mt-5">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h3 class="mb-0">Promoções Relacionadas</h3>
        <?php if (!empty($oferta['categoria'])): ?>
          <span class="text-muted small">Baseado na categoria "<?= htmlspecialchars($oferta['categoria']) ?>"</span>
        <?php endif; ?>
      </div>
      <div class="row g-4">
        <?php foreach ($relatedOffers as $relacionada): ?>
          <?php
            $relImage = $relacionada['imagem_url'] ?? '';
            if (!empty($relImage) && !preg_match('#^https?://#i', $relImage)) {
                $relImage = '/' . ltrim($relImage, '/');
            }
            $relPrecoAtual = isset($relacionada['preco_atual']) ? (float) $relacionada['preco_atual'] : 0.0;
            $relPrecoOriginal = isset($relacionada['preco_original']) ? (float) $relacionada['preco_original'] : 0.0;
            $relDesconto = ($relPrecoOriginal > 0 && $relPrecoOriginal > $relPrecoAtual)
                ? round((($relPrecoOriginal - $relPrecoAtual) / $relPrecoOriginal) * 100)
                : 0;
            $relAdminAvatar = resolvePublicAvatar($relacionada['admin_avatar'] ?? null);
            $relAdminInitial = getAdminInitial($relacionada['admin_nome'] ?? null);
          ?>
          <div class="col-md-6 col-xl-3">
            <article class="relacionadas-card">
              <a href="produto.php?id=<?= (int) $relacionada['id'] ?>" class="d-block">
                <img src="<?= htmlspecialchars($relImage) ?>" alt="<?= htmlspecialchars($relacionada['titulo']) ?>">
              </a>
              <div class="card-body">
                <?php if (!empty($relacionada['categoria'])): ?>
                  <span class="categoria-badge"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($relacionada['categoria']) ?></span>
                <?php endif; ?>
                <h5 class="card-title">
                  <a href="produto.php?id=<?= (int) $relacionada['id'] ?>" class="stretched-link text-decoration-none text-reset">
                    <?= htmlspecialchars($relacionada['titulo']) ?>
                  </a>
                </h5>
                <div class="card-price">R$ <?= number_format($relPrecoAtual, 2, ',', '.') ?></div>
                <?php if ($relDesconto > 0): ?>
                  <div class="card-price-old">R$ <?= number_format($relPrecoOriginal, 2, ',', '.') ?></div>
                  <div class="card-discount">Economize <?= $relDesconto ?>%</div>
                <?php elseif ($relPrecoOriginal > 0 && $relPrecoOriginal <= $relPrecoAtual): ?>
                  <div class="card-price-old text-muted">Preço original: R$ <?= number_format($relPrecoOriginal, 2, ',', '.') ?></div>
                <?php endif; ?>
              </div>
              <div class="card-footer">
                <?php if (!empty($relacionada['admin_nome'])): ?>
                  <div class="admin-badge" data-admin-name="<?= htmlspecialchars($relacionada['admin_nome']) ?>" title="Oferta cadastrada por <?= htmlspecialchars($relacionada['admin_nome']) ?>" aria-label="Oferta cadastrada por <?= htmlspecialchars($relacionada['admin_nome']) ?>">
                    <div class="admin-avatar">
                      <?php if (!empty($relAdminAvatar)): ?>
                        <img src="<?= htmlspecialchars($relAdminAvatar) ?>" alt="Avatar de <?= htmlspecialchars($relacionada['admin_nome']) ?>">
                      <?php elseif ($relAdminInitial !== ''): ?>
                        <?= htmlspecialchars($relAdminInitial) ?>
                      <?php else: ?>
                        <i class="bi bi-person-fill"></i>
                      <?php endif; ?>
                    </div>
                    <span class="admin-name"><?= htmlspecialchars($relacionada['admin_nome']) ?></span>
                  </div>
                <?php endif; ?>
                <a href="produto.php?id=<?= (int) $relacionada['id'] ?>" class="btn btn-sm btn-outline-primary">Ver detalhes</a>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

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
<div id="zoomOverlay" class="zoom-overlay" aria-hidden="true">
  <button type="button" class="zoom-close" aria-label="Fechar visualização ampliada">&times;</button>
  <img src="" id="zoomOverlayImg" alt="Visualização ampliada">
</div>

<footer class="text-center py-4 mt-5 bg-dark text-white">
  <div class="container">
    <p>&copy; <?= date('Y') ?> Oferta Shop. Todos os direitos reservados.</p>
  </div>
</footer>

<?php if ($comentarioEnviado): ?>
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="comentarioToast" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        Seu comentário foi enviado e será exibido após análise.
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-close-toast aria-label="Fechar"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  function onReady(callback) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      callback();
    } else {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
    }
  }

  onReady(function () {
    const overlay = document.getElementById('zoomOverlay');
    const overlayImg = document.getElementById('zoomOverlayImg');
    const overlayCloseBtn = overlay ? overlay.querySelector('.zoom-close') : null;

    function closeZoom() {
      if (!overlay || !overlayImg) {
        return;
      }
      overlay.classList.remove('is-visible');
      overlay.setAttribute('aria-hidden', 'true');
      overlayImg.removeAttribute('src');
      document.body.classList.remove('no-scroll');
    }

    function openZoom(src) {
      if (!overlay || !overlayImg) {
        return;
      }
      overlayImg.src = src;
      overlay.classList.add('is-visible');
      overlay.setAttribute('aria-hidden', 'false');
      document.body.classList.add('no-scroll');
    }

    if (overlay) {
      overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
          closeZoom();
        }
      });
    }

    if (overlayCloseBtn) {
      overlayCloseBtn.addEventListener('click', closeZoom);
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && overlay && overlay.classList.contains('is-visible')) {
        closeZoom();
      }
    });

    const gallery = document.querySelector('.produto-galeria');
    if (gallery) {
      const images = Array.from(gallery.querySelectorAll('.galeria-imagem'));
      const thumbs = Array.from(gallery.querySelectorAll('.miniatura'));
      const prevBtn = gallery.querySelector('.galeria-prev');
      const nextBtn = gallery.querySelector('.galeria-next');
      const statusCurrent = gallery.querySelector('.galeria-atual');
      let currentIndex = images.findIndex((img) => img.classList.contains('ativo'));
      if (currentIndex < 0) {
        currentIndex = 0;
      }

      function setActive(index) {
        if (!images.length) {
          return;
        }
        const total = images.length;
        currentIndex = (index + total) % total;
        images.forEach((img, i) => {
          const isActive = i === currentIndex;
          img.classList.toggle('ativo', isActive);
          img.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });
        thumbs.forEach((thumb, i) => {
          thumb.classList.toggle('ativo', i === currentIndex);
        });
        if (statusCurrent) {
          statusCurrent.textContent = currentIndex + 1;
        }
      }

      images.forEach((img) => {
        img.addEventListener('click', function () {
          const zoomSrc = img.dataset.zoomSrc || img.src;
          openZoom(zoomSrc);
        });
      });

      thumbs.forEach((thumb) => {
        thumb.addEventListener('click', function () {
          const idx = parseInt(thumb.dataset.index, 10);
          if (!Number.isNaN(idx)) {
            setActive(idx);
          }
        });
      });

      if (prevBtn) {
        prevBtn.addEventListener('click', function () {
          setActive(currentIndex - 1);
        });
      }

      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          setActive(currentIndex + 1);
        });
      }

      setActive(currentIndex);
    }

    const toastEl = document.getElementById('comentarioToast');
    if (toastEl) {
      const closeBtn = toastEl.querySelector('[data-close-toast]');
      let hideTimeout = null;

      const showToast = function () {
        toastEl.classList.add('is-visible');
      };

      const hideToast = function () {
        toastEl.classList.remove('is-visible');
        if (hideTimeout) {
          clearTimeout(hideTimeout);
          hideTimeout = null;
        }
      };

      showToast();
      hideTimeout = setTimeout(hideToast, 5000);

      if (closeBtn) {
        closeBtn.addEventListener('click', hideToast);
      }
    }
  });
})();
</script>
</body>
</html>

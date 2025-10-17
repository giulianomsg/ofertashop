<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// 1. Ofertas por categoria
$stmt = $pdo->query("
    SELECT c.nome AS categoria, COUNT(o.id) AS total
    FROM categorias c
    LEFT JOIN ofertas o ON o.categoria_id = c.id
    GROUP BY c.id
");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Ofertas por programa afiliado
$stmt = $pdo->query("
    SELECT p.nome AS afiliado, COUNT(o.id) AS total
    FROM programas_afiliados p
    LEFT JOIN ofertas o ON o.programa_id = p.id
    GROUP BY p.id
");
$afiliados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Ofertas ativas vs inativas
$stmt = $pdo->query("
    SELECT ativo, COUNT(*) as total
    FROM ofertas
    GROUP BY ativo
");
$statusOfertas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$ativas = $statusOfertas[1] ?? 0;
$inativas = $statusOfertas[0] ?? 0;

$totalOfertas = (int) $pdo->query('SELECT COUNT(*) FROM ofertas')->fetchColumn();
$ofertasAtivasTotal = (int) $pdo->query('SELECT COUNT(*) FROM ofertas WHERE ativo = 1')->fetchColumn();
$novasUltimos30 = (int) $pdo->query("SELECT COUNT(*) FROM ofertas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$comentariosPendentes = (int) $pdo->query('SELECT COUNT(*) FROM comentarios WHERE aprovado = 0')->fetchColumn();
$mediaDesconto = $pdo->query('SELECT AVG(CASE WHEN preco_original > 0 THEN ((preco_original - preco_atual) / preco_original) * 100 ELSE NULL END) FROM ofertas WHERE ativo = 1')->fetchColumn();
$mediaDesconto = $mediaDesconto !== null ? round((float) $mediaDesconto, 1) : 0.0;
$melhorOfertaStmt = $pdo->query('SELECT titulo, ROUND(((preco_original - preco_atual) / preco_original) * 100, 1) AS desconto FROM ofertas WHERE ativo = 1 AND preco_original > 0 ORDER BY desconto DESC LIMIT 1');
$melhorOferta = $melhorOfertaStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$ofertasMesStmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes, COUNT(*) AS total FROM ofertas WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY mes ORDER BY mes");
$ofertasPorMes = $ofertasMesStmt->fetchAll(PDO::FETCH_ASSOC);
$mesLabels = [];
$mesValores = [];
$mesesAbreviados = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
foreach ($ofertasPorMes as $linha) {
    $dataMes = DateTime::createFromFormat('Y-m', $linha['mes']);
    if ($dataMes instanceof DateTime) {
        $indiceMes = (int) $dataMes->format('n') - 1;
        $mesLabels[] = $mesesAbreviados[$indiceMes] . '/' . $dataMes->format('y');
    } else {
        $mesLabels[] = $linha['mes'];
    }
    $mesValores[] = (int) $linha['total'];
}

$performanceStmt = $pdo->query("SELECT o.titulo, COALESCE(SUM(e.cliques), 0) AS cliques, COALESCE(SUM(e.visualizacoes), 0) AS visualizacoes FROM ofertas o LEFT JOIN estatisticas e ON e.oferta_id = o.id GROUP BY o.id ORDER BY cliques DESC, visualizacoes DESC LIMIT 5");
$performance = $performanceStmt->fetchAll(PDO::FETCH_ASSOC);
$performanceLabels = array_column($performance, 'titulo');
$performanceCliques = array_map('intval', array_column($performance, 'cliques'));
$performanceViews = array_map('intval', array_column($performance, 'visualizacoes'));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard - Administração</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <link rel="stylesheet" href="/css/style.css">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    body {
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .sidebar {
      min-width: 250px;
      max-width: 250px;
      background-color: #343a40;
      color: #fff;
      transition: all 0.3s ease;
    }
    .sidebar a {
      color: #adb5bd;
      display: block;
      padding: 12px 20px;
      text-decoration: none;
    }
    .sidebar a:hover,
    .sidebar .active {
      background-color: #495057;
      color: #fff;
    }
    .main-content {
      flex-grow: 1;
      padding: 20px;
      transition: margin-left 0.3s ease;
    }
    .collapsed .sidebar {
      margin-left: -250px;
    }
    .collapsed .main-content {
      margin-left: 0;
    }
    .toggle-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: #333;
    }
    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        height: 100%;
        z-index: 1000;
      }
    }
  </style>
</head>
<body class="" id="bodyWrapper">

<!-- Sidebar -->
<div class="sidebar bg-dark" id="sidebar">
  <div class="px-3 py-3 border-bottom d-flex align-items-center">
    <i class="fas fa-gauge me-2"></i> <strong>Admin</strong>
  </div>
  <a href="dashboard.php" class="active"><i class="fas fa-chart-line me-2"></i> Dashboard</a>
  <a href="ofertas.php"><i class="fas fa-tags me-2"></i> Ofertas</a>
  <a href="categorias.php"><i class="fas fa-list me-2"></i> Categorias</a>
  <a href="afiliados.php"><i class="fas fa-link me-2"></i> Afiliados</a>
  <a href="comentarios.php">Comentários</a>
  <a href="administradores.php"><i class="fas fa-user-shield me-2"></i> Administradores</a>
  <a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Sair</a>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
  <button class="toggle-btn" id="toggleSidebar"><i class="fas fa-bars"></i></button>

  <h2>Estatísticas Gerais</h2>

  <div class="row g-4 mt-1">
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted text-uppercase small">Ofertas publicadas</div>
          <h3 class="fw-bold mb-1"><?= $totalOfertas ?></h3>
          <small class="text-success">Ativas: <?= $ofertasAtivasTotal ?></small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted text-uppercase small">Novas (30 dias)</div>
          <h3 class="fw-bold mb-1"><?= $novasUltimos30 ?></h3>
          <small class="text-muted">Atualize sempre que possível</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted text-uppercase small">Comentários pendentes</div>
          <h3 class="fw-bold mb-1"><?= $comentariosPendentes ?></h3>
          <small><a href="comentarios.php" class="text-decoration-none">Ir para moderação</a></small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <div class="text-muted text-uppercase small">Média de desconto</div>
          <h3 class="fw-bold mb-1"><?= number_format($mediaDesconto, 1, ',', '.') ?>%</h3>
          <?php if ($melhorOferta): ?>
            <small class="text-success">Top: <?= htmlspecialchars($melhorOferta['titulo']) ?> (<?= number_format((float) $melhorOferta['desconto'], 1, ',', '.') ?>%)</small>
          <?php else: ?>
            <small class="text-muted">Cadastre mais ofertas com preço original</small>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-4">
    <!-- Gráfico 1: Ofertas por Categoria -->
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header bg-primary text-white">Ofertas por Categoria</div>
        <div class="card-body">
          <canvas id="chartCategorias" height="300"></canvas>
        </div>
      </div>
    </div>

    <!-- Gráfico 2: Ofertas por Afiliado -->
    <div class="col-md-6 mb-4">
      <div class="card">
        <div class="card-header bg-success text-white">Ofertas por Afiliado</div>
        <div class="card-body">
          <canvas id="chartAfiliados" height="300"></canvas>
        </div>
      </div>
    </div>

    <!-- Gráfico 3: Ativas x Inativas -->
    <div class="col-lg-4 mb-4">
      <div class="card">
        <div class="card-header bg-warning text-dark">Status das Ofertas</div>
        <div class="card-body">
          <canvas id="chartStatus" height="220"></canvas>
        </div>
      </div>
    </div>

    <!-- Gráfico 4: Ofertas por mês -->
    <div class="col-lg-8 mb-4">
      <div class="card">
        <div class="card-header bg-info text-white">Novas ofertas por mês</div>
        <div class="card-body">
          <canvas id="chartOfertasMes" height="220"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header bg-secondary text-white">Top 5 ofertas por cliques</div>
        <div class="card-body">
          <canvas id="chartPerformance" height="160"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const toggleBtn = document.getElementById('toggleSidebar');
  const bodyWrapper = document.getElementById('bodyWrapper');

  toggleBtn.addEventListener('click', () => {
    bodyWrapper.classList.toggle('collapsed');
  });

  const ofertasMesLabels = <?= json_encode($mesLabels) ?>;
  const ofertasMesValores = <?= json_encode($mesValores) ?>;
  const performanceLabels = <?= json_encode($performanceLabels) ?>;
  const performanceCliques = <?= json_encode($performanceCliques) ?>;
  const performanceViews = <?= json_encode($performanceViews) ?>;

  // Gráfico 1: Categorias
  new Chart(document.getElementById("chartCategorias"), {
    type: "bar",
    data: {
      labels: <?= json_encode(array_column($categorias, 'categoria')) ?>,
      datasets: [{
        label: "Total de Ofertas",
        data: <?= json_encode(array_column($categorias, 'total')) ?>,
        backgroundColor: "#0d6efd"
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      }
    }
  });

  // Gráfico 2: Afiliados
  new Chart(document.getElementById("chartAfiliados"), {
    type: "pie",
    data: {
      labels: <?= json_encode(array_column($afiliados, 'afiliado')) ?>,
      datasets: [{
        label: "Ofertas",
        data: <?= json_encode(array_column($afiliados, 'total')) ?>,
        backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545', '#6f42c1']
      }]
    }
  });

  // Gráfico 3: Ativas x Inativas
  new Chart(document.getElementById("chartStatus"), {
    type: "doughnut",
    data: {
      labels: ["Ativas", "Inativas"],
      datasets: [{
        label: "Status",
        data: [<?= $ativas ?>, <?= $inativas ?>],
        backgroundColor: ['#198754', '#dc3545']
      }]
    }
  });

  // Gráfico 4: Ofertas por mês
  new Chart(document.getElementById("chartOfertasMes"), {
    type: "line",
    data: {
      labels: ofertasMesLabels,
      datasets: [{
        label: "Novas ofertas",
        data: ofertasMesValores,
        borderColor: '#0dcaf0',
        backgroundColor: 'rgba(13, 202, 240, 0.2)',
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });

  // Gráfico 5: Performance
  new Chart(document.getElementById("chartPerformance"), {
    type: "bar",
    data: {
      labels: performanceLabels,
      datasets: [
        {
          label: 'Cliques',
          data: performanceCliques,
          backgroundColor: '#6610f2'
        },
        {
          label: 'Visualizações',
          data: performanceViews,
          backgroundColor: '#ffc107'
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        x: { stacked: false },
        y: {
          beginAtZero: true,
          ticks: { precision: 0 }
        }
      }
    }
  });
</script>

</body>
</html>


<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Totais
$total_ofertas = $pdo->query("SELECT COUNT(*) FROM ofertas")->fetchColumn();
$total_categorias = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
$total_afiliados = $pdo->query("SELECT COUNT(*) FROM programas_afiliados")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

// Ofertas por categoria
$catLabels = [];
$catData = [];
$categorias = $pdo->query("SELECT c.nome, COUNT(o.id) AS total
    FROM categorias c
    LEFT JOIN ofertas o ON o.categoria_id = c.id
    GROUP BY c.id
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($categorias as $c) {
    $catLabels[] = $c['nome'];
    $catData[] = $c['total'];
}

// Ofertas por afiliado
$afLabels = [];
$afData = [];
$afiliados = $pdo->query("SELECT p.nome, COUNT(o.id) AS total
    FROM programas_afiliados p
    LEFT JOIN ofertas o ON o.programa_id = p.id
    GROUP BY p.id
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($afiliados as $a) {
    $afLabels[] = $a['nome'];
    $afData[] = $a['total'];
}

// Ativas x Inativas
$ativas = $pdo->query("SELECT COUNT(*) FROM ofertas WHERE ativo = 1")->fetchColumn();
$inativas = $pdo->query("SELECT COUNT(*) FROM ofertas WHERE ativo = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Oferta Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
    }
    .sidebar {
      height: 100vh;
      background-color: #343a40;
      padding-top: 1rem;
      position: fixed;
      width: 220px;
    }
    .sidebar a {
      color: #fff;
      text-decoration: none;
      display: block;
      padding: 10px 20px;
    }
    .sidebar a:hover {
      background-color: #495057;
    }
    .main-content {
      margin-left: 220px;
      padding: 2rem;
    }
    .card-icon {
      font-size: 2rem;
      color: #0d6efd;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h5 class="text-white text-center mb-4">Admin Oferta Shop</h5>
  <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
  <a href="ofertas.php"><i class="bi bi-tags me-2"></i>Ofertas</a>
  <a href="categorias.php"><i class="bi bi-grid-3x3-gap me-2"></i>Categorias</a>
  <a href="afiliados.php"><i class="bi bi-link-45deg me-2"></i>Afiliados</a>
  <a href="administradores.php"><i class="bi bi-person-badge me-2"></i>Administradores</a>
  <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a>
</div>

<!-- Main content -->
<div class="main-content">
  <div class="container-fluid">
    <h2 class="mb-4">Dashboard</h2>

    <div class="row g-4 mb-5">
      <div class="col-md-3">
        <div class="card text-bg-light">
          <div class="card-body">
            <div class="card-title fw-bold">Ofertas</div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fs-4"><?= $total_ofertas ?></span>
              <i class="bi bi-tags card-icon"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-bg-light">
          <div class="card-body">
            <div class="card-title fw-bold">Categorias</div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fs-4"><?= $total_categorias ?></span>
              <i class="bi bi-grid card-icon"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-bg-light">
          <div class="card-body">
            <div class="card-title fw-bold">Afiliados</div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fs-4"><?= $total_afiliados ?></span>
              <i class="bi bi-link-45deg card-icon"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-bg-light">
          <div class="card-body">
            <div class="card-title fw-bold">Admins</div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fs-4"><?= $total_admins ?></span>
              <i class="bi bi-person-badge card-icon"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
      <div class="col-lg-6 mb-4">
        <h5>Ofertas por Categoria</h5>
        <canvas id="catChart"></canvas>
      </div>
      <div class="col-lg-6 mb-4">
        <h5>Ofertas por Afiliado</h5>
        <canvas id="afChart"></canvas>
      </div>
      <div class="col-lg-6 mb-4">
        <h5>Status das Ofertas</h5>
        <canvas id="statusChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
const catCtx = document.getElementById('catChart');
new Chart(catCtx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($catLabels) ?>,
    datasets: [{
      label: 'Ofertas',
      data: <?= json_encode($catData) ?>,
      backgroundColor: '#0d6efd'
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false }
    }
  }
});

const afCtx = document.getElementById('afChart');
new Chart(afCtx, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($afLabels) ?>,
    datasets: [{
      data: <?= json_encode($afData) ?>,
      backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1']
    }]
  }
});

const statusCtx = document.getElementById('statusChart');
new Chart(statusCtx, {
  type: 'pie',
  data: {
    labels: ['Ativas', 'Inativas'],
    datasets: [{
      data: [<?= $ativas ?>, <?= $inativas ?>],
      backgroundColor: ['#198754', '#6c757d']
    }]
  }
});
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

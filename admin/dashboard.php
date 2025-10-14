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
    <div class="col-md-12">
      <div class="card">
        <div class="card-header bg-warning text-dark">Status das Ofertas</div>
        <div class="card-body">
          <canvas id="chartStatus" height="100"></canvas>
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
</script>

</body>
</html>


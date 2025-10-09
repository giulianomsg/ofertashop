<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT o.*, c.nome AS categoria FROM ofertas o LEFT JOIN categorias c ON o.categoria_id = c.id ORDER BY o.created_at DESC");
$ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Ofertas - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- DataTables CSS & Plugins -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.6.0/css/searchBuilder.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css">
</head>
<body>

  <div class="container mt-4">
    <h2>Ofertas Cadastradas</h2>
    <a href="nova_oferta.php" class="btn btn-success mb-3">+ Nova Oferta</a>

    <div class="table-responsive">
      <table id="tabela-ofertas" class="display nowrap table table-striped" style="width:100%">
        <thead>
          <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Categoria</th>
            <th>Preço Atual</th>
            <th>Preço Original</th>
            <th>Ativo</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ofertas as $oferta): ?>
            <tr>
              <td><?= $oferta['id'] ?></td>
              <td><?= htmlspecialchars($oferta['titulo']) ?></td>
              <td><?= htmlspecialchars($oferta['categoria']) ?></td>
              <td>R$ <?= number_format($oferta['preco_atual'], 2, ',', '.') ?></td>
              <td><s>R$ <?= number_format($oferta['preco_original'], 2, ',', '.') ?></s></td>
              <td><?= $oferta['ativo'] ? 'Sim' : 'Não' ?></td>
              <td>
                <a href="editar_oferta.php?id=<?= $oferta['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                <a href="excluir_oferta.php?id=<?= $oferta['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza?')">Excluir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <a href="dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- DataTables Core -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <!-- Plugins -->
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
  <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>
  <script src="https://cdn.datatables.net/searchbuilder/1.6.0/js/dataTables.searchBuilder.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

  <!-- Export Plugins -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

  <script>
    $(document).ready(function () {
      $('#tabela-ofertas').DataTable({
        responsive: true,
        dom: 'QBfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        select: true
      });
    });
  </script>
</body>
</html>

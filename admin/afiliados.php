<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$afiliados = $pdo->query("SELECT * FROM programas_afiliados ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Afiliados - Administração</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  
   <!-- DataTables CSS & Plugins -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.6.0/css/searchBuilder.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.dataTables.min.css">

  <style>
    .icon-img {
      width: 40px;
      height: 40px;
      object-fit: contain;
    }
  </style>
</head>

<body>

<div class="container mt-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Programas de Afiliados</h2>
    <a href="novo_afiliado.php" class="btn btn-success">+ Novo Afiliado</a>
  </div>

  <div class="table-responsive">
    <table id="afiliadosTable" class="table table-bordered table-striped nowrap w-100">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Ícone</th>
          <th>Nome</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($afiliados as $af): ?>
          <tr>
            <td><?= $af['id'] ?></td>
            <td>
              <?php if ($af['icone_url']): ?>
                <img src="../<?= htmlspecialchars($af['icone_url']) ?>" class="icon-img" alt="Ícone">
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($af['nome']) ?></td>
            <td>
              <a href="editar_afiliado.php?id=<?= $af['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
              <a href="excluir_afiliado.php?id=<?= $af['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este afiliado?')">Excluir</a>
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
      $('#afiliadosTable').DataTable({
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

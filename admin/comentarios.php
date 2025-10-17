<?php
session_start();
require 'config.php';

if (empty($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("
    SELECT c.*, o.titulo AS oferta
    FROM comentarios c
    JOIN ofertas o ON c.oferta_id = o.id
    ORDER BY c.aprovado ASC, c.created_at DESC
");
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totais = [
    'todos' => count($comentarios),
    'aprovados' => 0,
    'pendentes' => 0,
];

foreach ($comentarios as $comentario) {
    if ((int) $comentario['aprovado'] === 1) {
        $totais['aprovados']++;
    } else {
        $totais['pendentes']++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Comentários - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    .comentario-preview {
      max-width: 420px;
      white-space: normal;
    }
    .filter-buttons .btn {
      min-width: 160px;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
      <h3 class="mb-1">Moderação de Comentários</h3>
      <p class="text-muted mb-0">Gerencie aprovações, responda rapidamente e mantenha sua vitrine segura.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
      </a>
      <div class="filter-buttons btn-group" role="group" aria-label="Filtros de status">
        <button type="button" class="btn btn-outline-secondary active" data-filter-status="todos">Todos (<?= $totais['todos'] ?>)</button>
        <button type="button" class="btn btn-outline-warning" data-filter-status="pendentes">Pendentes (<?= $totais['pendentes'] ?>)</button>
        <button type="button" class="btn btn-outline-success" data-filter-status="aprovados">Aprovados (<?= $totais['aprovados'] ?>)</button>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table id="tabela-comentarios" class="display nowrap table table-striped" style="width:100%">
      <thead>
        <tr>
          <th>Oferta</th>
          <th>Nome</th>
          <th>Email</th>
          <th>Comentário</th>
          <th>Data</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($comentarios as $c): ?>
          <?php $textoComentario = trim(preg_replace('/\s+/', ' ', $c['comentario'])); ?>
          <tr>
            <td><?= htmlspecialchars($c['oferta']) ?></td>
            <td><?= htmlspecialchars($c['nome']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td class="comentario-preview" data-search="<?= htmlspecialchars($textoComentario) ?>"><?= nl2br(htmlspecialchars($c['comentario'])) ?></td>
            <td data-order="<?= strtotime($c['created_at']) ?>"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
            <td data-search="<?= $c['aprovado'] ? 'Aprovado' : 'Pendente' ?>" data-order="<?= (int) $c['aprovado'] ?>">
              <?= $c['aprovado'] ? '<span class="badge bg-success">Aprovado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>' ?>
            </td>
            <td>
              <div class="btn-group btn-group-sm" role="group">
                <?php if (!$c['aprovado']): ?>
                  <a href="comentario_aprovar.php?id=<?= $c['id'] ?>" class="btn btn-success">Aprovar</a>
                <?php endif; ?>
                <a href="comentario_excluir.php?id=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Excluir comentário?')">Excluir</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
  $(document).ready(function () {
    const tabela = $('#tabela-comentarios').DataTable({
      responsive: true,
      dom: 'Bfrtip',
      buttons: [
        {
          extend: 'collection',
          text: 'Exportar',
          buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
        },
        'colvis'
      ],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
      },
      order: [[4, 'desc']],
      columnDefs: [
        { responsivePriority: 1, targets: 0 },
        { responsivePriority: 2, targets: -1 },
        { responsivePriority: 3, targets: 3 }
      ]
    });

    $('.filter-buttons button').on('click', function () {
      $('.filter-buttons button').removeClass('active');
      $(this).addClass('active');

      const status = $(this).data('filter-status');
      if (status === 'todos') {
        tabela.column(5).search('').draw();
        return;
      }
      const termo = status === 'pendentes' ? 'Pendente' : 'Aprovado';
      tabela.column(5).search(termo, true, false).draw();
    });
  });
</script>
</body>
</html>

<?php
require 'verifica.php';
require 'config.php';

$stmt = $pdo->query("
    SELECT c.*, o.titulo AS oferta
    FROM comentarios c
    JOIN ofertas o ON c.oferta_id = o.id
    ORDER BY c.aprovado ASC, c.created_at DESC
");
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Comentários - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'menu.php'; ?>

<div class="container py-4">
  <h3>Moderação de Comentários</h3>

  <table class="table table-striped table-bordered">
    <thead class="table-dark">
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
        <tr>
          <td><?= htmlspecialchars($c['oferta']) ?></td>
          <td><?= htmlspecialchars($c['nome']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><?= nl2br(htmlspecialchars($c['comentario'])) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
          <td>
            <?= $c['aprovado'] ? '<span class="badge bg-success">Aprovado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>' ?>
          </td>
          <td>
            <?php if (!$c['aprovado']): ?>
              <a href="comentario_aprovar.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-success">Aprovar</a>
            <?php endif; ?>
            <a href="comentario_excluir.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Excluir comentário?')">Excluir</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</body>
</html>

<?php
require 'config.php';
require_once __DIR__ . '/includes/price_verification.php';

session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

ensurePriceVerificationSchema($pdo);

$stmt = $pdo->query("SELECT o.*, c.nome AS categoria,
    (SELECT verificado_em FROM verificacoes_preco WHERE oferta_id = o.id ORDER BY verificado_em DESC LIMIT 1) AS ultima_verificacao,
    (SELECT status FROM verificacoes_preco WHERE oferta_id = o.id ORDER BY verificado_em DESC LIMIT 1) AS status_verificacao,
    (SELECT preco_encontrado FROM verificacoes_preco WHERE oferta_id = o.id ORDER BY verificado_em DESC LIMIT 1) AS preco_verificado,
    (SELECT MIN(preco_encontrado) FROM verificacoes_preco WHERE oferta_id = o.id AND status = 'ok' AND preco_encontrado IS NOT NULL) AS melhor_preco
  FROM ofertas o
  LEFT JOIN categorias c ON o.categoria_id = c.id
  ORDER BY o.created_at DESC");
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
            <th>Verificação</th>
            <th>Melhor Preço</th>
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
              <td>
                <div class="verificacao-resumo" data-oferta="<?= $oferta['id'] ?>">
                  <?php if ($oferta['status_verificacao']): ?>
                    <span class="badge <?= $oferta['status_verificacao'] === 'ok' ? 'bg-success' : 'bg-warning text-dark' ?> mb-1">
                      <?= $oferta['status_verificacao'] === 'ok' ? 'Sucesso' : 'Falha' ?>
                    </span>
                    <?php if ($oferta['ultima_verificacao']): ?>
                      <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($oferta['ultima_verificacao'])) ?></div>
                    <?php endif; ?>
                    <?php if ($oferta['preco_verificado']): ?>
                      <div class="small">R$ <?= number_format($oferta['preco_verificado'], 2, ',', '.') ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted small">Sem verificações</span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="melhor-preco" data-oferta="<?= $oferta['id'] ?>">
                <?= $oferta['melhor_preco'] ? 'R$ ' . number_format($oferta['melhor_preco'], 2, ',', '.') : '<span class="text-muted">-</span>' ?>
              </td>
              <td><?= $oferta['ativo'] ? 'Sim' : 'Não' ?></td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  <a href="editar_oferta.php?id=<?= $oferta['id'] ?>" class="btn btn-primary">Editar</a>
                  <button type="button" class="btn btn-outline-secondary btn-verificar-preco" data-id="<?= $oferta['id'] ?>">Verificar preço</button>
                  <a href="excluir_oferta.php?id=<?= $oferta['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza?')">Excluir</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <a href="dashboard.php" class="btn btn-secondary mt-3">Voltar</a>
  </div>

  <div class="modal fade" id="modalVerificarPreco" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Verificação de preço</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="text-center py-3">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="mb-0">Conectando ao site parceiro...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
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
    const formatCurrency = (valor) => {
      if (valor === null || typeof valor === 'undefined') {
        return null;
      }
      return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    };

    const modalElement = document.getElementById('modalVerificarPreco');
    const modal = new bootstrap.Modal(modalElement);
    const modalTitle = modalElement.querySelector('.modal-title');
    const modalBody = modalElement.querySelector('.modal-body');

    $(document).ready(function () {
      const tabela = $('#tabela-ofertas').DataTable({
        responsive: true,
        dom: 'QBfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        select: true
      });

      $('#tabela-ofertas').on('click', '.btn-verificar-preco', function () {
        const ofertaId = $(this).data('id');
        modalTitle.textContent = `Verificando oferta #${ofertaId}`;
        modalBody.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary mb-2" role="status"></div><p class="mb-0">Conectando ao site parceiro...</p></div>';
        modal.show();

        fetch('api/verificar_preco.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ id: ofertaId })
        })
          .then(async (response) => {
            const data = await response.json();
            return { ok: response.ok, data };
          })
          .then(({ ok, data }) => {
            if (!ok || data.status !== 'ok') {
              const mensagem = data?.mensagem || 'Não foi possível concluir a verificação.';
              modalBody.innerHTML = `<div class="alert alert-danger mb-0">${mensagem}</div>`;
              return;
            }

            const resultado = data.resultado;
            const statusLabel = resultado.status === 'ok'
              ? '<span class="badge bg-success">Sucesso</span>'
              : '<span class="badge bg-warning text-dark">Falha</span>';
            const horario = resultado.verificacao?.verificado_em
              ? new Date(resultado.verificacao.verificado_em).toLocaleString('pt-BR')
              : '';
            const precoEncontrado = resultado.preco_encontrado !== null
              ? formatCurrency(resultado.preco_encontrado)
              : null;
            const diferenca = resultado.diferenca !== null
              ? formatCurrency(resultado.diferenca)
              : null;
            const melhorPreco = resultado.melhor_preco !== null
              ? formatCurrency(resultado.melhor_preco)
              : null;

            let corpo = `<div class="alert ${resultado.status === 'ok' ? 'alert-success' : 'alert-warning'} mb-0">`;
            corpo += `<div class="d-flex justify-content-between align-items-center mb-2">${statusLabel}<small class="text-muted">${horario}</small></div>`;
            corpo += `<p class="mb-1">${resultado.mensagem}</p>`;
            if (precoEncontrado) {
              corpo += `<p class="mb-1">Preço encontrado: <strong>${precoEncontrado}</strong></p>`;
            }
            if (diferenca && diferenca !== formatCurrency(0)) {
              corpo += `<p class="mb-1">Diferença: <strong>${diferenca}</strong></p>`;
            }
            if (melhorPreco) {
              corpo += `<p class="mb-0">Melhor preço registrado: <strong>${melhorPreco}</strong></p>`;
            }
            corpo += '</div>';

            modalBody.innerHTML = corpo;

            const resumo = document.querySelector(`.verificacao-resumo[data-oferta="${ofertaId}"]`);
            if (resumo) {
              let resumoHtml = '';
              resumoHtml += resultado.status === 'ok'
                ? '<span class="badge bg-success mb-1">Sucesso</span>'
                : '<span class="badge bg-warning text-dark mb-1">Falha</span>';
              if (horario) {
                resumoHtml += `<div class="small text-muted">${horario}</div>`;
              }
              if (precoEncontrado) {
                resumoHtml += `<div class="small">${precoEncontrado}</div>`;
              }
              resumo.innerHTML = resumoHtml;
            }

            const melhorPrecoCelula = document.querySelector(`.melhor-preco[data-oferta="${ofertaId}"]`);
            if (melhorPrecoCelula) {
              melhorPrecoCelula.innerHTML = melhorPreco
                ? melhorPreco
                : '<span class="text-muted">-</span>';
            }
          })
          .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger mb-0">Falha inesperada ao contatar o verificador de preços.</div>';
          });
      });
    });
  </script>
</body>
</html>

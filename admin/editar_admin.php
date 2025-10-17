<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: administradores.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header('Location: administradores.php');
    exit;
}

$erro = '';

function saveAdminAvatar(?array $file, ?string $croppedData, ?string $existing, ?string &$erro): ?string
{
    $croppedData = $croppedData !== null ? trim($croppedData) : '';
    $temArquivo = $file && !empty($file['name']);

    if ($croppedData === '' && !$temArquivo) {
        return $existing;
    }

    $destinoDir = '../uploads/admins';
    if (!is_dir($destinoDir)) {
        mkdir($destinoDir, 0755, true);
    }

    $allowedExtensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $novoNome = null;
    $conteudoImagem = null;
    $mime = null;

    if ($croppedData !== '') {
        if (preg_match('/^data:(image\/\w+);base64,/', $croppedData, $matches)) {
            $mime = strtolower($matches[1]);
            $base64 = substr($croppedData, strpos($croppedData, ',') + 1);
            $conteudoImagem = base64_decode($base64);
        }

        if ($conteudoImagem && function_exists('finfo_open') && function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo ? finfo_buffer($finfo, $conteudoImagem) : null;
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($detectedMime && isset($allowedExtensions[$detectedMime])) {
                $mime = $detectedMime;
            }
        }

        if (!$conteudoImagem || !isset($allowedExtensions[$mime])) {
            $erro = 'Não foi possível processar o avatar recortado. Tente novamente.';
            return $existing;
        }

        $ext = $allowedExtensions[$mime];
        $novoNome = uniqid('admin_', true) . '.' . $ext;
        $destinoFisico = $destinoDir . '/' . $novoNome;

        if (file_put_contents($destinoFisico, $conteudoImagem) === false) {
            $erro = 'Falha ao salvar o avatar recortado.';
            return $existing;
        }
    } else {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Falha no upload do avatar. Tente novamente.';
            return $existing;
        }

        $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $detectedMime = null;
        if (function_exists('finfo_open') && function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
            if ($finfo) {
                finfo_close($finfo);
            }
        }

        if (!in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || ($detectedMime && !isset($allowedExtensions[$detectedMime]))) {
            $erro = 'Tipo de arquivo não permitido. Utilize uma imagem JPG, PNG, GIF ou WEBP.';
            return $existing;
        }

        $extReal = $detectedMime && isset($allowedExtensions[$detectedMime]) ? $allowedExtensions[$detectedMime] : $extensao;
        $novoNome = uniqid('admin_', true) . '.' . $extReal;
        $destinoFisico = $destinoDir . '/' . $novoNome;

        if (!move_uploaded_file($file['tmp_name'], $destinoFisico)) {
            $erro = 'Não foi possível salvar o avatar enviado.';
            return $existing;
        }
    }

    if ($existing) {
        $caminhoAntigo = dirname(__DIR__) . '/' . ltrim($existing, '/');
        if (is_file($caminhoAntigo)) {
            @unlink($caminhoAntigo);
        }
    }

    return 'uploads/admins/' . $novoNome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $removerAvatar = isset($_POST['remover_avatar']);

    if (!empty($nome) && !empty($email)) {
        $avatarAtual = $admin['avatar'];

        $avatarCropped = $_POST['avatar_cropped'] ?? '';

        if ($removerAvatar && empty($avatarCropped) && $avatarAtual) {
            $caminhoFisico = dirname(__DIR__) . '/' . ltrim($avatarAtual, '/');
            if (is_file($caminhoFisico)) {
                @unlink($caminhoFisico);
            }
            $avatarAtual = null;
        }

        $avatarAtual = saveAdminAvatar($_FILES['avatar'] ?? null, $avatarCropped, $avatarAtual, $erro);

        if (empty($erro)) {
            if (!empty($senha)) {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE admins SET nome = ?, email = ?, senha_hash = ?, avatar = ? WHERE id = ?');
                $stmt->execute([$nome, $email, $hash, $avatarAtual, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE admins SET nome = ?, email = ?, avatar = ? WHERE id = ?');
                $stmt->execute([$nome, $email, $avatarAtual, $id]);
            }

            header('Location: administradores.php');
            exit;
        }
    } else {
        $erro = 'Preencha todos os campos obrigatórios.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <title>Editar Administrador</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css" rel="stylesheet">
  <style>
    .avatar-preview {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      overflow: hidden;
      background: #f1f3f5;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px dashed #ced4da;
      margin-bottom: 0.5rem;
    }
    .avatar-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  </style>
</head>
<body>
  <div class="container mt-4">
    <h2>Editar Administrador</h2>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($admin['nome']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Nova Senha (deixe em branco para manter)</label>
        <input type="password" name="senha" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Avatar</label>
        <div class="d-flex align-items-start gap-3 flex-wrap">
          <div>
            <div class="avatar-preview" id="avatarPreview">
              <?php if (!empty($admin['avatar'])): ?>
                <img src="<?= htmlspecialchars($admin['avatar']) ?>" alt="Avatar atual" id="avatarPreviewImage">
              <?php else: ?>
                <span class="text-muted small">Pré-visualização</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex-grow-1">
            <input type="file" name="avatar" id="avatarInput" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
            <input type="hidden" name="avatar_cropped" id="avatarCropped">
            <small class="text-muted d-block">Formatos permitidos: JPG, PNG, GIF ou WEBP.</small>
            <small class="text-muted">Após selecionar a imagem, ajuste o recorte e confirme.</small>
            <?php if (!empty($admin['avatar'])): ?>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remover_avatar" id="remover_avatar">
                <label class="form-check-label" for="remover_avatar">Remover avatar atual</label>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Atualizar</button>
      <a href="administradores.php" class="btn btn-secondary">Cancelar</a>
    </form>
  </div>

  <div class="modal fade" id="cropperModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Ajustar avatar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="ratio ratio-1x1 bg-light">
            <img src="#" alt="Pré-visualização do recorte" id="cropperImage" style="max-width:100%; display:none;">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="cropperConfirm">Aplicar recorte</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
  <script>
    (function() {
      const input = document.getElementById('avatarInput');
      const preview = document.getElementById('avatarPreview');
      const hiddenInput = document.getElementById('avatarCropped');
      const modalElement = document.getElementById('cropperModal');
      const imageElement = document.getElementById('cropperImage');
      const confirmButton = document.getElementById('cropperConfirm');
      const removeCheckbox = document.getElementById('remover_avatar');
      let cropper = null;
      let modal = null;

      if (!input) {
        return;
      }

      modal = new bootstrap.Modal(modalElement);

      input.addEventListener('change', function(event) {
        const file = event.target.files && event.target.files[0];
        hiddenInput.value = '';

        if (removeCheckbox) {
          removeCheckbox.checked = false;
        }

        if (!file) {
          if (!preview.querySelector('img')) {
            preview.innerHTML = '<span class="text-muted small">Pré-visualização</span>';
          }
          if (cropper) {
            cropper.destroy();
            cropper = null;
          }
          return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
          imageElement.src = e.target.result;
          imageElement.style.display = 'block';

          modal.show();

          if (cropper) {
            cropper.destroy();
          }

          cropper = new Cropper(imageElement, {
            aspectRatio: 1,
            viewMode: 1,
            minCropBoxWidth: 150,
            minCropBoxHeight: 150,
            movable: true,
            zoomable: true,
            scalable: true,
            responsive: true,
          });
        };
        reader.readAsDataURL(file);
      });

      confirmButton.addEventListener('click', function() {
        if (!cropper) {
          modal.hide();
          return;
        }

        const canvas = cropper.getCroppedCanvas({
          width: 320,
          height: 320,
          imageSmoothingQuality: 'high',
        });

        hiddenInput.value = canvas.toDataURL('image/png');
        preview.innerHTML = '';
        const previewImage = document.createElement('img');
        previewImage.src = hiddenInput.value;
        preview.appendChild(previewImage);

        modal.hide();
        cropper.destroy();
        cropper = null;
      });

      modalElement.addEventListener('hidden.bs.modal', function() {
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
      });
    })();
  </script>
</body>
</html>

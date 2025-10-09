<?php
require 'config.php';
session_start();

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM programas_afiliados WHERE id = :id");
$stmt->execute([':id' => $id]);
$afiliado = $stmt->fetch(PDO::FETCH_ASSOC);

if ($afiliado) {
    // Deletar ícone se existir
    if (!empty($afiliado['icone_url']) && file_exists('../' . $afiliado['icone_url'])) {
        unlink('../' . $afiliado['icone_url']);
    }

    // Deletar do banco
    $del = $pdo->prepare("DELETE FROM programas_afiliados WHERE id = :id");
    $del->execute([':id' => $id]);
}

header('Location: afiliados.php');
exit;

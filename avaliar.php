<?php
require 'admin/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
    $nota = isset($_POST['nota']) ? intval($_POST['nota']) : 0;

    // Validação básica
    if ($oferta_id > 0 && $nota >= 1 && $nota <= 5) {
        $stmt = $pdo->prepare("INSERT INTO avaliacoes (oferta_id, nota, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$oferta_id, $nota]);
    }

    header("Location: produto.php?id=" . $oferta_id);
    exit;
} else {
    header("Location: index.php");
    exit;
}

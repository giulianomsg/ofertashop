<?php
require 'admin/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oferta_id = isset($_POST['oferta_id']) ? intval($_POST['oferta_id']) : 0;
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');

    // Validação básica
    if ($oferta_id && $nome && $comentario) {
        $stmt = $pdo->prepare("INSERT INTO comentarios (oferta_id, nome, email, comentario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$oferta_id, $nome, $email, $comentario]);
    }

    // Redireciona de volta para a página do produto
    header("Location: produto.php?id=$oferta_id&comentario=enviado");
    exit;
} else {
    // Acesso inválido
    header("Location: index.php");
    exit;
}

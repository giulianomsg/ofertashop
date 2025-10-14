<?php
require 'verifica.php';
require 'config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header('Location: comentarios.php');
exit;

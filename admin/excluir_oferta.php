<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM ofertas WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: ofertas.php');
exit;

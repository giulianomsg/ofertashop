<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;

// Impede que o admin exclua a si mesmo
if ($id && $_SESSION['admin_nome'] !== 'SUPERADMIN' && $id != $_SESSION['admin_id']) {
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: administradores.php');
exit;

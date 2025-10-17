<?php
require 'config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;

// Impede que o admin exclua a si mesmo (exceto SUPERADMIN)
if ($id && $_SESSION['admin_nome'] !== 'SUPERADMIN' && $id != $_SESSION['admin_id']) {
    $stmt = $pdo->prepare('SELECT avatar FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    $avatar = $stmt->fetchColumn();

    if ($avatar) {
        $caminhoFisico = dirname(__DIR__) . '/' . ltrim($avatar, '/');
        if (is_file($caminhoFisico)) {
            @unlink($caminhoFisico);
        }
    }

    $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: administradores.php');
exit;

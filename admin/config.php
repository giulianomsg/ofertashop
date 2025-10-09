<?php
// Configurações de conexão com o banco de dados

$host = 'localhost';
$dbname = 'u197364123_ofertashop';
$user = 'u197364123_ofertashop';
$pass = 'Gava040879@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

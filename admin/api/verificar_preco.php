<?php

declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/price_verification.php';

ensurePriceVerificationSchema($pdo);

$payload = [];
$rawInput = file_get_contents('php://input');
if ($rawInput) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (empty($payload)) {
    $payload = $_POST;
}

$ofertaId = isset($payload['id']) ? (int) $payload['id'] : 0;

if ($ofertaId <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'erro', 'mensagem' => 'ID da oferta inválido.']);
    exit;
}

try {
    $verifier = new RemotePriceVerifier($pdo);
    $resultado = $verifier->verify($ofertaId);

    $dados = [
        'status' => $resultado['status'],
        'mensagem' => $resultado['mensagem'],
        'preco_encontrado' => $resultado['preco_encontrado'],
        'diferenca' => $resultado['diferenca'],
        'melhor_preco' => $resultado['melhor_preco'],
        'verificacao' => $resultado['verificacao'],
    ];

    echo json_encode(['status' => 'ok', 'resultado' => $dados]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Falha ao verificar o preço: ' . $e->getMessage(),
    ]);
}

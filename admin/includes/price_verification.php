<?php

declare(strict_types=1);

if (!function_exists('ensurePriceVerificationSchema')) {
    function ensurePriceVerificationSchema(PDO $pdo): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }

        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS verificacoes_preco (
                id INT AUTO_INCREMENT PRIMARY KEY,
                oferta_id INT NOT NULL,
                preco_cadastrado DECIMAL(10,2) NOT NULL,
                preco_encontrado DECIMAL(10,2) DEFAULT NULL,
                diferenca DECIMAL(10,2) DEFAULT NULL,
                status ENUM('ok','falha') NOT NULL DEFAULT 'falha',
                mensagem VARCHAR(255) DEFAULT NULL,
                fonte VARCHAR(150) DEFAULT NULL,
                http_code SMALLINT DEFAULT NULL,
                verificado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_oferta (oferta_id),
                CONSTRAINT fk_verificacao_oferta FOREIGN KEY (oferta_id)
                    REFERENCES ofertas(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        $ensured = true;
    }

    function getLastPriceVerification(PDO $pdo, int $ofertaId): ?array
    {
        $stmt = $pdo->prepare('SELECT preco_encontrado, diferenca, status, mensagem, fonte, http_code, verificado_em FROM verificacoes_preco WHERE oferta_id = ? ORDER BY verificado_em DESC LIMIT 1');
        $stmt->execute([$ofertaId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    function getBestPriceForOffer(PDO $pdo, int $ofertaId): ?float
    {
        $stmt = $pdo->prepare("SELECT MIN(preco_encontrado) AS melhor_preco FROM verificacoes_preco WHERE oferta_id = ? AND status = 'ok' AND preco_encontrado IS NOT NULL");
        $stmt->execute([$ofertaId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($result['melhor_preco']) && $result['melhor_preco'] !== null
            ? (float) $result['melhor_preco']
            : null;
    }

    class RemotePriceVerifier
    {
        private const USER_AGENT = 'Mozilla/5.0 (compatible; OfertaShopBot/1.0; +https://ofertashop.example)';

        public function __construct(private PDO $pdo)
        {
            ensurePriceVerificationSchema($pdo);
        }

        public function verify(int $ofertaId): array
        {
            $oferta = $this->buscarOferta($ofertaId);

            if (!$oferta) {
                throw new InvalidArgumentException('Oferta não encontrada.');
            }

            if (empty($oferta['link_afiliado'])) {
                return $this->registrarFalha($ofertaId, (float) $oferta['preco_atual'], 'Link de afiliado não informado.', null, null);
            }

            [$conteudo, $httpCode, $erro, $urlFinal] = $this->obterConteudoRemoto($oferta['link_afiliado']);

            if ($conteudo === null) {
                $mensagem = $erro ?: 'Não foi possível acessar o link do afiliado.';
                $fonteFalha = parse_url($urlFinal ?? $oferta['link_afiliado'], PHP_URL_HOST)
                    ?: ($urlFinal ?? $oferta['link_afiliado']);

                return $this->registrarFalha(
                    $ofertaId,
                    (float) $oferta['preco_atual'],
                    $mensagem,
                    $httpCode,
                    $fonteFalha
                );
            }

            $urlParaProcessar = $urlFinal ?? $oferta['link_afiliado'];
            $host = parse_url($urlParaProcessar, PHP_URL_HOST) ?: '';
            $precoEncontrado = $this->extrairPreco($conteudo, $host, $urlParaProcessar);

            if ($precoEncontrado === null) {
                return $this->registrarFalha($ofertaId, (float) $oferta['preco_atual'], 'Não foi possível localizar o preço na página.', $httpCode, $host);
            }

            $precoCadastrado = (float) $oferta['preco_atual'];
            $diferenca = round($precoEncontrado - $precoCadastrado, 2);
            $mensagem = $diferenca < 0 ? 'Preço verificado está menor que o cadastrado.' : 'Preço verificado com sucesso.';

            $stmt = $this->pdo->prepare('INSERT INTO verificacoes_preco (oferta_id, preco_cadastrado, preco_encontrado, diferenca, status, mensagem, fonte, http_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $ofertaId,
                $precoCadastrado,
                $precoEncontrado,
                $diferenca,
                'ok',
                $mensagem,
                $host,
                $httpCode,
            ]);

            $ultima = getLastPriceVerification($this->pdo, $ofertaId);
            $melhorPreco = getBestPriceForOffer($this->pdo, $ofertaId);

            return [
                'status' => 'ok',
                'mensagem' => $mensagem,
                'preco_encontrado' => $precoEncontrado,
                'diferenca' => $diferenca,
                'melhor_preco' => $melhorPreco,
                'verificacao' => $ultima,
            ];
        }

        private function registrarFalha(int $ofertaId, float $precoCadastrado, string $mensagem, ?int $httpCode, ?string $fonte): array
        {
            $stmt = $this->pdo->prepare('INSERT INTO verificacoes_preco (oferta_id, preco_cadastrado, status, mensagem, http_code, fonte) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $ofertaId,
                $precoCadastrado,
                'falha',
                $mensagem,
                $httpCode,
                $fonte,
            ]);

            $ultima = getLastPriceVerification($this->pdo, $ofertaId);

            return [
                'status' => 'falha',
                'mensagem' => $mensagem,
                'preco_encontrado' => null,
                'diferenca' => null,
                'melhor_preco' => getBestPriceForOffer($this->pdo, $ofertaId),
                'verificacao' => $ultima,
            ];
        }

        private function buscarOferta(int $ofertaId): ?array
        {
            $stmt = $this->pdo->prepare('SELECT id, titulo, preco_atual, link_afiliado FROM ofertas WHERE id = ? AND ativo = 1');
            $stmt->execute([$ofertaId]);
            $oferta = $stmt->fetch(PDO::FETCH_ASSOC);

            return $oferta !== false ? $oferta : null;
        }

        private function obterConteudoRemoto(string $url): array
        {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_HTTPHEADER => [
                    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                ],
            ]);

            $conteudo = curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $urlFinal = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL) ?: $url;
            $erro = curl_error($curl) ?: null;
            curl_close($curl);

            if ($conteudo === false || $httpCode >= 400) {
                return [null, $httpCode, $erro, $urlFinal];
            }

            return [$conteudo, $httpCode, null, $urlFinal];
        }

        private function extrairPreco(string $conteudo, string $host, ?string $urlFinal): ?float
        {
            $conteudo = html_entity_decode($conteudo, ENT_QUOTES | ENT_HTML5);

            if (stripos($host, 'amazon.') !== false) {
                if (preg_match('/"priceToPay"\s*:\s*\{"currency"\s*:\s*"BRL","amount":([0-9]+(?:\.[0-9]+)?)\}/', $conteudo, $match)) {
                    return (float) $match[1];
                }
                if (preg_match('/"priceAmount"\s*:\s*\{"currencySymbol":"R\$","amount":([0-9]+(?:\.[0-9]+)?)\}/', $conteudo, $match)) {
                    return (float) $match[1];
                }
            }

            if (stripos($host, 'shopee.') !== false) {
                $precoViaApi = $this->extrairPrecoShopeePorApi($urlFinal, $conteudo);
                if ($precoViaApi !== null) {
                    return $precoViaApi;
                }
                if (preg_match('/"price":\s*([0-9]+(?:\.[0-9]+)?)/', $conteudo, $match)) {
                    $valor = (float) $match[1];
                    if ($valor > 10000) {
                        $valor = $valor / 100000;
                    }
                    return round($valor, 2);
                }
            }

            if (preg_match('/R\$\s*([0-9\.]+,\d{2})/', $conteudo, $match)) {
                return $this->converteParaDecimal($match[1]);
            }

            if (preg_match('/([0-9]{1,3}(?:\.[0-9]{3})*,[0-9]{2})/', $conteudo, $match)) {
                return $this->converteParaDecimal($match[1]);
            }

            if (preg_match('/"amount"\s*:\s*([0-9]+(?:\.[0-9]+)?)/', $conteudo, $match)) {
                return (float) $match[1];
            }

            return null;
        }

        private function extrairPrecoShopeePorApi(?string $urlFinal, string $conteudo): ?float
        {
            $ids = $this->identificarShopeeIds($urlFinal, $conteudo);
            if ($ids === null) {
                return null;
            }

            [$shopId, $itemId] = $ids;
            $endpoint = sprintf('https://shopee.com.br/api/v4/item/get?itemid=%d&shopid=%d', $itemId, $shopId);

            $curl = curl_init($endpoint);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_HTTPHEADER => array_filter([
                    'Accept: application/json',
                    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                    $urlFinal ? 'Referer: ' . $urlFinal : null,
                ]),
            ]);

            $resposta = curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($resposta === false || $httpCode >= 400) {
                return null;
            }

            $dados = json_decode($resposta, true);
            if (!is_array($dados)) {
                return null;
            }

            $item = $dados['data']['item'] ?? null;
            if (!is_array($item)) {
                return null;
            }

            $valorBruto = $item['price_min']
                ?? $item['price']
                ?? $item['price_max']
                ?? null;

            if (!is_numeric($valorBruto)) {
                return null;
            }

            $valor = (float) $valorBruto;
            if ($valor > 0) {
                $valor = $valor / 100000;
            }

            return round($valor, 2);
        }

        private function identificarShopeeIds(?string $urlFinal, string $conteudo): ?array
        {
            $candidatos = [];

            if ($urlFinal) {
                $candidatos[] = $urlFinal;
            }

            if (preg_match('/href="([^"]*shopee\.[^"]+)"/', $conteudo, $match)) {
                $candidatos[] = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
            }

            foreach ($candidatos as $url) {
                if (preg_match('/i\.(\d+)\.(\d+)/', $url, $ids)) {
                    return [(int) $ids[1], (int) $ids[2]];
                }

                if (preg_match('/product\/(\d+)\/(\d+)/', $url, $ids)) {
                    return [(int) $ids[1], (int) $ids[2]];
                }
            }

            if (preg_match('/\bshopid\s*=\s*"?(\d+)"?/', $conteudo, $idsShop)
                && preg_match('/\bitemid\s*=\s*"?(\d+)"?/', $conteudo, $idsItem)) {
                return [(int) $idsShop[1], (int) $idsItem[1]];
            }

            return null;
        }

        private function converteParaDecimal(string $valor): float
        {
            $normalizado = str_replace(['.', ','], ['', '.'], $valor);
            return round((float) $normalizado, 2);
        }
    }
}

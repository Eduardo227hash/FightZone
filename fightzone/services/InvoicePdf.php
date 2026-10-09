<?php
class InvoicePdf
{
    public function enviar(array $pedido): void
    {
        $pdf = $this->gerar($pedido);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="fightzone-pedido-' . (int)$pedido['id_pedido'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    public function gerar(array $pedido): string
    {
        $text = static function (string &$content, float $x, float $y, int $size, string $value, string $font = 'F1'): void {
            $value = str_replace(["\r", "\n", "\t"], ' ', $value);
            $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
            $encoded = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $encoded);
            $content .= sprintf("0 g BT /%s %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $font, $size, $x, $y, $encoded);
        };
        $rect = static function (string &$content, float $x, float $y, float $width, float $height, bool $fill = false): void {
            $content .= $fill
                ? sprintf("0.96 0.95 0.92 rg %.2f %.2f %.2f %.2f re f\n", $x, $y, $width, $height)
                : sprintf("0.72 G 0.6 w %.2f %.2f %.2f %.2f re S\n", $x, $y, $width, $height);
        };
        $line = static function (string &$content, float $x1, float $y1, float $x2, float $y2): void {
            $content .= sprintf("0.72 G 0.5 w %.2f %.2f m %.2f %.2f l S\n", $x1, $y1, $x2, $y2);
        };
        $limit = static function (string $value, int $length): string {
            if (function_exists('mb_substr')) {
                return mb_strlen($value, 'UTF-8') > $length
                    ? mb_substr($value, 0, $length - 3, 'UTF-8') . '...'
                    : $value;
            }
            return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
        };
        $money = static function ($value): string {
            return 'R$ ' . number_format((float)$value, 2, ',', '.');
        };

        $pages = [];
        $content = '';
        $y = 0.0;
        $startPage = static function (bool $continued = false) use (&$content, &$y, $text, $rect, $limit, $pedido): void {
            $content = '';
            if ($continued) {
                $rect($content, 38, 770, 519, 34);
                $text($content, 50, 786, 13, 'FIGHTZONE | PEDIDO DEMONSTRATIVO', 'F2');
                $text($content, 50, 775, 8, 'CONTINUACAO - DOCUMENTO SEM VALOR FISCAL', 'F2');
                $y = 744;
            } else {
                $rect($content, 38, 744, 519, 60);
                $text($content, 50, 782, 20, 'FIGHTZONE', 'F2');
                $text($content, 50, 764, 9, 'PEDIDO / LAYOUT DEMONSTRATIVO INSPIRADO EM DANFE', 'F2');
                $rect($content, 390, 753, 155, 40, true);
                $text($content, 402, 778, 10, 'SEM VALOR FISCAL', 'F2');
                $text($content, 402, 762, 8, 'NAO E UMA NOTA FISCAL', 'F2');
                $text($content, 50, 750, 8, 'Pedido #' . (int)$pedido['id_pedido'] . '   |   ' . date('d/m/Y H:i', strtotime($pedido['criado_em'])));

                $rect($content, 38, 642, 519, 84);
                $text($content, 50, 709, 9, 'DADOS DO CLIENTE E ENTREGA', 'F2');
                $text($content, 50, 692, 9, 'Cliente: ' . $limit((string)$pedido['cliente_nome'], 52));
                $text($content, 330, 692, 9, 'CPF/CNPJ: ' . ($pedido['cliente_documento'] ?: 'nao informado'));
                $text($content, 50, 675, 8, 'E-mail: ' . $limit((string)$pedido['cliente_email'], 82));
                $endereco = 'Entrega: ' . $pedido['endereco'] . ' | CEP: ' . $pedido['cep'];
                $text($content, 50, 658, 8, $limit($endereco, 100));
                $y = 610;
            }
            $rect($content, 38, $y - 22, 519, 22, true);
            $text($content, 48, $y - 15, 8, 'DESCRICAO DOS PRODUTOS', 'F2');
            $text($content, 339, $y - 15, 8, 'QTD.', 'F2');
            $text($content, 397, $y - 15, 8, 'VALOR UNIT.', 'F2');
            $text($content, 488, $y - 15, 8, 'TOTAL', 'F2');
            $y -= 22;
        };
        $finishPage = static function () use (&$content, &$pages, $text): void {
            $text($content, 40, 38, 8, 'FIGHTZONE - DOCUMENTO DEMONSTRATIVO, SEM VALOR FISCAL');
            $pages[] = $content;
        };

        $startPage();
        foreach ($pedido['itens'] as $item) {
            $nome = (string)$item['produto_nome'];
            $linhasNome = explode("\n", wordwrap($nome, 48, "\n", true));
            if (count($linhasNome) > 3) {
                $linhasNome = array_slice($linhasNome, 0, 3);
                $linhasNome[2] = $limit($linhasNome[2], 44) . '...';
            }
            $altura = max(25, count($linhasNome) * 11 + 8);
            if ($y - $altura < 75) {
                $finishPage();
                $startPage(true);
            }
            $line($content, 38, $y, 557, $y);
            foreach ($linhasNome as $indice => $linhaNome) {
                $text($content, 48, $y - 13 - ($indice * 11), 8, $linhaNome);
            }
            $quantidade = (int)$item['quantidade'];
            $totalItem = (float)$item['preco_unitario'] * $quantidade;
            $text($content, 344, $y - 13, 8, (string)$quantidade);
            $text($content, 391, $y - 13, 8, $money($item['preco_unitario']));
            $text($content, 480, $y - 13, 8, $money($totalItem));
            $y -= $altura;
        }
        $line($content, 38, $y, 557, $y);
        if ($y - 112 < 75) {
            $finishPage();
            $startPage(true);
        }

        $rect($content, 322, $y - 91, 235, 88);
        $text($content, 334, $y - 20, 9, 'Subtotal: ' . $money($pedido['subtotal']));
        $text($content, 334, $y - 39, 9, 'Frete estimado: ' . $money($pedido['frete']));
        $line($content, 332, $y - 48, 547, $y - 48);
        $text($content, 334, $y - 65, 11, 'TOTAL: ' . $money($pedido['total']), 'F2');
        $text($content, 50, $y - 23, 8, 'Pagamento: ' . strtoupper((string)$pedido['forma_pagamento']));
        $text($content, 50, $y - 40, 8, 'Status: ' . strtoupper((string)($pedido['status_pagamento'] ?? 'pendente')));
        $text($content, 50, $y - 64, 8, 'Este documento resume um pedido e nao comprova pagamento.');
        $text($content, 50, $y - 77, 8, 'Nao possui validade fiscal e nao substitui uma NF-e ou DANFE.');
        $finishPage();
        $objetos = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'
        ];
        $paginasRefs = [];
        foreach ($pages as $pagina) {
            $paginaId = count($objetos) + 1;
            $conteudoId = $paginaId + 1;
            $paginasRefs[] = $paginaId . ' 0 R';
            $objetos[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $conteudoId . ' 0 R >>';
            $objetos[] = '<< /Length ' . strlen($pagina) . " >>\nstream\n" . $pagina . "endstream";
        }
        $objetos[1] = '<< /Type /Pages /Kids [' . implode(' ', $paginasRefs) . '] /Count ' . count($pages) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objetos as $indice => $objeto) {
            $offsets[] = strlen($pdf);
            $pdf .= ($indice + 1) . " 0 obj\n" . $objeto . "\nendobj\n";
        }
        $inicioXref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objetos); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\nstartxref\n" .
            $inicioXref . "\n%%EOF";

        return $pdf;
    }
}

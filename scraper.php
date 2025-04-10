<?php

$config = require 'config.php';
$url = $config['url'];

$ENABLE_DOWNLOAD = false;

if (!is_dir(__DIR__ . '/editais')) {
    mkdir(__DIR__ . '/editais', 0777, true);
}

// Requisição HTTP
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

// Carrega HTML
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Pega todos os elementos que podem ser <div class="ua"> ou <div class="da">
$containers = $xpath->query('//div[@class="ua" or @class="da"]');

$concursos = [];
$regiaoAtual = 'Indefinida';

foreach ($containers as $container) {
    $class = $container->getAttribute('class');

    if ($class === 'ua') {
        // Atualiza a região atual
        $ufNode = $xpath->query('.//div[@class="uf"]', $container)->item(0);
        if ($ufNode) {
            $regiaoAtual = trim($ufNode->nodeValue);
        }
    }

    if ($class === 'da') {
        $tituloNode = $xpath->query('.//div[@class="ca"]/a', $container)->item(0);
        $titulo = $tituloNode ? trim($tituloNode->nodeValue) : '';
        $link = $tituloNode ? $tituloNode->getAttribute('href') : '';

        $imgNode = $xpath->query('.//div[@class="cb"]/img', $container)->item(0);
        $imagem = $imgNode ? $imgNode->getAttribute('src') : '';

        $resumoNode = $xpath->query('.//div[@class="cd"]', $container)->item(0);
        $resumo = $resumoNode ? trim(preg_replace('/\s+/', ' ', $resumoNode->nodeValue)) : '';

        $inscricaoNode = $xpath->query('.//div[@class="ce"]/span', $container)->item(0);
        $inscricao = $inscricaoNode ? trim(preg_replace('/\s+/', ' ', $inscricaoNode->nodeValue)) : '';

        $situacao = 'Indefinido';
        if (stripos($inscricao, 'Prorrogado até') !== false) {
            $situacao = 'Prorrogado';
        } elseif (preg_match('/^(\d{2}\/\d{2}\/\d{4})$/', $inscricao, $matches)) {
            $dataUnica = DateTime::createFromFormat('d/m/Y', $matches[1]);
            $hoje = new DateTime('now');
            $situacao = $dataUnica > $hoje ? 'Inscrição não iniciada' : 'Fechado';
        } elseif (preg_match('/a\s*(\d{2}\/\d{2}\/\d{4})/', $inscricao, $matches)) {
            $dataFim = DateTime::createFromFormat('d/m/Y', $matches[1]);
            $hoje = new DateTime('now');
            $situacao = $dataFim >= $hoje ? 'Aberto' : 'Fechado';
        }

        $linkEditais = [];
        $apostilas = [];

        if (!empty($link)) {
            $subCh = curl_init($link);
            curl_setopt($subCh, CURLOPT_RETURNTRANSFER, true);
            $subHtml = curl_exec($subCh);
            curl_close($subCh);

            if ($subHtml) {
                $subDom = new DOMDocument();
                @$subDom->loadHTML($subHtml);
                $subXpath = new DOMXPath($subDom);

                $editalNodes = $subXpath->query('//aside[@id="links"]//li[contains(@class, "pdf")]/a');
                foreach ($editalNodes as $edital) {
                    $editalHref = $edital->getAttribute('href');
                    $editalTitulo = trim($edital->nodeValue);
                    $linkEditais[] = [
                        'titulo' => $editalTitulo,
                        'url' => $editalHref,
                    ];

                    // Download PDF
                    if($ENABLE_DOWNLOAD){
                        $arquivoNome = basename(parse_url($editalHref, PHP_URL_PATH));
                        $arquivoCaminho = __DIR__ . "/editais/" . $arquivoNome;
                        if (!file_exists($arquivoCaminho)) {
                            file_put_contents($arquivoCaminho, file_get_contents($editalHref));
                        }
                    }
                }

                $apostilaNodes = $subXpath->query('//aside[@id="links"]//li[contains(@class, "apostila")]/a');
                foreach ($apostilaNodes as $apostilaNode) {
                    $apostilas[] = [
                        'titulo' => trim($apostilaNode->nodeValue),
                        'url' => $apostilaNode->getAttribute('href'),
                    ];
                }
            }
        }

        $concursos[] = [
            'regiao' => $regiaoAtual,
            'titulo' => $titulo,
            'link' => $link,
            'imagem' => $imagem,
            'resumo' => $resumo,
            'periodo_inscricao' => $inscricao,
            'situacao' => $situacao,
            'link_apostila' => $apostilas,
            'link_edital' => $linkEditais,
        ];
    }
}

file_put_contents('concursos.json', json_encode($concursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "Dados salvos em concursos.json com a região de cada concurso.\n";

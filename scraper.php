<?php

$config = require 'config.php';
$url = $config['url'];

$ENABLE_DOWNLOAD = $config['enable_download'];

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
        $resumo = $resumoNode ? trim(preg_replace(['/\s+/', '/(\d{1,3}(?:\.\d{3})*,\d{2})([A-Za-z])/', '/(R\$ ?\d{1,3}(?:\.\d{3})*,\d{2})([A-Za-z])/', '/(\d)([A-Z])/', '/([a-z])([A-Z])/'], [' ', '$1 $2', '$1 $2', '$1 $2', '$1 $2'], $resumoNode->nodeValue)) : '';


        $inscricaoNode = $xpath->query('.//div[@class="ce"]/span', $container)->item(0);
        $inscricao = $inscricaoNode ? trim(preg_replace('/\s+/', ' ', $inscricaoNode->nodeValue)) : '';

        // Normaliza casos como "14 a25/07/2025" para "14 a 25/07/2025"
        $inscricao = preg_replace('/a(\d{1,2}\/\d{2}\/\d{4})/', 'a $1', $inscricao);
        // Normaliza prefixos colados com a data
        $inscricao = preg_replace('/(Reaberto até|Prorrogado até|Verificar por edital)(\d{1,2}\/\d{2}\/\d{4})/i', '$1 $2', $inscricao);

        $periodo_inscricao = $inscricao;
        $situacao = 'Indefinido';
        $hoje = new DateTime('now');

        // Trata casos "Reaberto até DD/MM/AAAA" ou "Prorrogado até DD/MM/AAAA"
        if (preg_match('/^(Reaberto até|Prorrogado até)\s*(\d{2}\/\d{2}\/\d{4})$/i', $inscricao, $matches)) {
            $dataFim = DateTime::createFromFormat('d/m/Y', $matches[2]);
            $periodo_inscricao = trim($matches[1]) . ' ' . $matches[2];
            $situacao = ($dataFim >= $hoje) ? 'Aberto' : 'Fechado';
        }
        // Trata caso "Verificar por edital DD/MM/AAAA"
        elseif (preg_match('/^Verificar por edital\s*(\d{2}\/\d{2}\/\d{4})$/i', $inscricao, $matches)) {
            $periodo_inscricao = 'Verificar por edital ' . $matches[1];
            $situacao = 'Indefinido';
        }
        // Tenta capturar período do tipo "14 a 25/07/2025" ou "14/07 a 25/07/2025"
        elseif (preg_match('/(\d{1,2})(?:\/(\d{2}))?\s*a\s*(\d{1,2}\/\d{2}\/\d{4})/', $inscricao, $matches)) {
            $diaInicio = $matches[1];
            $mesInicio = isset($matches[2]) ? $matches[2] : null;
            $dataFim = $matches[3];

            // Extrai dia, mês e ano da data final
            list($diaFim, $mesFim, $anoFim) = explode('/', $dataFim);

            // Se não tem mês na data inicial, usa o mês da data final
            if (!$mesInicio) $mesInicio = $mesFim;

            // Monta data inicial completa
            $dataInicio = sprintf('%02d/%02d/%04d', $diaInicio, $mesInicio, $anoFim);

            // Monta periodo_inscricao padronizado
            $periodo_inscricao = "$dataInicio a $dataFim";

            // Calcula situação
            $dtInicio = DateTime::createFromFormat('d/m/Y', $dataInicio);
            $dtFim = DateTime::createFromFormat('d/m/Y', $dataFim);

            if ($hoje < $dtInicio) {
                $situacao = 'Inscrição não iniciada';
            } elseif ($hoje > $dtFim) {
                $situacao = 'Fechado';
            } else {
                $situacao = 'Aberto';
            }
        } elseif (stripos($inscricao, 'Prorrogado até') !== false) {
            $situacao = 'Prorrogado';
        } elseif (preg_match('/^(\d{2}\/\d{2}\/\d{4})$/', $inscricao, $matches)) {
            $dataUnica = DateTime::createFromFormat('d/m/Y', $matches[1]);
            $situacao = $dataUnica > $hoje ? 'Inscrição não iniciada' : 'Fechado';
        } elseif (preg_match('/a\s*(\d{2}\/\d{2}\/\d{4})/', $inscricao, $matches)) {
            $dataFim = DateTime::createFromFormat('d/m/Y', $matches[1]);
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
            'periodo_inscricao' => $periodo_inscricao,
            'situacao' => $situacao,
            'link_apostila' => $apostilas,
            'link_edital' => $linkEditais,
        ];
    }
}

file_put_contents('concursos.json', json_encode($concursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "Dados salvos em concursos.json com a região de cada concurso.\n";

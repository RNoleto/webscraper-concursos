<?php

$config = require 'config.php';
$url = $config['url'];

// Cria pasta "editais" se não existir
if (!is_dir(__DIR__ . '/editais')) {
    mkdir(__DIR__ . '/editais', 0777, true);
}

// 1. Requisição HTTP
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);

// 2. Carrega o HTML
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

// 3. Extrair com XPath
$xpath = new DOMXPath($dom);
$nodes = $xpath->query('//div[@class="da"]');

if ($nodes->length === 0) {
    echo "Nenhum dado encontrado.\n";
    exit;
}

$concursos = [];

foreach ($nodes as $node) {
    $tituloNode = $xpath->query('.//div[@class="ca"]/a', $node)->item(0);
    $titulo = $tituloNode ? trim($tituloNode->nodeValue) : '';
    $link = $tituloNode ? $tituloNode->getAttribute('href') : '';

    $imgNode = $xpath->query('.//div[@class="cb"]/img', $node)->item(0);
    $imagem = $imgNode ? $imgNode->getAttribute('src') : '';

    $resumoNode = $xpath->query('.//div[@class="cd"]', $node)->item(0);
    $resumo = $resumoNode ? trim(preg_replace('/\s+/', ' ', $resumoNode->nodeValue)) : '';

    $inscricaoNode = $xpath->query('.//div[@class="ce"]/span', $node)->item(0);
    $inscricao = $inscricaoNode ? trim(preg_replace('/\s+/', ' ', $inscricaoNode->nodeValue)) : '';

    // Situação do concurso
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

    // Inicializa arrays
    $linkEditais = [];
    $apostilas = [];

    // Busca links de edital e apostila
    if (!empty($link)) {
        $subCh = curl_init($link);
        curl_setopt($subCh, CURLOPT_RETURNTRANSFER, true);
        $subHtml = curl_exec($subCh);
        curl_close($subCh);

        if ($subHtml) {
            $subDom = new DOMDocument();
            @$subDom->loadHTML($subHtml);
            $subXpath = new DOMXPath($subDom);

            // Links de edital (PDF)
            $editalNodes = $subXpath->query('//aside[@id="links"]//li[contains(@class, "pdf")]/a');
            foreach ($editalNodes as $edital) {
                $editalHref = $edital->getAttribute('href');
                $editalTitulo = trim($edital->nodeValue);
                $linkEditais[] = [
                    'titulo' => $editalTitulo,
                    'url' => $editalHref,
                ];

                // Download PDF
                $arquivoNome = basename(parse_url($editalHref, PHP_URL_PATH));
                $arquivoCaminho = __DIR__ . "/editais/" . $arquivoNome;
                if (!file_exists($arquivoCaminho)) {
                    file_put_contents($arquivoCaminho, file_get_contents($editalHref));
                }
            }

            // Apostilas (opcional — atualize o seletor se necessário)
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

// Salva JSON
file_put_contents('concursos.json', json_encode($concursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "Dados salvos em concursos.json\n";

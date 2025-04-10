<?php
require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

$parser = new Parser();

// Caminho do JSON
$jsonPath = __DIR__ . '/concursos.json';
$dados = json_decode(file_get_contents($jsonPath), true);

// Limita a quantidade de concursos para processar
$totalConcursos = min(3, count($dados));

for ($index = 0; $index < $totalConcursos; $index++) {

    if (!isset($dados[$index]['link_edital'][0]['url'])) {
        echo "Nenhum edital encontrado para o concurso #{$index}\n";
        continue;
    }

    // Baixar o PDF do edital
    $editalUrl = $dados[$index]['link_edital'][0]['url'];
    $editalPath = __DIR__ . "/editais/edital_temp_{$index}.pdf";
    file_put_contents($editalPath, file_get_contents($editalUrl));

    // Extrair texto do PDF
    $pdf = $parser->parseFile($editalPath);
    $texto = $pdf->getText();

    // Extrair vagas por cargo
    preg_match_all('/(\d+)\s+vagas?\s+para\s+o\s+cargo\s+de\s+(\w+)/iu', $texto, $vagas_matches, PREG_SET_ORDER);

    // Extrair salários por cargo
    preg_match_all('/cargo\s+de\s+(\w+)[^R\$]+R\$?\s?([\d.,]+)/iu', $texto, $salarios_matches, PREG_SET_ORDER);

    // Mapear os dados por cargo
    $cargos = [];

    foreach ($vagas_matches as $vaga) {
        $cargo_nome = ucfirst(strtolower($vaga[2]));
        $cargos[$cargo_nome] = [
            'cargo' => $cargo_nome,
            'vagas' => (int)$vaga[1],
            'salario' => ''
        ];
    }

    foreach ($salarios_matches as $salario) {
        $cargo_nome = ucfirst(strtolower($salario[1]));
        $salario_valor = 'R$ ' . $salario[2];

        if (isset($cargos[$cargo_nome])) {
            $cargos[$cargo_nome]['salario'] = $salario_valor;
        } else {
            $cargos[$cargo_nome] = [
                'cargo' => $cargo_nome,
                'vagas' => null,
                'salario' => $salario_valor
            ];
        }
    }

    // Atualiza os detalhes extraídos
    $dados[$index]['detalhes_extraidos'] = [
        'cargos' => array_values($cargos)
    ];

    echo "Concurso #{$index} atualizado com sucesso.\n";
}

// Salvar de volta no JSON
file_put_contents($jsonPath, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Todos os concursos foram atualizados com sucesso!\n";

# 🎯 Concurso Parser

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://camo.githubusercontent.com/your-dark-mode-image-url">
  <img alt="Logo do Projeto" src="https://camo.githubusercontent.com/your-light-mode-image-url">
</picture>

Este projeto em PHP automatiza o processo de download e análise de editais de concursos públicos, extraindo dados como **cargos**, **quantidade de vagas** e **salários** a partir de arquivos PDF. As informações estruturadas são atualizadas diariamente no arquivo `concursos.json`, garantindo que a lista de concursos esteja sempre atualizada.

**Fonte dos dados**: Os concursos são extraídos diretamente da página **PCI Concursos**.

## 🧰 Tecnologias utilizadas

- **PHP >= 7.4**
- **[Smalot/pdfparser](https://github.com/smalot/pdfparser)** – Biblioteca para leitura de PDFs em PHP
- **Composer** – Gerenciador de dependências do PHP
- **JSON** – Utilizado como formato de persistência dos dados

## 📁 Estrutura de diretórios

```
/
├── editais/              # Contém os arquivos PDF dos editais baixados
├── concursos.json        # Base de dados com informações dos concursos
├── script.php            # Script principal que processa os editais
├── composer.json         # Dependências do projeto
└── README.md             # Documentação do projeto
```

> **Nota:** A pasta `editais/` será criada automaticamente pelo script se não existir.

## 🚀 Como executar o projeto

### 1. Clonar o repositório

```bash
git clone https://github.com/seu-usuario/concurso-parser.git
cd concurso-parser
```

### 2. Instalar as dependências

Certifique-se de que o **Composer** está instalado em sua máquina. Em seguida, execute:

```bash
composer install
```

### 3. Executar o scraper

```bash
php scraper.php
```

Esse comando:

- Vai executar o script de scrap
- Extrai as informações de concursos do PCI Concursos
- Atualiza o arquivo `concursos.json` com as informações estruturadas

## Exemplo de dados extraídos

```json
[
  {
    "regiao": "NACIONAL",
    "titulo": "CONAB - Companhia Nacional de Abastecimento",
    "link": "https://www.pciconcursos.com.br/noticias/conab-divulga-retificacao-de-concurso-publico-com-403-vagas",
    "imagem": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADUlEQVQImWP4//8/AwAI/AL+hc2rNAAAAABJRU5ErkJggg==",
    "resumo": "403 vagas até R$ 8140,88 Assistente, Analista Médio / Superior",
    "periodo_inscricao": "14/04 a15/05/2025",
    "situacao": "Aberto",
    "link_apostila": [],
    "link_edital": [
      {
        "titulo": "EDITAL DE ABERTURA Nº 001/2025 - RETIFICADO",
        "url": "https://arq.pciconcursos.com.br/conab-divulga-retificacao-de-concurso-publico-com-403-vagas/1671608/91e67a018e/edital_de_abertura_n_001_2025_retificado_1671608.pdf"
      },
      {
        "titulo": "RETIFICAÇÃO I",
        "url": "https://arq.pciconcursos.com.br/conab-divulga-retificacao-de-concurso-publico-com-403-vagas/1672785/9a7eb24896/retificacao_i_1672785.pdf"
      }
    ]
  }
]
```

### 3. Adicionar o arquivo `concursos.json`

Você precisa criar ou colocar um arquivo `concursos.json` com a seguinte estrutura básica:

```json
[
  {
    "link_edital": [{ "url": "https://exemplo.com/edital.pdf" }]
  }
]
```

> O campo `url` deve conter o link direto para o arquivo PDF do edital.

### 4. Executar o script

```bash
php script.php
```

Esse comando:

- Baixa os PDFs dos editais
- Extrai os dados relevantes (cargos, vagas e salários)
- Atualiza o arquivo `concursos.json` com as informações estruturadas

## 📝 Exemplo de dados extraídos

Após a execução, o campo `detalhes_extraidos` será adicionado em cada objeto no `concursos.json`, com dados assim:

```json
"detalhes_extraidos": {
  "cargos": [
    {
      "cargo": "Analista",
      "vagas": 12,
      "salario": "R$ 4.500,00"
    },
    {
      "cargo": "Técnico",
      "vagas": 5,
      "salario": "R$ 2.200,00"
    }
  ]
}
```

## 📦 .gitignore recomendado

Certifique-se de que seu `.gitignore` contenha a pasta de PDFs para evitar o versionamento:

```
/editais/
```

## 📌 Requisitos

- PHP 7.4 ou superior
- Extensão `mbstring` habilitada
- Composer

## 📖 Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

## 🙋‍♂️ Contribuições

Sinta-se à vontade para abrir **issues**, sugerir melhorias ou enviar um **pull request**!

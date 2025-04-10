# Concurso Parser

Este projeto em PHP automatiza o processo de download e análise de editais de concursos públicos, extraindo dados como **cargos**, **quantidade de vagas** e **salários** a partir de arquivos PDF e atualizando um arquivo `concursos.json` com essas informações estruturadas.

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

### 3. Adicionar o arquivo `concursos.json`

Você precisa criar ou colocar um arquivo `concursos.json` com a seguinte estrutura básica:

```json
[
  {
    "link_edital": [
      { "url": "https://exemplo.com/edital.pdf" }
    ]
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
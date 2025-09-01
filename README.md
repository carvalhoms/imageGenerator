# Image Generator

Sistema simplificado para processamento de imagens com UUID.

## Estrutura do Projeto

```
/
├── index.php           # Página principal (mantida na raiz)
├── api/                # APIs PHP organizadas
│   ├── listImages.php      # Lista imagens da pasta imgBase
│   ├── saveProcessedImage.php  # Salva imagens processadas com UUID
│   └── saveImage.php       # Endpoint legado (será removido)
├── imgBase/            # Pasta com imagens originais
├── imgResult/          # Pasta com imagens processadas (UUID)
├── js/                 # JavaScript
├── css/                # Estilos
└── brand/              # Recursos visuais
```

## Funcionamento

1. **Pasta `imgBase`**: Coloque aqui as imagens originais a serem processadas
2. **Processamento**: O sistema aplica marcas d'água e processamentos nas imagens
3. **Pasta `imgResult`**: As imagens processadas são salvas com nomes UUID
4. **Arquivo `dePara.txt`**: Criado em `imgResult/` com mapeamento original;uuid

## Como usar

1. Coloque imagens na pasta `imgBase/`
2. Acesse `index.php` no navegador
3. Processe as imagens usando a interface
4. As imagens processadas ficarão em `imgResult/` com nomes UUID
5. Consulte `imgResult/dePara.txt` para ver o mapeamento

## Tecnologias

- PHP (backend/APIs)
- JavaScript (processamento frontend)
- HTML2Canvas (captura de tela)

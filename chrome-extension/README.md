# GovCert — Extensão Chrome

## Como instalar (modo desenvolvedor)

1. Abra o Chrome e acesse `chrome://extensions`
2. Ative **Modo desenvolvedor** (canto superior direito)
3. Clique em **Carregar sem compactação**
4. Selecione a pasta `chrome-extension/` deste projeto
5. A extensão aparecerá na barra do Chrome

## Configuração inicial

1. Clique no ícone da extensão → **Configurações**
2. Preencha:
   - **URL da API**: `http://localhost:8000` (ou o endereço de produção)
   - **API Token**: gere um token no sistema web em *Perfil → API Tokens*
   - **Identificador do Usuário**: matrícula, e-mail ou nome para identificação nos logs
3. Clique em **Salvar Configurações**

## Plataformas monitoradas

- ChatGPT (`chatgpt.com`)
- Gemini (`gemini.google.com`)
- Claude (`claude.ai`)
- Copilot (`copilot.microsoft.com`)

## Como funciona

O Content Script injeta um observer no DOM de cada plataforma.
Quando o usuário envia uma mensagem e a IA responde completamente,
a extensão captura o par input/output e envia via `POST /api/audit/logs`
com autenticação Bearer (Sanctum). O servidor retorna 202 imediatamente
e processa a análise Gemini em background.

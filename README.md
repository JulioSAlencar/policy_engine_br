# GovCert — Plataforma de Auditoria e Governança de IA

Sistema de auditoria, certificação e governança pública de interações com IAs
generativas (ChatGPT, Claude, Gemini, Copilot). Uma extensão do Chrome captura os
pares *input/output*, a plataforma analisa cada interação com o Google Gemini sob a
ótica de LGPD/governança e disponibiliza dashboards, trilha de auditoria e relatórios.

> **Este documento** é voltado para **usuários, auditores e visão de produto**.
> Toda a parte de **infraestrutura, deploy, manutenção e troubleshooting** foi
> movida para **[`RUNBOOK.md`](RUNBOOK.md)** (equipe de Infra/DevOps).

---

## 1. Visão Geral do Projeto

GovCert resolve um problema concreto de governança pública: **colaboradores colam
dados sensíveis (CPF, prontuários, credenciais, código proprietário) em IAs públicas**.
A plataforma registra essas interações, classifica o risco automaticamente e dá
visibilidade ao time de auditoria/conformidade.

**Principais capacidades:**

- Captura transparente das interações via **extensão Chrome** (Manifest V3).
- Ingestão **assíncrona** (a API responde na hora; a análise roda em segundo plano).
- Classificação por IA: risco, tipo de vazamento e parecer técnico (LGPD).
- **Painel web:** dashboard com gráficos e filtro por período, tabela de logs com
  modal de chat, gestão de usuários (papéis Admin/Auditor) e trilha de atividades.

### 1.1 Fluxo de Dados (simplificado)

```
Extensão Chrome  ──POST /api/audit/logs (Bearer Token)──▶  API Laravel
                                                              │ salva "pending" + HTTP 202
                                                              ▼
                                                        Fila no Redis
                                                              │
                                                              ▼
                                                    Worker em background
                                                              │ chama o Gemini
                                                              ▼
                                                     Google Gemini API
                                                              │ risco/tipo/parecer
                                                              ▼
                                                         Banco MySQL
                                                              │
                                                              ▼
                                                    Painel Web (Blade)
```

Pontos-chave:

- A API **nunca** chama o Gemini de forma síncrona — sempre responde **HTTP 202**
  imediatamente e enfileira a análise (o navegador do usuário não trava).
- A autenticação da extensão usa **Laravel Sanctum** (token `Bearer`).

---

## 2. Manual da Extensão do Chrome

A extensão fica em **`./chrome-extension/`** (Manifest V3).

### 2.1 Como carregar a extensão (modo desenvolvedor)

1. Abra o Chrome e acesse **`chrome://extensions`**.
2. Ative o **Modo do desenvolvedor** (canto superior direito).
3. Clique em **Carregar sem compactação** (*Load unpacked*).
4. Selecione a pasta **`chrome-extension/`** na raiz deste projeto.
5. A extensão **GovCert — Auditoria de IA** aparece na barra do Chrome.
6. Sempre que arquivos da extensão forem alterados, clique em **Recarregar (↻)**
   no card da extensão em `chrome://extensions`.

### 2.2 Conectando a extensão ao servidor (Login)

Não há mais necessidade de gerar tokens manualmente ou acessar telas de opções.
O login é feito **diretamente no popup** da extensão:

1. Clique no ícone **GovCert** na barra do Chrome — o popup de login abrirá.
2. Preencha os três campos:
   - **URL da Plataforma:** endereço do servidor GovCert.
     - Desenvolvimento: `http://localhost:8000`
     - Produção: a URL **HTTPS** fornecida pelo administrador (ex.: `https://govcert.seugov.gov.br`)
   - **E-mail:** seu e-mail corporativo cadastrado na plataforma.
   - **Senha:** sua senha de acesso ao painel GovCert.
3. Clique em **Conectar**.

Em caso de sucesso, o popup exibirá um **card verde** com:
- "Status: Conectado"
- Seu nome de usuário
- O horário do último log enviado (ou *"Nenhum envio recente"* se for a primeira vez)

> Se a mensagem *"As credenciais fornecidas estão incorretas"* aparecer, confirme
> com o Administrador que sua conta existe e está ativa na plataforma.
> Se aparecer *"Conta desativada"*, acione o Administrador para reativar o acesso.

### 2.3 Desconectando

No popup (card verde), clique no botão vermelho **Desconectar**. A extensão
revoga o token no servidor e limpa todas as credenciais locais. O formulário de
login é exibido novamente.

> O token gerado no login **não expira por prazo**. Ele é revogado automaticamente
> quando você clicar em Desconectar ou quando o Administrador desativar sua conta.
> Um novo login gera um token novo e invalida o anterior para o mesmo dispositivo.

### 2.4 Como a extensão opera no dia a dia

Depois de configurada, **não há nenhuma ação manual** — a operação é transparente:

1. **Captura invisível em background** ao usar uma das plataformas suportadas:
   - `https://chatgpt.com` e `https://chat.openai.com`
   - `https://claude.ai`
   - `https://gemini.google.com`
   - `https://copilot.microsoft.com`

   O *content script* observa o DOM e detecta o **texto enviado** (input) e a
   **resposta da IA** (output), sem pop-ups nem cliques.
2. **Envio assíncrono:** a cada interação concluída, a extensão faz um `POST` para
   `/api/audit/logs`; o servidor responde **HTTP 202** e enfileira a análise.
3. **Análise automática:** o registro passa de **Pendente → Concluído** sozinho.
4. **Onde conferir:** painel web → **Dashboard** (volume/risco/tendências) e
   **Logs de Auditoria** (clique numa linha para abrir o modal de chat com input,
   resposta da IA e o parecer da auditoria).

**Boas práticas e privacidade:**

- A extensão **só** atua nas URLs suportadas — navegação normal não é capturada.
- Trate o API Token como uma **senha**: não compartilhe e não o versione em Git.
  Em caso de suspeita de vazamento, solicite a revogação ao Administrador.
- Se as interações não aparecerem no painel, acione o time de Infra/DevOps com
  referência ao **Cenário C** do [`RUNBOOK.md`](RUNBOOK.md).

---

## 3. ⚠️ Aviso de Manutenção de Frontend (Content Script)

> **Atenção técnica — leitura obrigatória para a equipe de desenvolvimento.**
>
> O *content script* da extensão (`chrome-extension/content.js`) **depende da
> estrutura do DOM (HTML) das interfaces das IAs** (ChatGPT, Claude, Gemini,
> Copilot) para localizar o texto do usuário e a resposta do modelo. Ele usa
> seletores específicos de cada plataforma.
>
> **Essas plataformas mudam o frontend com frequência e sem aviso.** Qualquer
> atualização de layout/DOM por parte de OpenAI, Anthropic, Google ou Microsoft
> **pode quebrar silenciosamente a captura** — a extensão para de enviar dados,
> mesmo com token e rede corretos, e **nenhum erro evidente** aparece para o
> usuário final.

**Procedimento quando a captura parar em uma plataforma específica:**

1. Confirme que o problema é por plataforma (ex.: captura funciona no Claude mas
   não no ChatGPT) — isso indica mudança de DOM, não falha de infra.
2. Abra o **DevTools → Console** na aba afetada e procure logs `[GovCert]`.
3. Revise e atualize os **seletores de DOM** em
   **`chrome-extension/content.js`** para a plataforma quebrada (inspecione os
   elementos de input e da resposta na nova versão da página).
4. Recarregue a extensão em `chrome://extensions` e valide a captura.
5. Versione a correção — trate `content.js` como **código de manutenção
   recorrente**, não "configure e esqueça".

> Recomendação de governança: monitore o volume de logs por plataforma no
> Dashboard. Uma queda abrupta de captura em uma única origem é o **sinal
> precoce** de que o `content.js` daquela IA precisa de revisão.

---

## 4. Documentação Relacionada

| Documento | Público | Conteúdo |
|-----------|---------|----------|
| `README.md` (este) | Usuários, Auditores, Produto | Visão geral, manual da extensão, avisos de frontend |
| [`RUNBOOK.md`](RUNBOOK.md) | Infra / DevOps / SysAdmin | Setup Docker, SSL, manutenção, troubleshooting, governança de dados |

---

*GovCert — uso interno restrito. Mantenha `GEMINI_API_KEY` e tokens Sanctum
**fora** do controle de versão.*

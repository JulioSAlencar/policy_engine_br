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

### 2.2 Obtendo o seu API Token

> ⚠️ **Aviso de segurança:** **nunca** digite a sua senha corporativa em comandos
> de terminal (`curl`, scripts etc.) para obter um token. Senhas não devem
> transitar por linha de comando, histórico de shell ou logs.

Há **duas formas suportadas** de obter o token, ambas sem expor a sua senha:

**Opção A — Solicitar ao Administrador (disponível hoje):**

O Administrador da plataforma gera um token nominal para você executando, no
servidor, o comando abaixo (apenas o admin tem acesso ao terminal do servidor):

```bash
docker compose exec app php artisan tinker --execute="echo \App\Models\User::where('email','voce@govcert.gov.br')->first()->createToken('extensao-chrome')->plainTextToken;"
```

O comando imprime o token no formato `id|hash`. O Administrador o entrega a você
por um **canal seguro** (gerenciador de segredos / mensagem interna protegida).
Por segurança, o hash **não** é exibido novamente — guarde-o com cuidado.

**Opção B — Geração pelo Painel Web (em implantação):**

A funcionalidade de **auto-geração de token na tela do próprio painel web**
(*Perfil → Tokens de Acesso*) será utilizada para autoatendimento, eliminando a
necessidade de acionar o Administrador. Até a sua liberação, use a Opção A.

> O token **não expira por padrão**. Se ele for revogado, ou se a sua conta for
> desativada, será necessário solicitar/gerar um novo.

### 2.3 Configurando a extensão (tela de Opções)

1. Abra a tela de **Opções** por **um** dos caminhos:
   - Clique no ícone da extensão → botão **Configurações**; ou
   - `chrome://extensions` → **GovCert** → **Detalhes** → **Opções da extensão**; ou
   - Botão direito no ícone da extensão → **Opções**.
2. Preencha os três campos:
   - **URL da API:** endereço da plataforma (ex.: `http://localhost:8000` em
     desenvolvimento, ou a URL **HTTPS** de produção). *Não inclua barra final.*
   - **API Token:** cole o valor completo obtido em 2.2 (incluindo o `id|`).
   - **Identificador do usuário:** sua matrícula ou e-mail — rótulo que aparecerá
     nos logs de auditoria como autor da interação.
3. Clique em **Salvar Configurações** (confirmação verde = sucesso).
4. Clique no ícone da extensão: o status deve indicar **"Monitoramento ativo"**.
   Se aparecer *"Token não configurado"*, revise o passo 2.

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

# GovCert — RUNBOOK de Infraestrutura e Operações

> **Público:** Infra / DevOps / SysAdmin. Documento operacional. Todos os comandos
> foram validados contra a stack Docker deste repositório.
>
> A documentação de produto e o manual da extensão estão em
> **[`README.md`](README.md)**.

---

```
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║   🔒  ALERTA CRÍTICO DE SSL / HTTPS — LEIA ANTES DE QUALQUER DEPLOY  🔒       ║
║                                                                              ║
║   EM PRODUÇÃO, O NGINX **OBRIGATORIAMENTE** DEVE SERVIR A APLICAÇÃO          ║
║   SOB HTTPS COM CERTIFICADO VÁLIDO.                                          ║
║                                                                              ║
║   A extensão roda DENTRO de páginas HTTPS (chatgpt.com, claude.ai,           ║
║   gemini.google.com, copilot.microsoft.com). Se a API estiver em HTTP        ║
║   simples, o Chrome BLOQUEIA a requisição como **Mixed Content** e a         ║
║   captura PARA SILENCIOSAMENTE — sem erro visível ao usuário.                ║
║                                                                              ║
║   • Dev: http://localhost:8000 funciona (localhost é "secure context").     ║
║   • Produção: um domínio em HTTP **NÃO** funciona — use TLS válido.          ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

**Checklist mínimo de HTTPS para produção:**

- Certificado TLS válido (Let's Encrypt/ACME ou corporativo) terminando no Nginx
  (ou em um load balancer/reverse proxy à frente dele).
- `APP_URL=https://seu-dominio` no `.env` e a URL **HTTPS** configurada na tela
  de Opções da extensão.
- Redirecionamento `HTTP → HTTPS` e cabeçalho `Strict-Transport-Security`.
- Validar pós-deploy: abra o ChatGPT, use uma frase de teste e confirme em
  **Logs de Auditoria** que o registro chegou. Sem HTTPS, nada chega.

---

## 1. Arquitetura (referência rápida)

| Container     | Imagem                  | Porta (host)        | Responsabilidade                               |
|---------------|-------------------------|---------------------|------------------------------------------------|
| `app`         | `laravel-app` (build)   | —                   | PHP 8.2-FPM / Laravel 12                        |
| `webserver`   | `nginx:alpine`          | `8000:80`           | HTTP / proxy FastCGI para `app:9000`           |
| `db`          | `mysql:8.0`             | `3306:3306`         | Banco relacional (`govcert_dev` em dev)        |
| `redis`       | `redis:7-alpine`        | `6379:6379`         | Filas + cache + sessão                          |
| `queue_worker`| `laravel-app`           | —                   | `queue:work redis --queue=gemini,default`      |
| `phpmyadmin`  | `phpmyadmin/phpmyadmin` | `127.0.0.1:8080:80` | Admin MySQL (somente localhost)                |
| `mailpit`     | `axllent/mailpit`       | `1025` / `8025`     | Captura de e-mails em dev                       |

- Rede `laravel-network` (bridge); resolução por nome (`db`, `redis`, `mailpit`).
- Volumes persistentes: `dbdata`, `redisdata`.
- Job `ProcessAuditLog`: fila `gemini`, `tries = 3`, `timeout = 60s`.
- `GEMINI_API_KEY` no `.env` (lida em `config/services.php`).

---

## 2. Pré-requisitos e Setup com Docker

### 2.1 Pré-requisitos

- Docker Engine + Docker Compose v2 (`docker compose`); v1 (`docker-compose`) também serve.
- Portas livres: `8000`, `3306`, `6379`, `8080` (localhost), `8025`.
- Windows: Docker Desktop com **WSL 2** como backend.

> **Windows / Git Bash:** ao passar caminhos absolutos do container
> (`/var/www/...`) para `docker exec`, prefixe com `MSYS_NO_PATHCONV=1`.

### 2.2 Subida inicial

```bash
docker compose build app                       # 1ª vez / mudou o Dockerfile
docker compose up -d                           # sobe os 7 serviços
docker compose ps                              # confirme todos "Up"
docker compose exec app composer install       # Windows: -e COMPOSER_PROCESS_TIMEOUT=2000
docker compose exec app cp -n .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force        # OBRIGATÓRIO: cria usuários admin/auditor e dados iniciais
docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

> ⚠️ **Permissões de storage são obrigatórias.** Sem elas, o Laravel não consegue
> escrever em `laravel.log` nem em `bootstrap/cache` e lança um erro 500 com
> `UnexpectedValueException: The stream or file [...] StreamHandler.php` na
> primeira requisição. Execute os dois comandos acima logo após o `db:seed`.

### 2.3 Variáveis de ambiente (`src/.env`)

```dotenv
APP_URL=http://localhost:8000        # PRODUÇÃO: https://seu-dominio  (ver alerta de SSL)
QUEUE_CONNECTION=redis               # OBRIGATÓRIO ser redis
CACHE_STORE=redis
REDIS_HOST=redis
DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=govcert_dev              # PRODUÇÃO: use um nome/credenciais próprios
DB_USERNAME=govcert_user             # PRODUÇÃO: NÃO use estas credenciais de dev
DB_PASSWORD=root                     # PRODUÇÃO: senha forte via secret manager
GEMINI_API_KEY=                      # <<< PREENCHA, senão todo job falha
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

> 🔑 **Aviso de credenciais:** `govcert_user` / `root` / `govcert_dev` são valores
> **exclusivos de desenvolvimento**. Em produção, use um usuário de banco dedicado,
> senha forte gerenciada por *secret manager* (Vault, AWS Secrets Manager, etc.) e
> **nunca** versione o `.env` de produção.

### 2.4 Deploy / atualização

```bash
git pull
docker compose build app
docker compose up -d
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan queue:restart
```

> **Regra de ouro do worker:** o `queue_worker` mantém o código PHP em memória.
> Todo deploy que toque em Jobs/Models/config exige `php artisan queue:restart`
> (ou `docker compose restart queue_worker`).

---

## 3. Operações de Manutenção Diária

### 3.1 Cache

```bash
docker compose exec app php artisan optimize:clear         # limpa tudo
docker compose exec app php artisan config:clear           # após alterar .env
docker compose exec app php artisan optimize               # recria caches (produção)
```

### 3.2 Filas / Worker

```bash
docker compose exec app php artisan queue:restart          # recarrega o worker
docker compose restart queue_worker                        # hard restart do container
docker compose logs -f queue_worker                        # logs em tempo real
docker compose exec redis redis-cli LLEN queues:gemini     # tamanho da fila
docker compose exec app php artisan queue:failed           # jobs que falharam
```

### 3.3 Migrations / banco

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan migrate:status
docker compose exec app php artisan migrate:rollback --step=1     # CUIDADO
```

### 3.4 Logs e testes

```bash
docker compose exec app tail -n 200 -f storage/logs/laravel.log
docker compose logs -f app
docker compose exec app php artisan test                          # SQLite em memória
```

---

## 4. Troubleshooting Guiado

### Cenário A — Logs travados em "Pendente"

**Sintoma:** registros em `audit_logs` ficam em `status = pending`; o KPI
"Aguardando Análise" só cresce.

**Causa raiz:** `queue_worker` parado/em loop/código antigo; ou `QUEUE_CONNECTION`
não é `redis`.

```bash
docker compose ps queue_worker                                  # "Up"?
docker compose logs --tail=100 queue_worker                     # erros?
docker compose exec redis redis-cli LLEN queues:gemini          # fila não esvazia?
docker compose exec app php artisan tinker --execute="echo config('queue.default');"  # redis?
```

**Solução:**

```bash
docker compose restart queue_worker
# Se QUEUE_CONNECTION estava errado:
docker compose exec app php artisan config:clear
docker compose restart queue_worker
```

> Worker em *Restarting* → quase sempre `.env` ausente em `src/`, Redis/DB
> inacessível, ou `GEMINI_API_KEY` causando exceção fatal.

---

### Cenário B — Falhas na API do Gemini (HTTP 429 / Timeout)

**Sintoma:** jobs em *failed*; `laravel.log` com `ProcessAuditLog failed`
(`429 Too Many Requests` ou estouro do `timeout` de 60s). Após `tries = 3`,
vão para `failed_jobs`.

```bash
docker compose exec app grep -n "ProcessAuditLog failed" storage/logs/laravel.log | tail -20
docker compose exec app php artisan queue:failed
docker compose exec app php artisan tinker --execute="echo config('services.gemini.api_key') ? 'OK' : 'VAZIA';"
```

**Solução:**

1. **Chave ausente/inválida:** preencha `GEMINI_API_KEY` em `src/.env` →
   `php artisan config:clear` → `docker compose restart queue_worker`.
2. **Rate limit (429):** aguarde a quota resetar (não reprocesse em massa de
   imediato). Se recorrente, aumente `--sleep` do `queue_worker` no
   `docker-compose.yml` e `docker compose up -d queue_worker`.
3. **Reprocessar após corrigir:**
   ```bash
   docker compose exec app php artisan queue:retry all
   docker compose exec app php artisan queue:retry <uuid>
   docker compose exec app php artisan queue:flush      # limpa a lista de falhos
   ```

---

### Cenário C — A Extensão do Chrome não consegue enviar dados

**Sintoma:** nada chega em `audit_logs`; no DevTools (Console) há erro de
**Mixed Content**, CORS, `401 Unauthorized` ou `Failed to fetch`.

**Diagnóstico (Console da aba → filtre por `[GovCert]`):**

- **Mixed Content / requisição bloqueada** → API em HTTP atrás de página HTTPS.
  **Ver o alerta de SSL no topo deste documento.** É a causa nº 1 em produção.
- `401 Unauthorized` → token Sanctum inválido/revogado ou usuário desativado.
- Erro de **CORS / preflight** → origem da extensão bloqueada.
- `Failed to fetch` → URL da API errada nas Opções, app fora do ar, ou DNS/TLS.

**Solução:**

1. **HTTPS:** garanta certificado válido e `APP_URL` https (alerta do topo).
2. **Token Sanctum:** verifique o token nas Opções; tokens não expiram, então o
   problema costuma ser **revogado** ou usuário `is_active = false`
   (middleware `CheckUserStatus`):
   ```bash
   docker compose exec app php artisan tinker --execute="echo \App\Models\User::where('email','admin@govcert.gov.br')->first()->tokens()->count();"
   ```
3. **CORS (`src/config/cors.php`):** `/api/*` deve permitir a origem
   `chrome-extension://...`. Em dev, `allowed_origins => ['*']`; em produção,
   restrinja ao ID da extensão. Aplique com `php artisan config:clear`.
   A API usa token **Bearer** (stateless) — não depende de
   `SANCTUM_STATEFUL_DOMAINS`.
4. Se a captura quebrou em **apenas uma** plataforma de IA, não é infra: é
   mudança de DOM — ver "Aviso de Manutenção de Frontend" no `README.md`.

---

### Cenário D — Banco/Redis sem memória + Governança de Retenção

**Sintoma:** containers `db`/`redis` reiniciando; `OOM`,
`OOM command not allowed` (Redis); crescimento ilimitado de `audit_logs`.

**Diagnóstico:**

```bash
docker stats --no-stream
docker compose exec redis redis-cli INFO memory | grep used_memory_human
docker compose exec app php artisan tinker --execute="echo \App\Models\AuditLog::count().' audit / '.\App\Models\ActivityLog::count().' activity';"
```

#### 4.D.1 ❌ NÃO faça expurgo manual via `tinker`

> **Correção de governança.** O antigo procedimento mandava rodar
> `AuditLog::where(...)->delete()` manualmente no `tinker`. **Isso é proibido**
> em produção: é uma operação destrutiva, sem trilha, sem revisão, propensa a
> erro humano (apagar a janela errada) e sem reprodutibilidade — inaceitável
> num sistema de **auditoria/governança**.

#### 4.D.2 ✅ Retenção via Laravel Task Scheduling (Cron)

A limpeza de registros com **mais de 90 dias** deve ser **agendada e auditável**,
executada pelo *Task Scheduler* do Laravel.

**Passo 1 — Declarar o agendamento** em `src/routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;
use App\Models\AuditLog;
use App\Models\ActivityLog;

// Retenção: expurga audit_logs > 90 dias e trilha de atividade > 180 dias.
// Roda diariamente às 03:00, sem sobreposição, com saída registrada em log.
Schedule::call(function () {
    AuditLog::where('created_at', '<', now()->subDays(90))->delete();
    ActivityLog::where('created_at', '<', now()->subDays(180))->delete();
})->dailyAt('03:00')
  ->name('purge-retencao')
  ->withoutOverlapping()
  ->onOneServer();
```

**Passo 2 — Garantir que o scheduler roda.** O Laravel só executa o agendamento
se algo invocar `php artisan schedule:run` a cada minuto. Em Docker há **duas**
abordagens — escolha uma:

- **(Recomendado) Container/serviço dedicado** rodando o scheduler em loop
  (análogo ao `queue_worker`), adicionando ao `docker-compose.yml`:

  ```yaml
    scheduler:
      image: laravel-app
      container_name: scheduler
      restart: unless-stopped
      working_dir: /var/www
      volumes:
        - ./src:/var/www
      command: php artisan schedule:work
      depends_on:
        - app
        - db
        - redis
      networks:
        - laravel-network
  ```
  Suba com `docker compose up -d scheduler`.

- **(Alternativa) Cron do host** chamando o artisan dentro do container:

  ```cron
  * * * * * docker compose -f /caminho/docker-compose.yml exec -T app php artisan schedule:run >> /var/log/govcert-schedule.log 2>&1
  ```

**Passo 3 — SysAdmin: como verificar se o Cron/Scheduler está rodando.**

```bash
# (a) O agendamento está registrado e com próximo horário?
docker compose exec app php artisan schedule:list

# (b) Teste a tarefa manualmente, sem esperar as 03:00
docker compose exec app php artisan schedule:test
#     escolha a tarefa "purge-retencao" e confira a saída

# (c) Se usa o container 'scheduler', ele está de pé e logando a cada minuto?
docker compose ps scheduler
docker compose logs --tail=50 scheduler
#     'schedule:work' imprime uma linha de execução por minuto.

# (d) Se usa cron do HOST, o serviço cron está ativo e há registro de execução?
systemctl status cron        # (ou 'crond' conforme a distro)
crontab -l                   # a linha do schedule:run existe?
tail -n 50 /var/log/govcert-schedule.log

# (e) Auditar o efeito: a contagem deve cair após a janela das 03:00
docker compose exec app php artisan tinker --execute="echo \App\Models\AuditLog::where('created_at','<',now()->subDays(90))->count().' registros ainda fora da retenção';"
#     Esperado tender a 0 após a execução agendada.
```

> Se `schedule:list` mostra a tarefa mas ela **nunca executa**, o problema é o
> **Passo 2** (ninguém está chamando `schedule:run`): nenhum container
> `scheduler` no ar **ou** cron do host inativo. Esse é o erro de governança
> mais comum — o agendamento existe no código, mas o gatilho não roda.

#### 4.D.3 Pressão de memória no Redis (mitigação imediata)

```bash
docker compose exec redis redis-cli LLEN queues:gemini
docker compose exec app php artisan cache:clear        # limpa só o cache, preserva filas
docker compose restart redis                           # filas/cache reconectam sozinhos
```

> **Nunca** rode `FLUSHALL` em produção (apaga filas + cache + sessões juntos).

#### 4.D.4 Reiniciar contêineres isolados (sem derrubar a app)

```bash
docker compose restart redis
docker compose restart db
docker compose restart queue_worker
```

---

### Cenário E — Login com admin falha ("credenciais incorretas")

**Sintoma:** a tela de login retorna *"As credenciais fornecidas estão incorretas"*
mesmo usando `admin@govcert.gov.br` / `admin123`. As migrations rodaram sem erro.

**Causa raiz:** o `db:seed` nunca foi executado. As migrations criam apenas o
**schema** (tabelas/colunas); os usuários (admin, auditor) são inseridos pelo
`DatabaseSeeder`. Sem o seed, o banco fica vazio e qualquer login falha.

**Diagnóstico — confirme que não há nenhum usuário:**

```bash
docker compose exec app php artisan tinker --execute="echo \App\Models\User::count().' usuário(s) cadastrado(s)';"
# Saída esperada se o seed não rodou: "0 usuário(s) cadastrado(s)"
```

**Solução — rode o seed:**

```bash
docker compose exec app php artisan db:seed --force
```

Saída esperada:

```
Seed concluído: 2 admins, 5 auditores e 150 logs realistas (60 dias).
Login admin:   admin@govcert.gov.br / admin123
Login auditor: auditor@govcert.gov.br / auditor123
```

**Confirme que os usuários foram criados:**

```bash
docker compose exec app php artisan tinker --execute="echo \App\Models\User::count().' usuário(s) cadastrado(s)';"
# Esperado: 7 usuário(s) cadastrado(s)
```

> **Observação:** rodar `db:seed` duas vezes gera e-mails duplicados (constraint
> unique). Se o seed falhar por isso, rode `php artisan migrate:fresh --seed --force`
> (recria todas as tabelas e aplica o seed do zero) — **atenção: apaga todos os dados**.

**Por que isso acontece?** A `Subida inicial` na seção 2.2 lista o `db:seed` como
passo **obrigatório**. Se ele foi pulado (considerado "opcional"), o schema existe
mas não há dados de acesso. Sempre execute o seed logo após o `migrate`.

---

### Cenário F — Erro 500 imediato após subida (StreamHandler.php / permissão de escrita)

**Sintoma:** qualquer requisição ao app retorna HTTP 500. O `docker compose logs app`
(ou `docker compose logs webserver`) mostra uma exceção similar a:

```
UnexpectedValueException: The stream or file "/var/www/storage/logs/laravel.log"
could not be opened in append mode: Failed to open stream: Permission denied
(View: /var/www/storage/framework/views/...)
in /var/www/vendor/monolog/monolog/src/Monolog/Handler/StreamHandler.php
```

**Causa raiz:** o volume `./src` é montado pelo Docker e os diretórios
`storage/` e `bootstrap/cache/` pertencem ao usuário do host, não ao
`www-data` (uid 33) que o PHP-FPM usa dentro do container. O Laravel não
consegue abrir o arquivo de log nem gerar views compiladas.

**Solução — execute os dois comandos (sempre juntos):**

```bash
docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

**Verificação:**

```bash
# Deve listar "www-data www-data" como dono/grupo
docker compose exec app ls -la /var/www/storage/logs/

# Uma requisição simples deve retornar 200
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000
```

> **Quando ocorre?** Principalmente na primeira subida (`docker compose up`),
> após clonar o repositório em Linux/macOS com outro usuário de host, ou após
> `git checkout` que recrie arquivos com permissão do usuário host.
> No **Windows com Docker Desktop + WSL2** o mapeamento de uid é transparente e
> o problema raramente aparece, mas os comandos são seguros de rodar em qualquer OS.

> **Por que dois comandos?** `chown` corrige o dono (www-data precisa ser dono
> para criar subdiretórios); `chmod 775` garante que o grupo também possa
> escrever, necessário quando outros processos no container (ex: artisan via
> root) precisam acessar os mesmos arquivos.

---

### Apêndice — "Access denied for user 'govcert_user'"

**Sintoma:** `php artisan migrate` falha com
`SQLSTATE[HY000] [1045] Access denied for user 'govcert_user'@'...'`.

**Causa:** o volume `dbdata` foi inicializado com credenciais antigas. O MySQL só
cria usuário/banco das variáveis `MYSQL_*` no **primeiro boot com volume vazio**.

**Solução não-destrutiva** (cria usuário/banco no MySQL em execução, como root):

```bash
docker exec db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS govcert_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'govcert_user'@'%' IDENTIFIED BY 'root'; GRANT ALL PRIVILEGES ON govcert_dev.* TO 'govcert_user'@'%'; FLUSH PRIVILEGES;"
docker compose exec app php artisan migrate --force
```

> 🔑 **Aviso de credenciais (produção):** o comando acima usa `root/root` e
> `govcert_user/root` por serem **padrões de desenvolvimento**. Em produção,
> **jamais** use estes valores: utilize o usuário root real protegido, um usuário
> de aplicação com privilégios mínimos no schema, e senhas fortes vindas do
> *secret manager*. Não cole credenciais de produção em comandos shell (ficam no
> histórico) — prefira `MYSQL_PWD` temporário ou um arquivo `.my.cnf` protegido.

**Alternativa destrutiva** (recria o volume — **perde todos os dados**, apenas dev):

> ⚠️ O Docker Compose **prefixa o nome do volume com o nome da pasta do projeto**
> (em minúsculas, sem caracteres especiais). **Não** assuma `policy_engine_br_dbdata`
> — esse nome varia conforme a pasta onde o repositório foi clonado e o comando
> falhará com *"no such volume"* se você chutar. Sempre **descubra o nome real**
> antes de remover.

```bash
docker compose down

# 1. Descubra o nome REAL do volume (o prefixo é o nome da sua pasta de projeto):
docker volume ls | grep dbdata
#    Exemplos possíveis conforme a pasta:
#      <nome_da_pasta>_dbdata   →   govcert_dbdata, policyenginebr_dbdata, etc.

# 2. Remova usando EXATAMENTE o nome listado no passo 1:
docker volume rm <nome_da_pasta>_dbdata

# 3. Recrie do zero:
docker compose up -d
docker compose exec app php artisan migrate --seed --force
```

### Outros problemas comuns

- **Porta 8000/3306/8080 em uso:** pare o serviço conflitante ou altere o
  mapeamento em `docker-compose.yml`.
- **Timeout no `composer install` (Windows):**
  `docker compose exec -e COMPOSER_PROCESS_TIMEOUT=2000 app composer install`;
  mantenha o projeto no filesystem do WSL2.
- **Permissão em log/cache:** veja o **Cenário F** abaixo (erro 500 no StreamHandler.php).

---

## 5. Estrutura do Repositório

```
.
├── README.md                   # Produto + manual da extensão (usuários/auditores)
├── RUNBOOK.md                  # Este arquivo (Infra/DevOps)
├── docker-compose.yml          # app, webserver, db, redis, phpmyadmin, queue_worker, mailpit
├── docker/
│   ├── php/Dockerfile          # PHP 8.2-FPM + extensões + redis (PECL) + Composer
│   └── nginx/default.conf      # Vhost Nginx -> app:9000
├── chrome-extension/           # Extensão Manifest V3 (content/background/options/popup + icons)
└── src/                        # Aplicação Laravel 12
    ├── app/Http/Controllers/   # Api\AuditController, Web\*, Admin\*
    ├── app/Jobs/ProcessAuditLog.php
    ├── app/Models/             # AuditLog, ActivityLog, User
    ├── app/Http/Middleware/    # CheckUserStatus ("active"), IsAdmin ("admin")
    ├── database/migrations/
    ├── database/seeders/
    └── routes/{web,api,console}.php
```

---

*GovCert — uso interno restrito. Segredos (`GEMINI_API_KEY`, credenciais de banco,
tokens Sanctum) **fora** do controle de versão e em *secret manager* na produção.*

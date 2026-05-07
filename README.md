# POLICY_ENGINE_BR

---

## ❓ Resolução de Problemas Comuns

* **A porta 8000 ou 3306 já está em uso:** Outro serviço na sua máquina (como o XAMPP, WAMP ou outro contêiner) está usando essa porta. Pare o outro serviço ou altere a porta no arquivo `docker-compose.yml`.
* **Timeout ao instalar pacotes no Windows:** Ocorre pela lentidão de escrita entre o disco do Windows e o Docker. Use o comando com tempo estendido: `docker-compose exec -e COMPOSER_PROCESS_TIMEOUT=2000 app composer install`. Para resolver permanentemente,
```markdown
# Base Laravel com Docker (NGINX, PHP 8.2 e MySQL 8.0)

Este repositório contém a infraestrutura baseada em Docker para rodar uma aplicação Laravel localmente, garantindo que o ambiente seja idêntico, isolado e fácil de configurar, independentemente do sistema operacional.

---

## 🛠️ Pré-requisitos

Antes de começar, certifique-se de ter as seguintes ferramentas instaladas no seu sistema:

### 🪟 Windows
* **[Docker Desktop](https://www.docker.com/products/docker-desktop)** instalado e rodando.
* **WSL 2 (Windows Subsystem for Linux)** ativado e configurado como backend do Docker Desktop (altamente recomendado para evitar lentidão na leitura e escrita de arquivos).

### 🐧 Linux
* **[Docker Engine](https://docs.docker.com/engine/install/)** instalado.
* **[Docker Compose](https://docs.docker.com/compose/install/)** instalado (nas versões mais recentes, o comando `docker compose` já vem embutido na CLI do Docker, mas `docker-compose` como pacote isolado também funciona).
* *(Opcional, mas recomendado)* Configure o Docker para rodar sem `sudo` (`sudo usermod -aG docker $USER`).

### 🍎 macOS
* **[Docker Desktop para Mac](https://www.docker.com/products/docker-desktop)** instalado e rodando.

---

## 🚀 Instalação (Primeira Vez)

Siga os passos abaixo para configurar e rodar o projeto pela primeira vez. Abra o terminal (no Windows, dê preferência ao terminal do WSL ou PowerShell) na raiz do projeto e execute:

**1. Suba os contêineres e construa a imagem do PHP:**
```bash
docker-compose up -d --build
```
*Isso vai baixar as imagens do NGINX e MySQL e construir a imagem do nosso app com PHP e Composer.*

**2. Instale as dependências do Laravel:**
```bash
docker-compose exec app composer install
```

**3. Configure as variáveis de ambiente:**
Copie o arquivo de exemplo para criar o seu `.env` local:
* **Linux / macOS:** `cp src/.env.example src/.env`
* **Windows (PowerShell):** `copy src\.env.example src\.env`

Abra o arquivo `src/.env` e certifique-se de que a configuração do banco de dados está apontando para o contêiner do MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=root
```

**4. Gere a chave da aplicação Laravel:**
```bash
docker-compose exec app php artisan key:generate
```

**5. Rode as migrações do banco de dados:**
```bash
docker-compose exec app php artisan migrate
```

**6. Ajuste de Permissões (Apenas Linux e macOS):**
Para que o Laravel consiga gravar arquivos de cache e log corretamente, execute:
```bash
docker-compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
```

🎉 **Tudo pronto!** Acesse a aplicação no seu navegador: [http://localhost:8000](http://localhost:8000)

---

## ⚙️ Comandos do Dia a Dia

Aqui estão os comandos que você utilizará na sua rotina de desenvolvimento. Eles funcionam da mesma forma no Windows (PowerShell/WSL), Linux e Mac.

### Iniciar o ambiente
Inicia os contêineres em segundo plano (background).
```bash
docker-compose up -d
```

### Parar o ambiente
Para os contêineres de forma segura, mantendo o banco de dados e os volumes intactos.
```bash
docker-compose down
```

### Reiniciar o ambiente
Útil quando você precisa recarregar alguma configuração de rede ou do Docker.
```bash
docker-compose restart
```

### Resetar o ambiente (⚠️ Cuidado)
Destrói todos os contêineres e **apaga o banco de dados** (remove os volumes). Excelente para começar com o banco do zero.
```bash
docker-compose down -v
```
*(Após resetar, ao rodar `docker-compose up -d`, você precisará rodar `docker-compose exec app php artisan migrate` novamente).*

### Visualizar os Logs
Para ver o que está acontecendo nos contêineres em tempo real:
* Todos os serviços: `docker-compose logs -f`
* Apenas do PHP: `docker-compose logs -f app`
* Apenas do Banco de Dados: `docker-compose logs -f db`
* Apenas do Servidor Web: `docker-compose logs -f webserver`

### Acessar o terminal do contêiner PHP
Se você precisar rodar comandos do `artisan`, `composer` ou navegar pelos arquivos diretamente por dentro do servidor:
```bash
docker-compose exec app bash
```

---

## 🛠️ Comandos do Laravel (Artisan e Composer)

Como o PHP e o Composer estão instalados dentro do contêiner `app`, você sempre deve prefixar seus comandos com `docker-compose exec app`. 

**Exemplos comuns:**

Criar um Controller:
```bash
docker-compose exec app php artisan make:controller UserController
```

Criar uma Migration:
```bash
docker-compose exec app php artisan make:migration create_users_table
```

Instalar um novo pacote via Composer:
```bash
docker-compose exec app composer require guzzlehttp/guzzle
```

Limpar os caches da aplicação:
```bash
docker-compose exec app php artisan optimize:clear
```

---

## ❓ Resolução de Problemas Comuns

* **A porta 8000 ou 3306 já está em uso:** Outro serviço na sua máquina (como o XAMPP, WAMP ou outro contêiner) está usando essa porta. Pare o outro serviço ou altere a porta no arquivo `docker-compose.yml`.
* **Timeout ao instalar pacotes no Windows:** Ocorre pela lentidão de escrita entre o disco do Windows e o Docker. Use o comando com tempo estendido: `docker-compose exec -e COMPOSER_PROCESS_TIMEOUT=2000 app composer install`. Para resolver permanentemente, garanta que o projeto está dentro de uma pasta do WSL2 (ex: `\\wsl$\Ubuntu\home\usuario\seu-projeto`).
* **Erro de permissão no log/cache (Linux/Mac):** Execute o comando de ajuste de permissões (passo 6 da instalação) ou use `chmod -R 777 src/storage src/bootstrap/cache` na raiz do projeto (apenas em desenvolvimento).
```
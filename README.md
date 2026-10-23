# Economy API

API REST em PHP para catalogar produtos e fontes de preços, registrar coletas e consultar estatísticas e histórico de mercado. O projeto também inclui um painel administrativo PHP servido junto da aplicação.

## Funcionalidades

- Cadastro e autenticação de usuários com tokens JWT.
- Catálogo de categorias, produtos e fontes de dados.
- Registro e consulta de preços coletados, com filtros por produto e fonte.
- Cálculo de estatísticas por produto e local, incluindo média, mínimo, máximo, preço atual e variação.
- Persistência e consulta de snapshots do histórico de preços.
- Controle de acesso por perfil: operações de escrita exigem `admin` ou `manager`; usuários cadastrados publicamente recebem o perfil `client`.
- Painel administrativo em `/admin`, que consome a API usando sessões PHP.

As rotas de consulta dos catálogos e do histórico são públicas. O cálculo e a gravação de snapshots requerem autenticação com perfil autorizado. A listagem e consulta de mercados ainda não estão implementadas no serviço.

## Tecnologias

- **PHP 8.1+**: linguagem da aplicação, com autoload PSR-4 para as classes `App\`.
- **MySQL**: persistência relacional; o acesso é feito com PDO e prepared statements.
- **FastRoute**: registro e despacho das rotas HTTP.
- **Firebase PHP-JWT**: criação e validação dos tokens JWT com HS256.
- **PHP dotenv**: carregamento de configurações locais a partir de `.env`.
- **cURL**: comunicação do painel administrativo com a API.
- **PHPUnit 10.5+**: testes unitários e de integração.

Não há framework de aplicação nem framework JavaScript no projeto: a API e o painel usam PHP diretamente.

## Estrutura do projeto

```text
.
├── bin/                  # Criação do banco e execução das migrações
├── config/               # Configurações da aplicação, como JWT
├── docs/                 # Referência da API e orientações de produção
├── public/               # Document root e ponto de entrada HTTP
│   ├── admin/            # Painel administrativo e seus recursos
│   └── index.php         # Inicialização e despacho da API
├── src/
│   ├── Controllers/      # Tratamento das requisições da API e do painel
│   ├── Database/         # Conexão PDO e migrações SQL
│   ├── Helpers/          # Funções auxiliares
│   ├── Middleware/       # Autenticação JWT e autorização por perfil
│   ├── Models/           # Acesso e operações sobre entidades do banco
│   ├── Routes/           # Definição das rotas da API
│   └── Services/         # Regras de negócio, autenticação e preços
├── storage/logs/         # Logs da aplicação
├── tests/                # Testes unitários e de integração
├── vendor/               # Dependências gerenciadas pelo Composer
├── .env.example          # Exemplo de configuração local
└── composer.json         # Dependências, autoload e comando de testes
```

O fluxo da API começa em `public/index.php`, que carrega o ambiente, prepara os headers e encaminha método e URL às rotas de `src/Routes/api.php`. As rotas chamam controllers; regras de negócio ficam nos services, e os models acessam o MySQL pela conexão PDO. O painel em `public/admin/` mantém sua própria sessão e faz requisições HTTP à API.

## Requisitos

- PHP 8.1 ou superior com as extensões `curl`, `mbstring` e `pdo_mysql`.
- Composer.
- MySQL 8.0 ou compatível com InnoDB, chaves estrangeiras e `utf8mb4`.

## Configuração e execução local

A seguir está o fluxo recomendado para rodar a aplicação em um ambiente local de desenvolvimento.

### 1) Requisitos do ambiente

Antes de iniciar, certifique-se de que o computador tenha:

- PHP 8.1 ou superior
- Extensões PHP habilitadas: `curl`, `mbstring` e `pdo_mysql`
- Composer instalado
- MySQL 8.0 ou compatível
- Acesso ao cliente MySQL (pelo menos para criar o banco e executar as migrações)

Verifique as versões:

```bash
php -v
composer --version
mysql --version
```

### 2) Crie o arquivo de ambiente

Na raiz do projeto, copie o exemplo de configuração para um arquivo `.env`:

```bash
cp .env.example .env
```

No PowerShell:

```powershell
Copy-Item .env.example .env
```

Edite o `.env` e ajuste os valores do banco e do JWT. O mínimo necessário é:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=economy_api
DB_USERNAME=seu_usuario_mysql
DB_PASSWORD=sua_senha_mysql

JWT_SECRET=um_segredo_aleatorio_com_mais_de_32_bytes
JWT_EXPIRATION=3600
```

Dica: `JWT_SECRET` deve ser uma string aleatória e não reutilizar o valor do exemplo.

### 3) Instale as dependências

```bash
composer install
```

Se o Composer pedir para atualizar o pacote de dependência ou não encontrar o PHP correto, confirme que o `php` e o `composer` do ambiente estejam no mesmo runtime.

### 4) Prepare o banco MySQL

Crie o banco e execute as migrações do projeto:

```bash
php bin/create_database.php
php bin/migrate.php
```

Esses comandos criam o schema e as tabelas exigidas pela API. Caso o MySQL não esteja rodando, o processo vai falhar com erro de conexão; nesse caso, inicie o serviço do MySQL e confirme as credenciais do `.env`.

### 5) Inicie a aplicação

Na raiz do projeto, rode:

```bash
php -S localhost:8000 -t public
```

Esse comando faz o PHP servir a pasta `public/` como raiz da aplicação. Com isso, a API fica disponível em:

- API: `http://localhost:8000`
- Painel administrativo: `http://localhost:8000/admin/`

O painel usa `APP_URL` para encontrar a API; mantenha esse valor alinhado com o endereço local que você está usando.

### 6) Verifique se a aplicação está respondendo

Uma checagem rápida é:

```bash
curl http://localhost:8000/api/health
```

Se a API estiver funcionando, a resposta deve vir em JSON. Esse endpoint confirma que o servidor responde, mas não garante que o banco está conectado.

### 7) Fluxo rápido de uso

1. Siga o [guia de uso](docs/GUIA_DE_USO.md) para criar o primeiro administrador e acessar o painel.
2. No painel, cadastre categorias, produtos e fontes antes de registrar preços.
3. Contas `client` podem se cadastrar e consultar a API pública, mas não têm acesso ao painel administrativo.
4. Para chamadas de escrita, autentique-se e use uma conta `admin` ou `manager`.

### Problemas comuns

- `PDOException` ou erro de conexão com o banco: confirme `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env` e verifique se o MySQL está ativo.
- `JWT_SECRET` inválido: use um segredo aleatório com pelo menos 32 bytes e atualize o `.env`.
- `404` em rotas da API: confirme se o servidor foi iniciado com `-t public` e se a URL está correta.
- `admin` não consegue consumir a API: abra a configuração do painel e confirme que `APP_URL` aponta para o mesmo host e porta onde a API está rodando.

## Testes

Execute a suíte configurada no Composer:

```bash
composer test
```

Os testes estão separados em `tests/Unit/` e `tests/Integration/`.

## Documentação

- [Guia de uso](docs/GUIA_DE_USO.md): instalação local, primeiro acesso, perfis, painel e exemplos PowerShell.
- [Referência da API](docs/API.md): endpoints, autenticação, payloads, filtros e respostas de erro.
- [Preparação para produção](docs/DEPLOYMENT.md): configuração de hospedagem, segurança, migrações e operação.



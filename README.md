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

Na raiz do projeto:

1. Crie o arquivo `.env` a partir de `.env.example` e configure banco de dados e JWT. Use um segredo aleatório com pelo menos 32 bytes; não use o valor ilustrativo do exemplo.

	```bash
	cp .env.example .env
	```

	No PowerShell, a cópia equivalente é:

	```powershell
	Copy-Item .env.example .env
	```

2. Instale as dependências:

	```bash
	composer install
	```

3. Crie o banco configurado e aplique as migrações:

	```bash
	php bin/create_database.php
	php bin/migrate.php
	```

4. Inicie o servidor de desenvolvimento com `public/` como raiz:

	```bash
	php -S localhost:8000 -t public
	```

A API estará disponível em `http://localhost:8000`, e o painel em `http://localhost:8000/admin/`. O painel usa `APP_URL` para encontrar a API; mantenha esse valor alinhado com o endereço usado localmente.

O endpoint `GET /api/health` confirma que a aplicação está respondendo, mas não verifica a conexão com o banco.

## Testes

Execute a suíte configurada no Composer:

```bash
composer test
```

Os testes estão separados em `tests/Unit/` e `tests/Integration/`.

## Documentação

- [Referência da API](docs/API.md): endpoints, autenticação, payloads, filtros e respostas de erro.
- [Preparação para produção](docs/DEPLOYMENT.md): configuração de hospedagem, segurança, migrações e operação.



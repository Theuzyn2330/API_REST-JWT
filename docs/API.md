# Economy API

API REST em JSON. Os exemplos usam `http://localhost:8000` como base URL.

## Inicialização

1. Copie `.env.example` para `.env` e configure as credenciais MySQL e um `JWT_SECRET` aleatório com pelo menos 32 bytes.
2. Instale as dependências com `composer install`.
3. Crie a base configurada com `php bin/create_database.php`.
4. Aplique as migrações com `php bin/migrate.php`.
5. Inicie a API local com `php -S localhost:8000 -t public`.

O endpoint `GET /api/health` verifica a disponibilidade da aplicação, não a conectividade com o banco.

## Autenticação

Login em `POST /api/auth/login`. Envie o token retornado em rotas protegidas:

```http
Authorization: Bearer <access_token>
```

Os tokens usam HS256 e expiram segundo `JWT_EXPIRATION`. Tokens incluem `sub`, `iat`, `nbf`, `exp` e a role do usuário. Senhas nunca são incluídas.

As operações de escrita de categorias, produtos, fontes, preços e recálculo do histórico exigem role `admin` ou `manager`. Leitura de catálogos e histórico é pública. Novos usuários recebem role `client` por padrão.

## Endpoints

### Sistema e autenticação

| Método | URL | Auth | Descrição |
|---|---|---|---|
| GET | `/api/health` | Não | Estado da API. |
| POST | `/api/auth/register` | Não | Cadastra usuário. |
| POST | `/api/auth/login` | Não | Valida credenciais e emite JWT. |
| GET | `/api/profile` | Bearer | Retorna o perfil autenticado, sem hash da senha. |

Cadastro:

```json
{"name":"Ana Silva","email":"ana@example.com","password":"uma-senha-segura"}
```

Login:

```json
{"email":"ana@example.com","password":"uma-senha-segura"}
```

Resposta de login: `user`, `token_type`, `access_token` e `expires_in`.

### Categorias

| Método | URL | Auth | Descrição |
|---|---|---|---|
| GET | `/api/categories` | Não | Lista categorias. |
| GET | `/api/categories/{id}` | Não | Consulta categoria. |
| POST | `/api/categories` | Admin/manager | Cria categoria. |
| PUT | `/api/categories/{id}` | Admin/manager | Atualiza categoria. |
| DELETE | `/api/categories/{id}` | Admin/manager | Remove categoria; retorna conflito se estiver em uso. |

Body de criação/atualização: `name` obrigatório, `slug` opcional e `description` opcional. O slug é derivado do nome quando omitido.

### Produtos

| Método | URL | Auth | Descrição |
|---|---|---|---|
| GET | `/api/products` | Não | Lista produtos; aceita `category_id` e `active=true|false`. |
| GET | `/api/products/{id}` | Não | Consulta produto. |
| POST | `/api/products` | Admin/manager | Cria produto. |
| PUT | `/api/products/{id}` | Admin/manager | Atualiza produto. |
| DELETE | `/api/products/{id}` | Admin/manager | Desativa produto sem apagar seus dados. |

Body de criação/atualização: `category_id`, `name` e `unit` obrigatórios; `slug`, `description` e `active` opcionais.

### Fontes

| Método | URL | Auth | Descrição |
|---|---|---|---|
| GET | `/api/sources` | Não | Lista fontes. |
| GET | `/api/sources/{id}` | Não | Consulta fonte. |
| POST | `/api/sources` | Admin/manager | Cria fonte. |
| PUT | `/api/sources/{id}` | Admin/manager | Atualiza fonte. |
| DELETE | `/api/sources/{id}` | Admin/manager | Desativa fonte. |

Body: `name` e `type` obrigatórios; `url` e `active` opcionais. Tipos: `api`, `manual`, `automated_collection`, `external_database`. URLs devem usar HTTP ou HTTPS.

### Preços

| Método | URL | Auth | Descrição |
|---|---|---|---|
| GET | `/api/prices` | Não | Lista até 100 registros; aceita filtros `product_id` e `source_id`. |
| GET | `/api/prices/{id}` | Não | Consulta registro bruto. |
| POST | `/api/prices` | Admin/manager | Registra preço bruto. |

Body:

```json
{"product_id":12,"source_id":3,"price":"4.2500","unit":"kg","location":"Campinas","collected_at":"2026-09-28 12:00:00"}
```

`location` e `collected_at` são opcionais; o horário omitido é preenchido em UTC. `price` deve ser maior que zero. O valor e a unidade enviados ficam preservados como dados brutos.

### Histórico de mercado

| Método | URL | Auth | Descrição |
|---|---|---|---|
| GET | `/api/products/{id}/history` | Não | Lista snapshots; aceita `location` e `limit` de 1 a 500. |
| POST | `/api/products/{id}/history` | Admin/manager | Calcula e persiste snapshots dos registros válidos mais recentes. |

Body opcional do recálculo: `from` e `to`, no formato `YYYY-MM-DD HH:MM:SS`. Snapshots incluem média, mínimo, máximo, unidade normalizada, local, contagem e horário do cálculo.

## Respostas e erros

Respostas usam `Content-Type: application/json; charset=UTF-8`. Erros comuns:

| Status | Significado |
|---|---|
| 400 | JSON inválido ou parâmetro malformado. |
| 401 | Bearer ausente, inválido ou expirado. |
| 403 | Role sem permissão. |
| 404 | Rota ou registro inexistente. |
| 405 | Método não permitido; confira o header `Allow`. |
| 409 | Registro duplicado ou recurso relacionado em uso. |
| 422 | Campos ou valores inválidos. |
| 500 | Falha interna; detalhes de conexão e credenciais não são retornados. |

Formato de erro:

```json
{"error":"Descrição do erro"}
```
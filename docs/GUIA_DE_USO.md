# Guia de uso

Este guia cobre a execução local, o primeiro acesso e os limites de cada perfil. A aplicação tem uma API REST e um painel administrativo; ela ainda não tem um portal visual para clientes.

## Quem pode acessar

| Perfil | Acesso |
|---|---|
| `client` | Pode se cadastrar pela API e consultar os endpoints públicos de catálogo, preços e histórico. Não entra no painel administrativo. |
| `manager` | Pode entrar no painel e manter categorias, produtos, fontes e preços. Não administra contas de usuário. |
| `admin` | Acesso operacional completo ao painel, incluindo a listagem e a criação de contas clientes. |

O cadastro público sempre cria `client`. A elevação para `manager` ou `admin` é deliberadamente manual no banco de dados. Assim, uma conta não consegue conceder privilégios a si mesma.

## Executar no Windows

Abra dois terminais na pasta do projeto. Confirme antes que MySQL está ativo e que `.env` contém as credenciais corretas.

No primeiro terminal, inicie a API:

```powershell
php -S 127.0.0.1:8000 -t public
```

No segundo terminal, inicie o painel:

```powershell
php -S 127.0.0.1:8002 -t public
```

Confira no `.env` se `APP_URL` aponta para a API, não para a porta do painel:

```dotenv
APP_URL=http://localhost:8000
```

Acesse:

- Painel: <http://127.0.0.1:8002/admin/>
- API: <http://127.0.0.1:8000>
- Verificação da API: <http://127.0.0.1:8000/api/health>

Se uma porta já estiver ocupada, escolha outra e atualize `APP_URL` para a porta da API. O endereço no navegador pode continuar sendo a porta do painel.

Na primeira execução, prepare o banco e aplique as migrações:

```powershell
php bin/create_database.php
php bin/migrate.php
```

Esses comandos são necessários apenas quando o banco ainda não foi criado ou quando há novas migrações para aplicar.

## Criar o primeiro administrador

O registro público cria somente contas `client`, portanto o primeiro administrador precisa ser promovido diretamente no MySQL.

1. Com a API em execução, registre uma conta usando um e-mail seu:

```powershell
$body = @{ name = 'Administrador'; email = 'admin@example.com'; password = 'troque-por-uma-senha-forte' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/auth/register' -Method Post -ContentType 'application/json' -Body $body
```

2. Abra uma conexão com o banco indicado em `.env`:

```powershell
mysql -u seu_usuario_mysql -p economy_api
```

3. Promova a conta registrada, trocando o e-mail pelo que você usou:

```sql
UPDATE users
SET role_id = (SELECT id FROM roles WHERE code = 'admin')
WHERE email = 'admin@example.com';
```

4. Entre em <http://127.0.0.1:8002/admin/> com esse e-mail e a senha escolhida.

Não compartilhe a senha nem coloque credenciais reais em documentação ou no repositório. Para promover outra pessoa, use o perfil `manager` se ela precisa operar o catálogo sem administrar usuários.

## Usar o painel

Depois de entrar, o fluxo recomendado é:

1. Em **Categorias**, crie as categorias do catálogo.
2. Em **Produtos**, crie os produtos e informe a categoria e a unidade de medida.
3. Em **Fontes**, cadastre os locais, sites ou sistemas de onde vêm os preços.
4. Em **Preços**, selecione produto e fonte, informe valor e unidade; local e data da coleta são opcionais.
5. Em **Painel**, acompanhe os totais e os registros recentes.
6. Em **Usuários**, um administrador pode consultar as contas e criar novas contas `client`.
7. Em **Configurações**, confira a URL da API, a conexão com o banco e a validade dos tokens. Os valores são apenas para consulta; edite `.env` e reinicie os servidores para alterá-los.

Para encerrar o acesso, use **Sair** no cabeçalho. O painel usa uma sessão no navegador; se ela expirar, entre novamente.

## Acesso de clientes pela API

Clientes não têm uma tela própria neste projeto. Eles podem se cadastrar e consumir os endpoints públicos diretamente, por exemplo no PowerShell:

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/products'
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/prices'
```

Para registrar uma conta de cliente:

```powershell
$body = @{ name = 'Ana Silva'; email = 'ana@example.com'; password = 'uma-senha-forte' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/auth/register' -Method Post -ContentType 'application/json' -Body $body
```

Para autenticar e consultar o próprio perfil:

```powershell
$body = @{ email = 'ana@example.com'; password = 'uma-senha-forte' } | ConvertTo-Json
$login = Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/auth/login' -Method Post -ContentType 'application/json' -Body $body
$headers = @{ Authorization = "Bearer $($login.access_token)" }
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/profile' -Headers $headers
```

O token expira conforme `JWT_EXPIRATION`. As consultas de catálogo, preços e histórico são públicas. Escritas exigem `admin` ou `manager`; contas `client` não podem criar ou alterar esses dados.

## Acesso pela API como operador

O login do painel aceita `admin` e `manager`. Para chamadas manuais que alteram dados, primeiro faça login em `/api/auth/login` e envie o token como `Authorization: Bearer <access_token>`. Por exemplo, para criar um preço:

```powershell
$token = 'cole-aqui-o-access-token'
$headers = @{ Authorization = "Bearer $token" }
$body = @{
    product_id = 1
    source_id = 1
    price = '4.2500'
    unit = 'kg'
    location = 'Campinas'
} | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/prices' -Method Post -Headers $headers -ContentType 'application/json' -Body $body
```

Use IDs que existam no banco. Consulte os endpoints e campos aceitos na [referência da API](API.md).

## Erros comuns

- `401`: credenciais inválidas, token expirado ou sessão do painel encerrada. Entre novamente ou faça login para obter outro token.
- `403`: o perfil não tem permissão. `client` não acessa o painel; apenas `admin` pode abrir a área de usuários.
- `404`: confira a URL e confirme que o servidor foi iniciado com `-t public`.
- `Não foi possível autenticar` ou erro de conexão no painel: confirme que a API está ativa na porta de `APP_URL` e que esse endereço aparece em **Configurações**.
- Erro de banco: valide as variáveis `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`, além das migrações.
- O servidor PHP informa que a porta está em uso: mantenha o processo existente ou escolha outra porta; não inicie um segundo servidor na mesma porta.

O servidor embutido do PHP é para desenvolvimento local. Para produção, siga as orientações de [preparação para produção](DEPLOYMENT.md).

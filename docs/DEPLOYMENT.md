# Preparação para produção

## Requisitos

- PHP 8.1 ou superior com `curl`, `mbstring` e `pdo_mysql`.
- MySQL 8.0 ou compatível com InnoDB, chaves estrangeiras e `utf8mb4`.
- Apache com `mod_rewrite` e `mod_headers`, ou um servidor equivalente configurado para usar `public/` como document root.
- HTTPS obrigatório para tráfego externo.

## Instalação

1. Publique somente o diretório `public/`; mantenha `.env`, `src/`, `vendor/` e `storage/` fora do document root.
2. Execute `composer install --no-dev --prefer-dist --classmap-authoritative` durante o build.
3. Configure as variáveis do ambiente fora do repositório: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` com HTTPS, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `JWT_SECRET` e `JWT_EXPIRATION`.
4. Gere `JWT_SECRET` com pelo menos 32 bytes de entropia. Não reutilize o valor ilustrativo de `.env.example`.
5. Execute `php bin/create_database.php` com uma credencial de provisionamento e, em seguida, `php bin/migrate.php` com a credencial da aplicação.
6. Garanta que o usuário da aplicação tenha somente permissões necessárias; a aplicação precisa ler e gravar tabelas, enquanto alterações de schema devem usar uma credencial de migração separada.
7. Dê ao processo PHP permissão de escrita apenas em `storage/logs/` e aplique rotação/expiração dos logs.

## Verificação e operação

- Confirme `GET /api/health` após cada release. Esse endpoint confirma o processo da API, não a conexão MySQL.
- Execute `composer test` no pipeline antes do deploy. Execute as migrações como etapa controlada, com backup verificado.
- Monitore respostas 5xx, falhas de autenticação do banco, expiração/rotação de JWT e espaço em `storage/`.
- Faça backup consistente do MySQL antes de migrações; teste restauração e plano de rollback em ambiente de staging.
- Configure TLS no load balancer/web server, limites de requisição, timeouts e política de backup no ambiente de hospedagem.
- O painel exige HTTPS em produção para que o cookie de sessão receba o atributo `Secure`.

## Segurança

- Nunca publique `.env`, tokens, senhas, dumps do banco ou logs.
- Mantenha `APP_DEBUG=false` em produção. Exceções não tratadas são registradas no log e retornam uma resposta JSON genérica.
- Restringa o acesso ao MySQL à rede privada do serviço e rotacione credenciais e segredo JWT em processo controlado.
- Crie o primeiro administrador por procedimento operacional protegido; os cadastros públicos recebem role `client`.
- Revise permissões e configure monitoramento/alertas antes de habilitar tráfego público.
# Delivery Hub

Aplicação Laravel com interface web em React/Inertia e uma API JSON para gerir restaurantes, categorias, produtos, utilizadores e pedidos. Este README explica como executar o sistema e como a equipa de front-end pode integrar-se com a API.

## Requisitos

- PHP 8.3 ou superior e Composer
- Node.js e npm
- SQLite (configurado por omissão) ou outra base de dados suportada pelo Laravel
- Docker Compose v2 (opcional, para executar todos os serviços em contentores)

## Instalar e executar

Na raiz do projeto:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
npm install
```

Antes de semear a base de dados, configure no `.env` um utilizador administrador. A palavra-passe tem de ter pelo menos 16 caracteres:

```dotenv
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=uma-palavra-passe-segura-com-16-caracteres
```

Crie as tabelas e o utilizador administrador:

```bash
php artisan migrate --seed
```

Inicie o servidor Laravel e o servidor de desenvolvimento do front-end em dois terminais:

```bash
php artisan serve
```

```bash
npm run dev
```

Abra <http://localhost:8000>. A interface web inclui a página inicial, os ecrãs de registo e autenticação (`/register` e `/login`), o painel (`/dashboard`) e as definições da conta. O painel exige autenticação e confirmação do endereço de e-mail. Com o `MAIL_MAILER=log` do ambiente local, consulte `storage/logs/laravel.log` para obter a mensagem de verificação.

Não existem credenciais de demonstração embutidas no repositório. O administrador criado pelo seeder usa os valores configurados em `SUPER_ADMIN_EMAIL` e `SUPER_ADMIN_PASSWORD`. Um cliente também pode criar a sua conta em `/register` ou através da API.

## Estrutura do projeto

- `resources/js/pages` contém as páginas React servidas pela interface Inertia.
- `routes/web.php` define as páginas web; `routes/api.php` carrega os grupos de rotas da API.
- `app/Http/Controllers`, `Requests` e `Resources` recebem pedidos, validam entradas e definem respostas JSON.
- `app/Domains` contém as operações de negócio, os dados transportados entre camadas, os serviços e o acesso à persistência.
- `app/Models` representa os dados guardados na base de dados.

As páginas React existentes são a interface base de autenticação e conta. As operações de marketplace estão expostas pela API e podem ser consumidas pelas páginas do front-end.

## API para o front-end

O endereço base local é `http://localhost:8000/api`. Os pedidos devem enviar e receber JSON (`Accept: application/json`; para operações com corpo, `Content-Type: application/json`). As rotas protegidas usam tokens pessoais do Sanctum no cabeçalho:

```http
Authorization: Bearer TOKEN_RECEBIDO_NO_LOGIN
```

### Autenticação

| Método | Caminho                 | Acesso  | Resultado                                           |
| ------ | ----------------------- | ------- | --------------------------------------------------- |
| `POST` | `/auth/register`        | Público | Regista um cliente e devolve utilizador e token     |
| `POST` | `/auth/login`           | Público | Autentica e devolve utilizador e token              |
| `POST` | `/auth/forgot-password` | Público | Envia instruções de reposição quando a conta existe |
| `POST` | `/auth/reset-password`  | Público | Define uma nova palavra-passe                       |
| `POST` | `/auth/logout`          | Token   | Revoga o token atual                                |
| `GET`  | `/auth/me`              | Token   | Devolve os dados do utilizador autenticado          |

Exemplo de autenticação:

```http
POST /api/auth/login
Content-Type: application/json
Accept: application/json
```

```json
{
    "email": "cliente@example.com",
    "password": "palavra-passe"
}
```

O sucesso devolve `user` e `token` na raiz da resposta. O objeto `user` contém `id`, `name`, `email`, `role` e `restaurant_id`. Envie o token como `Bearer` nos pedidos autenticados.
Os perfis possíveis são `super_admin`, `restaurant_admin`, `staff` e `customer`; o perfil e o `restaurant_id` determinam que operações a conta pode executar.

### Restaurantes

| Método         | Caminho                     | Acesso                                                 |
| -------------- | --------------------------- | ------------------------------------------------------ |
| `GET`          | `/restaurants`              | Público; permite filtrar por `id`, `name` e `per_page` |
| `GET`          | `/restaurants/{restaurant}` | Público                                                |
| `POST`         | `/restaurants`              | `super_admin`                                          |
| `PUT`, `PATCH` | `/restaurants/{restaurant}` | Autenticado                                            |
| `DELETE`       | `/restaurants/{restaurant}` | Autenticado                                            |

### Categorias e produtos

| Método                          | Caminho                                        | Acesso                                                          |
| ------------------------------- | ---------------------------------------------- | --------------------------------------------------------------- |
| `GET`, `POST`                   | `/categories`                                  | `restaurant_admin` ou `staff`, associado a um restaurante ativo |
| `GET`, `PUT`, `PATCH`, `DELETE` | `/categories/{category}`                       | `restaurant_admin` ou `staff`, associado a um restaurante ativo |
| `GET`                           | `/restaurants/{restaurant}/products`           | Público; permite filtrar por `name` e `per_page`                |
| `GET`                           | `/restaurants/{restaurant}/products/{product}` | Público                                                         |
| `POST`                          | `/products`                                    | `restaurant_admin` ou `staff`, associado a um restaurante ativo |
| `PUT`, `PATCH`, `DELETE`        | `/products/{product}`                          | `restaurant_admin` ou `staff`, associado a um restaurante ativo |

`GET /categories` aceita os filtros `id`, `name` e `per_page`; a listagem pública de restaurantes aceita `id`, `name` e `per_page`; a listagem pública de produtos aceita `name` e `per_page`. Os parâmetros de paginação aceitam valores entre 1 e 100 (por omissão, 10). As categorias e os produtos geridos por utilizadores de restaurante ficam associados ao restaurante da conta autenticada.

### Pedidos e equipa do restaurante

| Método  | Caminho                             | Acesso                                                |
| ------- | ----------------------------------- | ----------------------------------------------------- |
| `GET`   | `/orders`                           | `customer`; lista os pedidos do cliente               |
| `POST`  | `/orders`                           | `customer`; cria um pedido                            |
| `GET`   | `/orders/{order}`                   | `customer`; consulta um pedido próprio                |
| `POST`  | `/orders/{order}/cancel`            | `customer`; cancela um pedido próprio                 |
| `GET`   | `/restaurant/orders`                | `restaurant_admin` ou `staff` de um restaurante ativo |
| `GET`   | `/restaurant/orders/{order}`        | `restaurant_admin` ou `staff` de um restaurante ativo |
| `PATCH` | `/restaurant/orders/{order}/status` | `restaurant_admin` ou `staff` de um restaurante ativo |
| `POST`  | `/restaurant/users`                 | `restaurant_admin`; cria um membro da equipa          |

Para criar um pedido, envie `restaurant_id`, `address` e uma lista `items` com `product_id` e `quantity`. O estado do pedido pode ser `pending`, `in_progress`, `completed` ou `cancelled`. Os recursos de pedido incluem o total e as linhas com quantidade, preço unitário e subtotal.

Exemplo do corpo de criação de pedido:

```json
{
    "restaurant_id": 1,
    "address": "Rua de Exemplo, 10",
    "items": [
        {
            "product_id": 25,
            "quantity": 2
        }
    ]
}
```

Para atualizar o estado, envie `{"status":"in_progress"}` para `PATCH /api/restaurant/orders/{order}/status`.

### Formato das respostas

Os endpoints de recursos devolvem os dados em `data`; as operações de escrita podem incluir também `message`. As respostas de autenticação devolvem `user` e `token` sem o invólucro `data`. Erros de validação são enviados no formato padrão do Laravel e podem ser usados pelo front-end para associar mensagens aos campos. Os códigos `401`, `403` e `422` indicam, respetivamente, falta de autenticação, falta de permissão e dados inválidos.

Os campos e as regras exatas de cada pedido estão nas classes `app/Http/Requests/Api`. A estrutura JSON exposta está nas classes `app/Http/Resources`; consulte-as antes de alterar os contratos consumidos pelo front-end.

## Verificações

```bash
# Executa a suite automatizada, incluindo o percurso completo de entrega.
php artisan test
```

O projeto também disponibiliza `composer run lint:check`, `composer run types:check`, `npm run check` e `npm run types:check`.

## Serviços em tempo real

Para testar notificações de pedidos, mantenha Reverb e um worker ativos em terminais separados:

```bash
# Inicia o servidor WebSocket local na porta configurada em REVERB_SERVER_PORT.
php artisan reverb:start --host=0.0.0.0 --port=8080
```

```bash
# Processa tarefas normais e relatórios sem bloquear pedidos HTTP.
php artisan queue:work --queue=default,reports --tries=3
```

As alterações de estado são emitidas como `order.status.updated` para os canais privados `restaurant.{id}` e `orders.{customerId}`. A autorização do canal usa a sessão web autenticada ou um token Sanctum autorizado.

## API versionada e observabilidade

Use `/api/v1` para novos clientes; `/api` mantém compatibilidade temporária. As respostas HTTP incluem `X-Request-Id`, também guardado como `request_id` no contexto dos logs. Os logs JSON são enviados para `stderr` e `storage/logs`.

A consola Laravel Pulse fica em `/pulse` e só está acessível a contas `super_admin`. Para agendar manualmente um resumo diário de pedidos na fila `reports`, execute:

```bash
# Enfileira o resumo do dia; omita a data para usar a data local atual.
php artisan marketplace:orders:summary 2026-10-22
```

## Docker Compose

Crie o `.env` local, configure APP_KEY e credenciais e inicie todos os serviços:

```bash
# Prepara configuração local sem incluir segredos em imagens ou no Git.
cp .env.example .env
```

```bash
# Gera a chave de aplicação necessária para cifrar sessões e dados sensíveis.
php artisan key:generate
```

```bash
# Compila e inicia Nginx, Laravel, MySQL, Redis, phpMyAdmin, Reverb, worker e scheduler.
docker compose up --build -d
```

```bash
# Aplica o esquema e cria a conta administrativa na base de dados MySQL.
docker compose exec app php artisan migrate --seed
```

```bash
# Acompanha os logs da aplicação, dos workers e do servidor WebSocket.
docker compose logs -f app worker reverb
```

Abra <http://localhost:8000>; phpMyAdmin fica em <http://localhost:8081>, MySQL está disponível apenas em `127.0.0.1:3307`, Redis em `127.0.0.1:6379` e Reverb na porta 8080. Inicie sessão no phpMyAdmin com `DB_USERNAME` e `DB_PASSWORD` do `.env`. MySQL e Redis usam volumes persistentes. Em produção, substitua todas as palavras-passe de exemplo por segredos geridos pela plataforma.

As portas publicadas de MySQL, Redis e phpMyAdmin estão limitadas ao loopback do computador para não as expor à rede. Configure `MYSQL_FORWARD_PORT`, `REDIS_FORWARD_PORT` ou `PHPMYADMIN_PORT` no `.env` se essas portas já estiverem ocupadas.

## Deploy: Forge e Vapor

**Laravel Forge:** configure PHP 8.3, Node 22, Composer, MySQL e Redis no servidor; associe domínio/SSL e use `scripts/forge-deploy.sh` como deployment script. Configure workers persistentes para as filas `default,reports`, mantenha `schedule:work` ativo e execute Reverb como serviço de longa duração atrás de um proxy WebSocket. Docker Compose também pode executar a stack num VPS; não inicie serviços privilegiados na imagem web.

**Laravel Vapor:** execute `vapor init` para substituir o ID de exemplo em `vapor.yml` e crie os recursos AWS com os nomes `delivery-hub-db`, `delivery-hub-cache`, `delivery-hub-storage`, `staging-queue` e `production-queue`. Configure `BROADCAST_CONNECTION=reverb` e as credenciais/URL do Reverb em Vapor; forneça `VITE_REVERB_*` também ao runner de build, pois os assets são compilados antes do deploy. As migrações correm antes da ativação. Vapor fornece filas e agendamento serverless; Reverb precisa de um host persistente acessível externamente (por exemplo, Forge ou ECS), porque uma Lambda HTTP não deve iniciar o servidor WebSocket como processo permanente. O `Dockerfile` e `compose.yaml` destinam-se à execução persistente e CI local/Forge; Vapor usa o runtime nativo descrito em `vapor.yml`.

## CI/CD no GitHub Actions

O workflow `.github/workflows/deploy.yml` valida pull requests, pushes para `main` e tags `v*`: executa testes Laravel e verificações PHP, valida tipos/formatação e compila o front-end, valida o Compose e constrói/testa as imagens Docker. Os jobs de PHP, front-end e containers correm em paralelo.

O deploy é iniciado manualmente em **Actions → Delivery Hub Deploy → Run workflow**, escolhendo `forge` ou `vapor`; só é permitido a partir de `main` e só começa depois de todos os checks passarem. Configure ambientes protegidos do GitHub Actions chamados `production-forge` e `production-vapor`:

- `production-forge`: secret `FORGE_DEPLOYMENT_WEBHOOK`, copiado do webhook de deployment do site Forge.
- `production-vapor`: secret `VAPOR_API_TOKEN`; configure também o projeto/ID Vapor, recursos, variáveis e credenciais necessárias no `vapor.yml` e no painel Vapor.

O CI não precisa de credenciais de produção. O deploy Vapor publica o ambiente `production`; o webhook Forge executa o deployment script configurado no servidor.

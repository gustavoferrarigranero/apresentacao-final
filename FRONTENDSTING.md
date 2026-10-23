# Frontend DeliveryHub — guia de desenvolvimento, integração e testes

Este documento descreve a implementação existente, como executar as telas, onde alterar cada funcionalidade e como validar uma entrega. Os comandos devem ser executados na raiz do projeto. Os endpoints e permissões apresentados correspondem ao código entregue pelo backend; funcionalidades futuras estão identificadas como pendentes.

## Índice

1. [Âmbito e tecnologias](#âmbito)
2. [Preparar e abrir a aplicação](#abrir-e-testar)
3. [Estrutura e responsabilidades](#estrutura-de-pastas-alinhada-com-o-backend)
4. [Contratos e regras](#contratos-utilizados)
5. [Limitações conhecidas](#limites-do-backend-identificados)
6. [Verificações já executadas](#verificação-executada)
7. [Limpeza e carregamento](#limpeza-do-frontend)
8. [Fluxo de execução e estado](#fluxo-de-execução-e-gestão-de-estado)
9. [Design, acessibilidade e desempenho](#interface-estilos-e-acessibilidade)
10. [Testes automatizados](#guia-de-testes-automatizados)
11. [Testes manuais por tela e papel](#guia-de-testes-manuais)
12. [Falhas, recuperação e tempo real](#testar-falhas-recuperação-e-tempo-real)
13. [Diagnóstico de problemas](#diagnóstico-de-problemas)
14. [Adicionar funcionalidades e entregar alterações](#desenvolvimento-e-entrega)

## Âmbito

### Tecnologias e ficheiros de configuração

| Elemento                           | Utilização no projeto                                         |
| ---------------------------------- | ------------------------------------------------------------- |
| React 19 e TypeScript              | Telas, componentes, estado e tipos dos contratos              |
| Inertia 3                          | Arranque, entradas de páginas e autenticação/definições web   |
| Laravel e Sanctum                  | API, autenticação por token e autorização no servidor         |
| Vite 8 através do Vite+ (`vp`)     | Servidor de desenvolvimento, build, formatação, lint e testes |
| Tailwind CSS 4                     | Estilos utilitários e tokens globais                          |
| Radix UI                           | Primitivos acessíveis de diálogos e componentes existentes    |
| Lucide React e Sonner              | Ícones e notificações globais                                 |
| Laravel Echo e Pusher JS           | Cliente websocket compatível com Reverb                       |
| Wayfinder                          | Geração de actions/rotas TypeScript para os fluxos web        |
| Vitest através de `vite-plus/test` | Testes de regras e serviços HTTP do frontend                  |

As versões exatas são fixadas em `package-lock.json`; `package.json` define os intervalos aceites. Usar `npm ci` para reproduzir a instalação. O backend usa `composer.lock`.

| Ficheiro                             | Responsabilidade                                                              |
| ------------------------------------ | ----------------------------------------------------------------------------- |
| `package.json` / `package-lock.json` | Dependências JavaScript e comandos disponíveis                                |
| `vite.config.ts`                     | Entradas, plugins React/Inertia/Tailwind/Wayfinder, fontes, lint e formatação |
| `tsconfig.json`                      | TypeScript estrito, JSX e alias `@/*` para `resources/js/*`                   |
| `vitest.frontend.config.ts`          | Testes em ambiente Node, alias e padrão de descoberta                         |
| `resources/css/app.css`              | Tailwind, tokens de cores, tipografia, raios e estilos base                   |
| `resources/views/app.blade.php`      | Documento HTML que monta o Inertia e inclui os assets                         |
| `.github/workflows/deploy.yml`       | Verificações automáticas e build de produção                                  |

A interface React foi ligada aos contratos existentes em `routes/api.php`, `routes/api/*`, controllers, Form Requests, API Resources, ações de domínio e canais de broadcasting. Não foram alterados controllers, rotas, modelos, migrations, configuração PHP ou ficheiros Composer.

A aplicação Inertia continua a arrancar em `resources/js/app.tsx`. As páginas `welcome.tsx` e `dashboard.tsx` apresentam agora o portal e a área de gestão. As páginas originais de autenticação web e definições continuam disponíveis nas rotas existentes.

Não existem dados comerciais de exemplo no novo portal: listas, detalhes, pedidos e indicadores vêm da API. Carrinho e avisos são estado local do React.

## Abrir e testar

Com o servidor Laravel e Vite habituais do projeto em execução, abrir a raiz da aplicação:

| Ecrã                   | Caminho                   |
| ---------------------- | ------------------------- |
| Portal público         | `/#portal`                |
| Menu de um restaurante | `/#restaurant/ID`         |
| Carrinho               | `/#cart`                  |
| Checkout               | `/#checkout`              |
| Autenticação API       | `/#login`                 |
| Registo de cliente     | `/#register`              |
| Recuperação de acesso  | `/#forgot`                |
| Dashboard              | `/#dashboard`             |
| Pedidos / detalhe      | `/#orders` / `/#order/ID` |
| Restaurantes           | `/#restaurants`           |
| Categorias             | `/#categories`            |
| Produtos               | `/#products`              |
| Criar membro da equipa | `/#team`                  |

Os fragmentos `#...` permitem testar e navegar sem adicionar rotas PHP. A rota `/dashboard` existente também apresenta a área de gestão, mas mantém o middleware de autenticação web original. Para uma sessão exclusivamente API, usar `/#dashboard`.

Comandos:

```sh
composer install
npm ci
npm run dev
# Noutro terminal, se não estiver a usar o Herd:
php artisan serve
```

Os testes automatizados de frontend usam respostas simuladas e não precisam de base de dados. Para testar as telas contra a API real, a base deve estar configurada e ter **todas as migrations aplicadas**. Uma base sem restaurantes ativos produz uma lista vazia; uma base com schema incompleto pode produzir erros HTTP.

### Preparação inicial

Requisitos: PHP compatível com `composer.json` (pelo menos 8.3), Composer e Node compatível com as dependências. O pipeline utiliza Node 22. Herd pode servir a aplicação Laravel sem `php artisan serve`.

Num checkout novo, copiar `.env.example` para `.env` **apenas se `.env` ainda não existir** e configurar a ligação à base de dados destinada a desenvolvimento. Não substituir o `.env` já configurado.

```sh
composer install
php artisan key:generate
php artisan migrate:status
php artisan migrate
npm ci
```

`key:generate` é uma preparação inicial para um ambiente novo, não um passo de rotina num ambiente que já tem chave e sessões em uso. `migrate` aplica as migrations pendentes. Não usar `migrate:fresh` para preparar testes numa base com dados a preservar.

O build executa Wayfinder, pelo que também depende de Laravel arrancar corretamente, mesmo quando a alteração é apenas visual. Se necessário, gerar os ficheiros explicitamente:

```sh
php artisan wayfinder:generate --with-form
```

### Conta de super administrador

O ficheiro `database/seeders/SuperAdminSeeder.php` lê `SUPER_ADMIN_EMAIL` e `SUPER_ADMIN_PASSWORD` do ambiente. Não existe uma palavra-passe fixa no código.

Exemplo de configuração — substituir por valores próprios de desenvolvimento:

```dotenv
SUPER_ADMIN_EMAIL=admin@example.test
SUPER_ADMIN_PASSWORD="SubstituirPorUmaSenhaForteCom16CaracteresOuMais"
```

A palavra-passe do super administrador deve ter pelo menos **16 caracteres**. Após guardar:

```sh
php artisan config:clear
php artisan db:seed --class=SuperAdminSeeder
```

O seeder cria ou atualiza a conta do email indicado, incluindo palavra-passe e papel. Não deve ser executado inadvertidamente para redefinir credenciais existentes. Não publicar `.env`, tokens, palavras-passe reais ou capturas com esses valores.

Para registo de clientes e criação de membros/administrador inicial pela API, o requisito de palavra-passe é de 12 caracteres, conforme os Form Requests existentes; é diferente do requisito do seeder.

### Variáveis utilizadas pelo navegador

| Variável              | Utilização                                                      |
| --------------------- | --------------------------------------------------------------- |
| `VITE_APP_NAME`       | Nome usado nos títulos Inertia; fallback para `Delivery Hub`    |
| `VITE_REVERB_APP_KEY` | Chave pública do cliente Reverb; ativa a tentativa de websocket |
| `VITE_REVERB_HOST`    | Host acessível pelo navegador, sem esquema ou caminho           |
| `VITE_REVERB_PORT`    | Porta websocket acessível pelo navegador                        |
| `VITE_REVERB_SCHEME`  | `http` para WS local ou `https` para WSS                        |

Variáveis `VITE_*` são públicas e entram nos assets. Não colocar nelas `REVERB_APP_SECRET`, credenciais da base ou tokens privados. Reiniciar `npm run dev` após alterações às variáveis; em produção, reconstruir os assets.

Para desenvolvimento local com um servidor Reverb configurado em `localhost:8080`, o cliente pode usar:

```dotenv
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

Estes valores têm de corresponder ao serviço real. Não ativam nem configuram o servidor por si próprios. O frontend usa API da mesma origem (`/api/v1`), não uma variável com domínio externo.

### Serviços para tempo real

Quando a configuração de broadcasting e filas do ambiente exigir esses processos, executar em terminais separados:

```sh
php artisan reverb:start
php artisan queue:work
```

Em produção, esses processos pertencem à infraestrutura gerida pela equipa backend. O frontend não modifica a configuração de filas ou broadcasting.

Para testar fluxos autenticados, utilizar contas de desenvolvimento disponibilizadas pela equipa. O registo no portal e as ações de gestão criam e alteram dados reais na base ligada.

## Estrutura de pastas alinhada com o backend

A organização usa os mesmos cinco domínios existentes em `app/Domains`: `Users`, `Restaurants`, `Categories`, `Products` e `Orders`. No frontend os nomes usam minúsculas e ficheiros TS/TSX. Não se copiam Repositories/Actions PHP para o navegador: persistência, autorização e transações continuam no servidor.

```text
resources/js/
├── app.tsx                         # Arranque do Inertia e providers globais
├── application/
│   ├── DeliveryHub.tsx             # Composição da aplicação, carrinho e menu
│   └── use-view.ts                  # Navegação local por fragmentos da URL
├── domains/
│   ├── users/
│   │   ├── types.ts                # UserResource, papéis e resposta de login
│   │   ├── permissions.ts          # Papel e contexto de equipa
│   │   ├── services.ts             # Login, registo, sessão e criação de membro
│   │   ├── context/session.tsx     # ApiSessionProvider e useSession
│   │   └── pages/                  # auth.tsx e team.tsx
│   ├── restaurants/
│   │   ├── types.ts                # RestaurantResource
│   │   ├── permissions.ts          # Permissão para editar restaurante
│   │   ├── services.ts             # Consultas, criação, edição e desativação
│   │   └── pages/restaurants.tsx   # Gestão de restaurantes
│   ├── categories/
│   │   ├── types.ts                # CategoryResource
│   │   ├── services.ts             # Gestão e opções paginadas de categorias
│   │   └── pages/categories.tsx    # Gestão de categorias
│   ├── products/
│   │   ├── types.ts                # ProductResource, preço decimal e stock
│   │   ├── services.ts             # Catálogo público e mutações de produto
│   │   └── pages/products.tsx      # Gestão de catálogo e stock
│   └── orders/
│       ├── types.ts                # OrderResource, itens e payload de checkout
│       ├── rules.ts                # Quatro estados, transições, payload e canais
│       ├── cart.ts                 # Estado e regras do carrinho
│       ├── services.ts             # Endpoints por ator e operações de pedido
│       ├── realtime/connection.ts  # Echo/Reverb e autorização do canal
│       ├── components/            # Estado da ligação e badge de pedido
│       ├── pages/                 # cart.tsx, checkout.tsx e orders.tsx
│       └── __tests__/             # Contratos de pedidos, permissões e valores
├── features/
│   ├── portal/pages/portal.tsx     # Vitrine que combina restaurantes e produtos
│   └── dashboard/pages/dashboard.tsx # Resumo de dados dos domínios
├── shared/
│   ├── api/
│   │   ├── client.ts              # /api/v1, token, JSON, erros e request ID
│   │   ├── types.ts               # Resource<T>, Page<T> e estado ativo/inativo
│   │   ├── payload.ts             # Seleção de campos aceites pelo backend
│   │   ├── pagination.ts          # Query string sem perder filtros existentes
│   │   └── __tests__/             # Testes do cliente HTTP
│   ├── hooks/use-api.ts           # Consultas abortáveis, paginação e mutations
│   ├── components/
│   │   ├── api-ui.tsx             # Campos, formulários, erros e confirmações
│   │   └── catalog-state.tsx      # Campo/badge ativo ou inativo
│   └── utils/                     # money.ts e date.ts
├── __tests__/integration/         # Serviços e contratos entre domínios
├── pages/
│   ├── welcome.tsx                # Entrada Inertia para DeliveryHub
│   ├── dashboard.tsx              # Entrada Inertia para a área de gestão
│   ├── auth/                      # Autenticação web original (Fortify)
│   └── settings/                  # Definições web originais
├── layouts/                       # Layouts das restantes páginas Inertia
└── components/ui/                 # Primitivos visuais e acessíveis existentes
```

### Responsabilidade de cada camada

- `pages/`: entradas resolvidas pelo Inertia. As páginas internas de domínio são compostas pela aplicação e não criam rotas PHP automaticamente.
- `application/`: junta os providers, navegação, estado do carrinho e menu por papel. Não define os contratos HTTP dos domínios.
- `domains/*/types.ts`: representa os campos realmente expostos pelos API Resources. Alterações futuras a um Resource devem começar neste ficheiro e no serviço correspondente.
- `domains/*/services.ts`: concentra os endpoints e os métodos HTTP. Nas operações de formulário, seleciona apenas os campos aceites pelos Form Requests. Um campo visual adicional não é enviado por acidente como permissão, restaurante ou credencial.
- `rules.ts` e `permissions.ts`: regras para apresentar ações válidas e orientar o utilizador. As verificações do backend continuam a decidir a autorização final.
- `features/`: telas que combinam vários domínios, como portal e dashboard. Consultam os serviços e tipos dos domínios sem duplicar contratos.
- `shared/`: infraestrutura reutilizável, sem dependências de regras comerciais específicas. Um diálogo de confirmação recebe uma função de ação; não precisa de conhecer rotas de produtos, restaurantes ou pedidos.
- `__tests__/`: testes junto do código quando tratam um módulo e testes de integração separados quando verificam a composição entre domínios.

### Correspondência com a lógica do servidor

| Backend                                                     | Frontend                                    | Responsabilidade                          |
| ----------------------------------------------------------- | ------------------------------------------- | ----------------------------------------- |
| `app/Domains/Users` e rotas `api/auth.php`, `api/users.php` | `domains/users`                             | Sessão, papéis e criação de membro        |
| `app/Domains/Restaurants`                                   | `domains/restaurants`                       | Restaurante e administrador inicial       |
| `app/Domains/Categories`                                    | `domains/categories`                        | Categorias do restaurante autenticado     |
| `app/Domains/Products`                                      | `domains/products`                          | Preço, stock, categoria e disponibilidade |
| `app/Domains/Orders`                                        | `domains/orders`                            | Checkout, pedidos, transições e canais    |
| `app/Http/Resources`                                        | `domains/*/types.ts`, `shared/api/types.ts` | Dados e paginação recebidos               |
| `app/Http/Requests/Api`                                     | `domains/*/services.ts`, formulários        | Campos enviados e erros por campo         |

A pasta anterior `marketplace` e o ficheiro conjunto `Management.tsx` foram substituídos por estes módulos. Os imports apontam diretamente para os módulos novos, sem ficheiros de compatibilidade ou cópias da implementação antiga. O módulo de Echo passou de `lib/echo.ts` para `domains/orders/realtime/connection.ts`.

As pastas geradas `actions`, `routes` e `wayfinder` continuam a pertencer ao Wayfinder; não devem ser editadas manualmente. Os componentes e layouts do starter que suportam autenticação e definições web foram preservados.

### Ajustes de lógica nesta reorganização

- O serviço de pedidos escolhe `/orders` para cliente e `/restaurant/orders` para equipa com restaurante associado. Super administrador e contas sem contexto não recebem um endpoint de pedidos indevido.
- Antes de enviar uma transição, o serviço verifica estado, papel e, para a equipa, pertença do pedido ao restaurante. O servidor volta a validar a operação.
- A listagem administrativa de um administrador de restaurante inclui o filtro `id` do restaurante associado. Super administrador usa a listagem geral. A limitação de autenticação opcional no índice público, descrita abaixo, permanece dependente do backend.
- A construção da paginação preserva filtros que já existam na URL, evitando perder o `id` ao pesquisar ou mudar de página.
- A criação de restaurante envia as credenciais do administrador inicial; a edição envia apenas os campos editáveis do restaurante.
- Produtos enviam somente nome, tipo, preço, quantidade, categoria e estado. O restaurante do produto é determinado pelo backend a partir do utilizador.
- O carregamento de opções de categorias percorre as páginas do paginator dentro do serviço de categorias e pode ser cancelado ao sair da tela.

## Contratos utilizados

### Autenticação e autorização

- Login e registo usam `POST /api/v1/auth/login` e `/auth/register`, com a resposta real `{ user, token }`.
- O token Sanctum é guardado em `sessionStorage` da aba, com alternativa em memória se o armazenamento estiver bloqueado. Não é escrito em `localStorage` nem apresentado na interface.
- O restauro consulta `GET /api/v1/auth/me`; um 401 invalida a sessão local. Logout chama `/auth/logout` antes de limpar o estado.
- Registo pede confirmação da palavra-passe e mínimo de 12 caracteres. Recuperação chama `/auth/forgot-password`; a reposição pelo link mantém o fluxo web já existente.
- A autenticação API é independente da sessão Fortify. Entrar em `/login` não cria automaticamente um token para o portal, e entrar em `/#login` não autentica automaticamente as rotas web protegidas.
- Papéis reais: `customer`, `staff`, `restaurant_admin`, `super_admin`. A interface esconde menus e ações incompatíveis, mas o backend continua responsável por autorizar cada operação.

| Papel                        | Interface disponibilizada                                                        |
| ---------------------------- | -------------------------------------------------------------------------------- |
| Cliente                      | Portal, carrinho, checkout, pedidos próprios, cancelamento enquanto pendente     |
| Staff                        | Categorias, produtos e pedidos do restaurante associado                          |
| Administrador do restaurante | Operações de staff, edição do próprio restaurante e criação de membros da equipa |
| Super administrador          | Criação/edição/desativação de restaurantes e criação do administrador inicial    |

Não se inventaram permissões de entregador/operador nem uma listagem global de pedidos para o super administrador: esses contratos não estão expostos nesta API.

### Catálogo e gestão

- Restaurantes: GET/POST `/restaurants`, PATCH/DELETE `/restaurants/{id}`. A criação envia também `admin_name`, `admin_email` e `admin_password`, exigidos pelo backend.
- Catálogo público: GET `/restaurants/{id}/products`. Campos reais incluem `category_id`, `type`, `price`, `quantity` e `status`.
- Produtos: POST `/products`, PATCH/DELETE `/products/{id}`. Categorias: GET/POST `/categories`, PATCH/DELETE `/categories/{id}`.
- Equipa: POST `/restaurant/users`, com confirmação da palavra-passe. O backend não disponibiliza uma lista/edit/delete de membros; a interface não simula essas operações.
- Desativação tem confirmação e só atualiza a interface depois de resposta bem-sucedida.
- Formulários apresentam erros 422 por campo, erro geral, estado de envio e proteção contra duplo clique.
- Pesquisa usa `name`; a paginação usa `page`/`per_page`, `data`, `links` e `meta` do paginator Laravel, incluindo o total devolvido pelo servidor. Não usa cursor pagination porque este backend não a fornece.

### Carrinho e checkout

- Apenas um restaurante por pedido; adicionar um produto de outro restaurante não apaga o carrinho existente.
- Quantidades limitadas pelo stock conhecido e pelo limite de 100 unidades por produto; máximo de 50 produtos distintos. Produtos inativos ou sem stock não podem ser adicionados.
- O carrinho dura enquanto a aplicação está montada. Um recarregamento limpa-o. Login na mesma aplicação preserva-o; logout ou mudança de utilizador autenticado limpa-o.
- A API envia preços como valores decimais (`"18.50"`), não como centavos. O frontend converte-os para inteiros em centavos para somar e depois formata em euros. Nunca multiplica diretamente valores monetários com ponto flutuante.

Payload efetivamente enviado a `POST /api/v1/orders`:

```json
{
    "restaurant_id": 1,
    "address": "Morada de entrega",
    "delivery_date": "2026-10-23",
    "items": [{ "product_id": 10, "quantity": 2 }]
}
```

`delivery_date` é omitida se estiver em branco. Não se enviam preços, totais, `address_id` ou `payment_method`: o servidor calcula o preço e valida stock, disponibilidade e restaurante.

O formulário mantém a página e os dados em erros, apresenta validação por campo/item e mensagens de stock. O envio tem bloqueio síncrono e estado de loading. Em sucesso, usa o número e total retornados pelo servidor, limpa o carrinho e oferece acompanhamento.

Em falha de rede ou erro 5xx, o resultado pode ser incerto. A interface orienta a consultar os pedidos antes de desbloquear outra tentativa. `X-Request-Id` é um UUID novo por pedido HTTP e serve para correlação; **não é uma chave de idempotência**. A API não implementa deduplicação de checkout, por isso não há reenvio automático.

O checkout usa JSON via cliente HTTP, porque os controllers entregues devolvem API Resources/erros JSON e não redirecionamentos Inertia. Não usa `useForm`/`router.post` contra esses endpoints, evitando respostas incompatíveis com o protocolo Inertia.

### Pedidos e dashboard

Estados reais, provenientes de `OrderStatus`:

| Estado        | Rótulo        | Ações na interface                                   |
| ------------- | ------------- | ---------------------------------------------------- |
| `pending`     | Pendente      | Cliente cancela; equipa inicia preparação ou cancela |
| `in_progress` | Em preparação | Equipa conclui ou cancela                            |
| `completed`   | Concluído     | Sem transições                                       |
| `cancelled`   | Cancelado     | Sem transições                                       |

Cliente consulta `/orders` e `/orders/{id}` e cancela em `/orders/{id}/cancel`. A equipa consulta `/restaurant/orders` e atualiza `/restaurant/orders/{id}/status`. O detalhe mostra morada, data, itens, quantidades e valores reais. O campo `status` do Resource representa o fluxo de pedido apesar do nome interno `payment_status` no modelo.

O dashboard usa as mesmas listagens: total global da listagem apenas quando fornecido por `meta.total`; contagens por estado, valor e gráfico referem-se explicitamente à página carregada. O valor de pedidos não é apresentado como receita paga, pois não existe confirmação de pagamentos na API. Super administrador vê restaurantes, sem inventar estatísticas globais de encomendas.

### Tempo real

- Cliente subscreve `orders.{user.id}` — o sufixo é o **ID do cliente**, não o ID do pedido.
- Equipa subscreve `restaurant.{restaurant_id}`.
- Evento real: `.order.status.updated`, com `order_id`, `restaurant_id` e `status`.
- A ligação só é criada com utilizador, token e `VITE_REVERB_APP_KEY`. A autorização usa `/sanctum/csrf-cookie` e `/broadcasting/auth`, com token Bearer e cabeçalho XSRF, conforme o middleware existente.
- Echo/Reverb reconecta, apresenta estado e permite nova tentativa em falhas de autorização/ligação. As subscrições e listeners são limpos ao sair.
- Listas de pedidos e dashboard voltam a consultar o servidor após eventos/reconexão. Existe também atualização de 30 em 30 segundos com a página visível e online, cobrindo alterações que não emitam evento.
- A aplicação usa as variáveis públicas `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` já previstas no projeto; o serviço Reverb e a infraestrutura de filas devem estar operacionais para entrega de eventos. Nenhuma configuração backend foi alterada.

## Limites do backend identificados

1. A listagem de produtos exposta é pública e filtra ativos. Não há endpoint de catálogo de gestão com produtos inativos. A interface indica essa limitação; um produto desativado na sessão pode continuar a ser editado pela resposta já recebida, mas não se promete recuperá-lo da lista após recarregar.
2. O índice de restaurantes é público e recebe `$request->user()`, mas a rota não aplica `auth:sanctum`. Um Bearer por si só pode não ativar o contexto de utilizador nesse endpoint, condicionando a listagem de inativos/escopo administrativo. O frontend envia o token, respeita as respostas e nunca contorna a autorização. Este ponto deve ser confirmado pela equipa backend; não foi corrigido em PHP.
3. Não existem contratos de pagamento/SDK de cartão, rotas de entregador, GPS, evento `delivery.location.updated`, auditoria de operador ou reprocessamento de entregas.
4. Não existem sete estados de pedido, cursor pagination, endpoint de métricas globais ou suporte de idempotência. A interface usa os quatro estados e a paginação realmente disponíveis.
5. Os Resources de produto não incluem fotografias, descrição comercial ou prato do dia. O portal usa elementos visuais neutros, sem inventar imagens ou publicidade provenientes do servidor.
6. Definições e segurança web mantêm os componentes e contratos existentes; os endpoints de API entregues não expõem personalização global de marca/temas.

## Verificação executada

- `npm run types:check`: aprovado.
- `npm run check`: formatação e lint aprovados.
- `npm run test:frontend`: 25 testes aprovados em três ficheiros, cobrindo payload, stock, restaurante único, quantidades, valores monetários, permissões, transições, canais, eventos, autenticação HTTP e erros 422/401.
- `npm run build`: aprovado, incluindo geração Wayfinder e assets de produção. O aviso da dependência opcional `fontaine` não impede o build.
- Inspeção no navegador: portal abre em `http://127.0.0.1:8000/`, API retorna lista sem restaurantes publicados, navegação para carrinho vazio e formulário de login funciona.
- Não foram criados utilizadores, restaurantes ou pedidos na base durante a verificação. O fluxo autenticado completo e a entrega efetiva de eventos Reverb precisam de contas/dados e serviços disponíveis; os testes de contrato não substituem essa validação integrada.
- As dependências locais em `vendor` foram sincronizadas com o `composer.lock` existente para conseguir gerar as rotas e o build; os ficheiros Composer permanecem intactos.
- O workflow `.github/workflows/deploy.yml` mantém o build de produção e passa também a executar `npm run test:frontend` antes do build.
- A comparação SHA dos ficheiros versionados de `app`, `routes`, `database`, `config`, `bootstrap` e Composer não encontrou alterações ao backend.

### Roteiro para validação integrada pela equipa

1. Usar um super administrador existente, criar restaurante e administrador inicial na área Restaurantes.
2. Entrar com o administrador do restaurante, criar categoria ativa e produto com stock/preço; criar membro da equipa se necessário.
3. Entrar como cliente, escolher o restaurante, adicionar produtos e confirmar a morada no checkout. Confirmar o total devolvido e o detalhe em Pedidos.
4. Noutra sessão, usar a equipa do restaurante para passar de Pendente a Em preparação e depois Concluído; confirmar atualização no cliente.
5. Simular stock desatualizado com duas sessões: após consumir o stock numa, tentar finalizar na outra e confirmar mensagem do servidor sem perder o carrinho.
6. Criar um pedido pendente e cancelar como cliente; confirmar estado e indisponibilidade de ações posteriores.
7. Interromper a ligação, observar erro, consultar pedidos e só repetir checkout após verificar se houve criação. Com Reverb ativo, testar interrupção e recuperação do websocket.

Esses passos criam e alteram dados reais no ambiente utilizado. Executá-los numa base destinada a testes, com as contas fornecidas pela equipa.

## Limpeza do frontend

Foram removidos 15 ficheiros sem utilização ou substituídos por uma composição direta:

- Layout de cabeçalho alternativo, componente de cabeçalho correspondente e layouts de autenticação alternativos (`auth-card` e `auth-split`).
- Componentes UI sem consumidores: badge, collapsible, icon, navigation-menu, placeholder-pattern, select, toggle e toggle-group.
- `nav-footer`, juntamente com os links de exemplo para o repositório e a documentação do starter Laravel.
- Wrappers `app-shell` e `app-content`; a composição com `SidebarProvider` e `SidebarInset` passou diretamente para o layout lateral. O tipo `AppVariant` deixou de ser necessário.

As cinco dependências diretas usadas apenas pelos componentes removidos (`@radix-ui/react-collapsible`, `react-navigation-menu`, `react-select`, `react-toggle` e `react-toggle-group`) foram retiradas de `package.json` e do lockfile. As bibliotecas ainda utilizadas pelas telas de autenticação, segurança, perfil e componentes acessíveis foram mantidas.

As telas de carrinho, checkout, autenticação API, dashboard, pedidos e gestão são agora carregadas com `React.lazy` e `Suspense`. Os layouts de autenticação e definições web também são carregados sob demanda. O módulo Echo/Pusher só é solicitado quando existe uma sessão API autenticada. Assim, abrir o portal público não exige descarregar antecipadamente todas as áreas de gestão e a infraestrutura websocket.

A limpeza preserva as páginas referenciadas pelas rotas web/Fortify, os testes, os ficheiros gerados pelo Wayfinder e os contratos da API. Nenhum ficheiro do backend foi alterado. A análise de imports deixou de encontrar ficheiros TS/TSX sem utilização entre os módulos mantidos, considerando as entradas Inertia, declarações e testes.

## Fluxo de execução e gestão de estado

### Do documento HTML até à tela

1. A rota web devolve uma entrada Inertia (`welcome`, `dashboard`, `auth/*` ou `settings/*`).
2. `app.blade.php` inclui os assets e o ponto de montagem. `app.tsx` configura título, layouts, tooltips, notificações e aparência.
3. `welcome.tsx` e `dashboard.tsx` montam `application/DeliveryHub.tsx`. Os fluxos web originais usam os layouts correspondentes.
4. `ApiSessionProvider` tenta recuperar a sessão API através do token da aba e de `/auth/me`.
5. `use-view.ts` lê o fragmento da URL e acompanha `hashchange`. O menu apresenta as opções permitidas pelo papel.
6. A tela consulta os endpoints definidos pelos serviços do domínio e apresenta carregamento, dados, estado vazio ou erro.
7. Uma mutação envia os campos do contrato, aguarda confirmação do servidor e atualiza a listagem ou a confirmação de sucesso.

`/#orders` é navegação dentro da aplicação montada na raiz; não corresponde a uma rota PHP `/orders`. Mudar apenas o fragmento não remonta a aplicação inteira. Abrir uma rota web diferente pode remontá-la e perder o carrinho local.

### Onde vive cada estado

| Estado                              | Local                                     | Duração e recuperação                                          |
| ----------------------------------- | ----------------------------------------- | -------------------------------------------------------------- |
| Token Sanctum                       | `sessionStorage`, com fallback em memória | Restauro por `/auth/me`; limpeza em logout/401                 |
| Utilizador API                      | Contexto `ApiSessionProvider`             | Obtido por login/registo ou `/auth/me`                         |
| Sessão web                          | Cookie/sessão Laravel                     | Fluxo separado, gerido por Fortify                             |
| Carrinho                            | `useReducer` no shell                     | Mantido entre telas hash; perdido em reload/saída da aplicação |
| Filtros, página e dados de listagem | Hooks da tela                             | Recarregados ao entrar; pesquisa muda a página para 1          |
| Formulário e erros                  | Componente de formulário                  | Preservados durante falha; não há autosave persistente         |
| Aparência                           | `localStorage` e cookie `appearance`      | Modo claro, escuro ou sistema dos componentes web existentes   |
| Estado websocket                    | Componente `Connection`                   | Recriado por sessão; listeners e ligação limpos ao sair        |

O portal não tem Redux, Zustand, cache persistente de consultas ou service worker. Não promete navegação funcional offline, armazenamento durável de carrinho ou sincronização de alterações feitas sem rede.

### Hooks e componentes comuns

| Módulo/export                                     | Como utilizar                                                                         |
| ------------------------------------------------- | ------------------------------------------------------------------------------------- |
| `useResource<T>(path, revision)`                  | GET abortável; `null` desativa a consulta; expõe `value`, `loading`, `error`, `retry` |
| `useList<T>(endpoint, revision, perPage, search)` | GET paginado; expõe `go`, `page`, `refresh` e os estados da consulta                  |
| `useMutation()`                                   | Executa uma ação com bloqueio síncrono, `busy` e erro contextual                      |
| `useSession()`                                    | Acede ao utilizador, restauro, autenticação e logout dentro do provider               |
| `Title`, `Empty`, `LoadState`, `Feedback`         | Cabeçalhos e estados consistentes das telas                                           |
| `SearchBox`, `Pagination`                         | Pesquisa submetida pelo utilizador e paginação por números                            |
| `Fields`, `ApiForm`                               | Campos, labels, constraints HTML, mensagens 422 e envio controlado                    |
| `EditDialog`, `ConfirmAction`                     | Edição e confirmação acessíveis; a ação é uma função de serviço                       |
| `stateField`, `ActiveBadge`                       | Estado `active`/`inactive` de catálogo                                                |
| `OrderBadge`                                      | Um dos quatro estados reais de pedido                                                 |

As consultas usam `AbortController` para cancelar trabalho quando a tela muda. Não apresentar a resposta antiga de outra URL como se pertencesse à pesquisa atual. Aumentar `revision` ou chamar `retry`/`refresh` para voltar a consultar após uma alteração confirmada.

### Cliente HTTP e erros

`shared/api/client.ts` define o prefixo `/api/v1`, JSON, `credentials: same-origin`, Bearer quando aplicável, XSRF quando existe cookie e um `X-Request-Id` por chamada. Pedidos públicos de login/registo não enviam Bearer. URLs de outra origem são rejeitadas pelo cliente.

`ApiError` transporta `status`, `fields` e `requestId`. O campo `errors` do Laravel é normalizado para uma mensagem por campo. Uma mensagem de domínio pode vir apenas em `message`; não pressupor que todo 422 contém `errors`.

| Situação                    | Tratamento/validação esperados                                                |
| --------------------------- | ----------------------------------------------------------------------------- |
| 401                         | Token local invalidado e sessão API terminada; solicitar novo login           |
| 403                         | Apresentar recusa; não inventar outra rota para contornar a permissão         |
| 404                         | Apresentar o erro do recurso e permitir regressar à listagem                  |
| 419                         | Mensagem de sessão expirada; relevante sobretudo para fluxos web/broadcasting |
| 422 com `errors`            | Mostrar erro junto do campo/item e permitir corrigir                          |
| 422 com apenas `message`    | Mostrar mensagem contextual, por exemplo stock insuficiente                   |
| 429                         | Mostrar erro retornado; não fazer tentativas automáticas ilimitadas           |
| 5xx ou falha de rede        | Mensagem de falha; no checkout, confirmar se houve criação antes de repetir   |
| Sucesso sem JSON inesperado | Mensagem de resposta inesperada; 204 é aceite como ausência de corpo          |

Os tipos TypeScript não validam JSON em tempo de execução. Mudanças incompatíveis nos Resources continuam a poder provocar falhas; validar contratos nos serviços/testes e manter o backend como fonte da verdade.

### Campos principais dos Resources

| Tipo         | Campos de negócio consumidos                                                                              |
| ------------ | --------------------------------------------------------------------------------------------------------- |
| `ApiUser`    | `id`, `name`, `email`, `role`, `restaurant_id` (pode ser `null`)                                          |
| `Restaurant` | `id`, `name`, `address`, `phone_number`, `nif`, `status`                                                  |
| `Category`   | `id`, `restaurant_id`, `name`, `status`                                                                   |
| `Product`    | `id`, `restaurant_id`, `category_id`, `category?`, `name`, `type`, `price`, `quantity`, `status`          |
| `Order`      | `id`, `restaurant_id`, `restaurant?`, `customer?`, `status`, `total`, `address`, `delivery_date`, `items` |
| `OrderItem`  | `id`, `product_id`, `product_name`, `quantity`, `unit_price`, `line_total`                                |

Recursos comerciais incluem ainda os timestamps indicados nos ficheiros `types.ts`. Relações opcionais dependem de o backend as carregar. `product_name` pode ser `null`; a tela apresenta um identificador como alternativa. Não depender de campos internos do modelo que o Resource não expõe.

Resposta individual: `{ "data": { ... } }`. Login/registo usam `{ "user": { ... }, "token": "..." }`. A listagem paginada tem `data`, `links` e `meta`, com `current_page`, `last_page`, `per_page`, `total`, `from` e `to`; `from`/`to` podem ser `null` na lista vazia.

### Métodos HTTP por operação

Todos os caminhos abaixo têm prefixo `/api/v1`:

| Operação                                  | Método e caminho                                                           |
| ----------------------------------------- | -------------------------------------------------------------------------- |
| Login / registo / recuperação             | POST `/auth/login`, `/auth/register`, `/auth/forgot-password`              |
| Restaurar sessão / sair                   | GET `/auth/me`, POST `/auth/logout`                                        |
| Listar / consultar restaurante            | GET `/restaurants`, GET `/restaurants/{id}`                                |
| Criar / editar / desativar restaurante    | POST `/restaurants`, PATCH `/restaurants/{id}`, DELETE `/restaurants/{id}` |
| Menu público                              | GET `/restaurants/{id}/products`                                           |
| Criar / editar / desativar produto        | POST `/products`, PATCH `/products/{id}`, DELETE `/products/{id}`          |
| Listar categorias                         | GET `/categories`                                                          |
| Criar / editar / desativar categoria      | POST `/categories`, PATCH `/categories/{id}`, DELETE `/categories/{id}`    |
| Criar membro da equipa                    | POST `/restaurant/users`                                                   |
| Listar / consultar pedidos do cliente     | GET `/orders`, GET `/orders/{id}`                                          |
| Criar / cancelar pedido                   | POST `/orders`, POST `/orders/{id}/cancel`                                 |
| Listar / consultar pedidos do restaurante | GET `/restaurant/orders`, GET `/restaurant/orders/{id}`                    |
| Atualizar estado pela equipa              | PATCH `/restaurant/orders/{id}/status`                                     |

Não acrescentar `/api/v1` duas vezes ao chamar os serviços. Não usar o router Inertia para endpoints que devolvem JSON puro. Os formulários web originais continuam a usar os contratos Inertia/Fortify e rotas geradas pelo Wayfinder.

## Interface, estilos e acessibilidade

### Estilos e personalização

O portal e a gestão usam Tailwind, superfícies claras, tons slate e destaque laranja. Os estilos de campos e botões comuns estão em `shared/components/api-ui.tsx`; os tokens globais estão em `resources/css/app.css`.

A preferência claro/escuro/sistema das telas web existentes é mantida por `hooks/use-appearance.tsx`, pela classe `dark` no HTML e pelo cookie de aparência. As telas novas têm várias cores explícitas e **não implementam uma adaptação completa ao modo escuro**. Não existe neste estado um seletor de 20 temas, nem persistência de personalização global de marca na API.

Para alterar o tema, começar pelos estilos partilhados e pelos tokens realmente usados. Rever botões, foco, badges, mensagens e fundos em todas as telas; não alterar apenas uma cor de destaque e considerar a aplicação inteira validada. A moeda atual é EUR e a apresentação usa `pt-PT`; não existe seleção de moeda por restaurante. As datas usam a formatação local do navegador em português.

O portal usa placeholders gráficos para produtos porque o Resource não fornece imagem/descrição. Não apresentar imagens de exemplo como se tivessem sido publicadas pelo restaurante.

### Verificações visuais e de acessibilidade

- Rever em 360 px, 768 px e 1280 px ou mais, usando o modo responsivo do navegador.
- Confirmar menu móvel, cabeçalho, botões, formulários, diálogos e resumo do checkout sem sobreposição.
- As tabelas podem ter scroll horizontal dentro do contentor; não devem empurrar a página inteira para fora do ecrã.
- Navegar apenas com Tab, Shift+Tab, Enter, Espaço e Escape. Confirmar foco visível e ordem compreensível.
- Testar o link «Saltar para o conteúdo»; deve focar o conteúdo sem substituir o fragmento de navegação.
- Conferir labels, campos obrigatórios, `aria-invalid`, associação de mensagens e anúncios de loading/sucesso/erro.
- Num diálogo, verificar foco inicial, contenção de foco, fecho por teclado e retorno ao elemento que o abriu.
- Confirmar que estados não dependem só da cor: o texto «Pendente», «Concluído» ou a mensagem de erro deve continuar visível.
- Testar zoom a 200% e nomes/moradas longos. Verificar contraste com as ferramentas de acessibilidade do navegador.

Estas verificações são um roteiro manual. Não existe atualmente uma auditoria automática de acessibilidade ou garantia de conformidade integral.

### Carregamento e desempenho

`React.lazy`/`Suspense` dividem as telas internas e layouts em chunks. O portal inicial não deve solicitar os módulos de gestão nem Echo/Pusher antes de serem necessários. Confirmar no separador Network do navegador ao navegar para cada área.

O build produz assets versionados em `public/build`; esses ficheiros são gerados, não editados manualmente. Acompanhar os tamanhos reportados pelo build e testar a navegação com rede lenta para confirmar que «A carregar…» é mostrado durante imports dinâmicos.

Não interpretar uma divisão em vários chunks como garantia de menos bytes totais. A melhoria pretendida é adiar o carregamento de funcionalidades que ainda não foram abertas. Não aumentar artificialmente o limite de aviso de tamanho para esconder uma regressão.

## Guia de testes automatizados

### O que já existe

A configuração está em `vitest.frontend.config.ts`:

- Runtime **Node**, sem DOM, navegador ou base de dados.
- Descoberta: `resources/js/**/__tests__/**/*.test.ts`.
- Imports do runner: `vite-plus/test`.
- Alias `@` para `resources/js`.
- `clearMocks` e `restoreMocks` ativos; os testes que criam globals devem também usar `vi.unstubAllGlobals()` na limpeza.

| Ficheiro                                     | Testes atuais | Cobertura funcional                                                                                                                         |
| -------------------------------------------- | ------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `domains/orders/__tests__/contracts.test.ts` | 12            | Payload de checkout, morada, itens distintos, restaurante único, stock, quantidade, centavos, papéis, transições e eventos/canais           |
| `shared/api/__tests__/client.test.ts`        | 4             | Prefixo/token/corpo HTTP, erros 422, request ID, login público, rejeição de origem externa e expiração 401                                  |
| `__tests__/integration/services.test.ts`     | 9             | Endpoints por papel/contexto, filtros com paginação, payload de restaurante/produto/login, transições e carregamento paginado de categorias |
| **Total**                                    | **25**        | Regras e integração interna dos serviços frontend                                                                                           |

«Integração» no nome do último ficheiro significa integração entre módulos JavaScript com `fetch` simulado. **Não significa um teste contra a API Laravel real.** A suíte não cria utilizadores, pedidos ou registos na base de dados.

### Comandos de execução

Instalar primeiro as dependências com `npm ci`. Executar:

```sh
# Todos os testes frontend, uma execução
npm run test:frontend

# Apenas os contratos de pedidos
npm run test:frontend -- resources/js/domains/orders/__tests__/contracts.test.ts

# Apenas um caso pelo nome
npm run test:frontend -- -t "preserva filtros"

# Acompanhar alterações durante desenvolvimento
npx vp test --config vitest.frontend.config.ts --watch

# Relatório detalhado dos casos
npm run test:frontend -- --reporter=verbose
```

O script `test:frontend` já inclui `run`; para watch usa-se o comando direto acima. Os scripts de testes não iniciam Laravel, Vite dev, Reverb ou uma base de testes.

### Verificações complementares

```sh
npm run types:check
npm run check
npm run test:frontend
npm run build
```

| Comando         | O que valida                              | O que não comprova                                            |
| --------------- | ----------------------------------------- | ------------------------------------------------------------- |
| `types:check`   | Tipos/imports TypeScript                  | Formato real de uma resposta HTTP ou comportamento no browser |
| `check`         | Formatação e regras de lint configuradas  | Correção comercial, acessibilidade ou layout                  |
| `test:frontend` | Casos automatizados com mocks             | Integração real, CSS ou websocket real                        |
| `build`         | Compilação e geração dos assets/Wayfinder | Que utilizadores e dados reais conseguem concluir cada fluxo  |

`npm run check:fix` pode corrigir ficheiros automaticamente; rever o diff após usá-lo. Para formatar apenas uma alteração, usar, por exemplo, `npx vp fmt resources/js/domains/orders/rules.ts`.

### Como escrever um teste novo

1. Identificar a regra observável e um caso de falha relevante antes de escolher os detalhes da implementação.
2. Se for uma regra pura, testar junto do domínio em `__tests__/*.test.ts`.
3. Se envolver vários serviços, colocar em `resources/js/__tests__/integration/` e simular apenas a fronteira HTTP.
4. Usar fixtures mínimas e fictícias, tipadas com os Resources reais. Nunca copiar credenciais, tokens ou dados pessoais do ambiente.
5. Simular `fetch`, `sessionStorage`, `document.cookie` e `window` apenas quando o módulo precisar deles. Devolver uma `Response` nova por chamada; o corpo de uma resposta já lida não pode ser reutilizado.
6. Validar o resultado relevante: método, caminho, campos enviados, mensagem por campo, estado ou ausência de uma chamada indevida.
7. Cobrir sucesso e falha pertinentes, como transição terminal, 401, 422 ou stock alterado.
8. Limpar globals/mocks entre casos e confirmar que o teste funciona isoladamente e na suíte completa.

Exemplo de regra pura, compatível com a configuração atual:

```ts
import { expect, it } from 'vite-plus/test';
import { transitions } from '@/domains/orders/rules';
import type { ApiUser } from '@/domains/users/types';

it('cliente não cancela um pedido em preparação', () => {
    const customer: ApiUser = {
        id: 1,
        name: 'Cliente de teste',
        email: 'cliente@example.test',
        role: 'customer',
        restaurant_id: null,
    };

    expect(transitions('in_progress', customer)).toEqual([]);
});
```

Este exemplo explica o padrão; a regra já tem cobertura nos testes existentes, pelo que não é necessário duplicá-la.

### Cobertura ainda não implementada

Não existe uma suíte de componentes renderizados em DOM, screenshots automáticos, E2E de navegador ou relatório de percentagem de cobertura configurado. O padrão atual descobre apenas `.test.ts`; adicionar `.test.tsx` por si só não o coloca na suíte.

Para introduzir testes de componentes ou E2E, configurar explicitamente o ambiente, dependências, fixtures, isolamento de dados e job do pipeline. Priorizar: erros e bloqueio de duplo envio do checkout, navegação por papel, foco de diálogos, fluxo cliente/restaurante e reconexão. Não apresentar a contagem dos 25 testes atuais como cobertura dessas interações visuais.

## Guia de testes manuais

### Preparar dados e sessões

Usar uma base de desenvolvimento/testes com migrations aplicadas. Preparar as contas: super administrador, administrador do restaurante A, staff do A, cliente A e cliente B; quando possível, também restaurante B com a sua equipa.

Criar pelo menos uma categoria, um produto ativo com stock e um produto sem stock em cada restaurante de teste. Para paginação, preparar mais de 12 registos numa listagem. Os dados podem ser criados pelas telas disponíveis ou por fixtures aprovadas pela equipa backend; não há gerador de demonstração frontend.

Usar perfis separados do navegador para cliente e equipa. O token da API usa `sessionStorage`, mas cookies de sessão web são partilhados entre abas do mesmo perfil; uma aba duplicada também pode copiar inicialmente o armazenamento. Confirmar sempre o nome e papel da conta que está a efetuar cada ação.

O seeder do super administrador apenas prepara essa conta, não cria automaticamente restaurantes, categorias ou produtos.

### Matriz por tela

| ID    | Tela/ação           | Passos                                                     | Resultado esperado                                                         |
| ----- | ------------------- | ---------------------------------------------------------- | -------------------------------------------------------------------------- |
| UI-01 | Portal vazio        | Abrir `/#portal` sem restaurantes ativos                   | Mensagem de lista vazia; sem dados inventados                              |
| UI-02 | Pesquisa            | Pesquisar um nome existente e outro inexistente            | Resultados filtrados; vazio compreensível; limpar restaura a pesquisa      |
| UI-03 | Paginação           | Avançar/retroceder numa lista com mais de 12 itens         | Página e total corretos; filtro preservado; limites desativados            |
| UI-04 | Menu                | Abrir um restaurante ativo                                 | Nome/contacto e produtos do restaurante selecionado                        |
| UI-05 | Produto sem stock   | Tentar adicionar quantidade zero/inativo                   | Ação indisponível ou mensagem sem alterar o carrinho                       |
| UI-06 | Carrinho            | Adicionar, aumentar, diminuir e remover                    | Quantidades e totais corretos, respeitando stock e máximo de 100           |
| UI-07 | Restaurante único   | Adicionar de A e depois tentar adicionar de B              | Mensagem e carrinho de A preservado                                        |
| UI-08 | Carrinho local      | Navegar por hashes, entrar na conta e depois recarregar    | Navegação/login preservam itens; reload limpa conforme contrato atual      |
| UI-09 | Checkout sem sessão | Abrir checkout com itens sem login                         | Pedido para entrar; nenhum POST de pedido                                  |
| UI-10 | Checkout válido     | Cliente confirma morada e itens                            | Um POST correto, total do servidor, número de pedido e carrinho limpo      |
| UI-11 | Validação           | Usar morada inválida ou data anterior ao dia atual         | Bloqueio HTML/regras locais ou erro 422; dados mantidos para corrigir      |
| UI-12 | Duplo envio         | Com rede lenta, clicar repetidamente no envio              | Botão ocupado e apenas uma chamada POST durante o processamento            |
| UI-13 | Pedidos do cliente  | Abrir lista e detalhe                                      | Apenas pedidos próprios, itens e valores reais                             |
| UI-14 | Cancelamento        | Cancelar pedido pendente e confirmar                       | Pedido cancelado e ausência de novas ações inválidas                       |
| UI-15 | Preparo             | Equipa inicia e conclui um pedido                          | `pending → in_progress → completed`; estado final sem transições           |
| UI-16 | Terminal            | Abrir pedido concluído/cancelado                           | Sem botões para reiniciar ou cancelar novamente                            |
| UI-17 | Dashboard           | Abrir como cliente, equipa e super administrador           | Dados compatíveis com o papel; métricas da página claramente identificadas |
| UI-18 | Novo restaurante    | Super administrador preenche dados e administrador inicial | Criação real ou erros por campo; não confundir com criação de cliente      |
| UI-19 | Editar restaurante  | Administrador abre gestão do seu restaurante               | Filtro pelo ID associado; edição apenas onde autorizada                    |
| UI-20 | Categorias          | Criar, editar e desativar                                  | Lista atualizada após confirmação do servidor                              |
| UI-21 | Produtos            | Criar produto, alterar preço/stock e desativar             | Payload correto; preço decimal; produto desativado sai da lista pública    |
| UI-22 | Equipa              | Administrador cria membro com confirmação da palavra-passe | Confirmação real; staff não recebe esta ação                               |
| UI-23 | Logout              | Sair e tentar voltar a área privada                        | Token e carrinho limpos, conteúdo privado indisponível                     |
| UI-24 | Autenticação web    | Abrir `/login` e, com sessão web, definições               | Fluxo Fortify e layouts preservados; não assumir criação de token API      |
| UI-25 | Apresentação        | Repetir telas em móvel, teclado e zoom                     | Controlos utilizáveis e mensagens legíveis                                 |

Criação, edição, desativação, checkout e cancelamento alteram dados reais. Usar registos de teste identificáveis. Não automatizar destruição de dados de outros ambientes para obter uma demonstração limpa.

### Autorização e isolamento

| Conta               | Verificação                                                             |
| ------------------- | ----------------------------------------------------------------------- |
| Visitante           | Portal e carrinho disponíveis; gestão/pedidos exigem login              |
| Cliente A           | Não vê gestão; não consulta/cancela pedido do cliente B                 |
| Staff A             | Gere catálogo/preparo do A; não cria membros nem gere o restaurante B   |
| Administrador A     | Cria membros e edita A; não recebe ações globais de super administrador |
| Super administrador | Gere restaurantes; não recebe ações de preparo como se fosse staff      |

Para verificar o backend, usar IDs de fixtures autorizadas e ferramentas de teste aprovadas pela equipa. Esconder um botão não comprova que a API recusará acesso direto. Um erro 403/404 não deve ser substituído por dados em cache ou por uma chamada a um endpoint menos restrito.

## Testar falhas, recuperação e tempo real

### Stock desatualizado

1. No cliente A, adicionar um produto cujo stock seja 1.
2. Noutro cliente, concluir um pedido que consuma a unidade.
3. Tentar finalizar o carrinho antigo do cliente A.
4. Confirmar mensagem de stock do servidor, manutenção do carrinho e ausência de confirmação falsa de sucesso.
5. Voltar ao menu para consultar disponibilidade atual e corrigir o pedido.

O stock exibido é uma fotografia da última consulta. A validação transacional do servidor decide se o pedido pode ser criado.

### Falha HTTP 422

Usar um caso válido no browser mas recusado pelo servidor, como email/NIF já existente em fixtures de teste ou stock entretanto alterado. Confirmar erros junto dos campos quando `errors` existir e erro geral quando só houver `message`. O botão deve voltar a permitir correção após terminar a chamada.

### Perda de rede durante checkout

1. Usar DevTools para reduzir a velocidade da rede num ambiente de teste.
2. Iniciar checkout e interromper a ligação antes de receber resposta.
3. Observar a indicação de resultado incerto; não repetir imediatamente.
4. Restaurar a rede e consultar Pedidos para confirmar se o servidor criou o pedido.
5. Só desbloquear nova tentativa após essa verificação.

Desligar a rede antes de enviar testa indisponibilidade; não prova o caso em que o servidor gravou o pedido e a resposta se perdeu. Não existe garantia de idempotência no backend atual.

### Sessão expirada e falta de permissão

Com uma conta de teste, invalidar o token pelo mecanismo disponibilizado pela equipa ou simular um 401 nos testes HTTP. No browser, a aplicação deve abandonar a sessão API e pedir novo login. Para um 403, deve apresentar a recusa sem elevar o papel local ou mudar de conta automaticamente.

### Tempo real e reconexão

1. Confirmar Reverb/filas e variáveis públicas corretas; autenticar cliente e equipa em perfis separados.
2. Em Network, verificar websocket e autorização de canal privado. Não publicar o conteúdo dos cabeçalhos com Bearer ou cookies.
3. Como equipa, iniciar preparação de um pedido do cliente autenticado.
4. Confirmar o evento `.order.status.updated`, nova consulta HTTP e atualização da listagem/detalhe/dashboard aberto no cliente.
5. Interromper **apenas o websocket**, mantendo HTTP, para testar reconexão sem confundir com falha geral de rede.
6. Observar o estado da ligação, recuperar Reverb e confirmar restabelecimento e atualização autoritativa dos dados.
7. Sem websocket configurado, verificar atualização periódica de pedidos com a aba visível e online.
8. Sair da sessão e verificar que a subscrição anterior foi encerrada.

A atualização periódica pode mascarar uma falha websocket. Para comprovar tempo real, observar evento e pedido HTTP no momento da transição, em vez de apenas aguardar que a tela mude após 30 segundos.

## Diagnóstico de problemas

| Sintoma                                 | Verificação e resolução                                                                                                                        |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| Portal sem restaurantes                 | GET `/api/v1/restaurants` devolve lista vazia? Confirmar restaurante ativo e base usada; não é necessariamente erro visual                     |
| `table users has no column named role`  | Consultar `php artisan migrate:status` e aplicar `php artisan migrate`; schema está desatualizado                                              |
| Seeder exige email/palavra-passe        | Confirmar nomes no `.env`, guardar ficheiro e verificar mínimo de 16 caracteres da palavra-passe; não imprimir o segredo                       |
| `Auth guard [sanctum] is not defined`   | Confirmar dependências instaladas, executar `php artisan package:discover` e `php artisan config:clear`; Sanctum regista o guard pelo provider |
| Classe de Sanctum/Pulse/Reverb em falta | Executar `composer install` a partir do lockfile; não remover configuração backend para esconder dependência em falta                          |
| Wayfinder falha no build                | Confirmar Laravel arranca, dependências PHP e ambiente; executar geração manual para observar a causa                                          |
| Asset ou manifesto Vite em falta        | Executar `npm run dev` em desenvolvimento ou `npm run build` para servir assets de produção                                                    |
| API devolve HTML/redirect               | Confirmar URL `/api/v1`, cabeçalho Accept JSON e contrato do endpoint; não tratar HTML como Resource                                           |
| Login em `/login` mas portal pede login | São sessões distintas: Fortify web e token API; usar `/#login` para o portal                                                                   |
| Mudança de `VITE_*` não aparece         | Reiniciar Vite dev ou reconstruir/publicar os assets; variáveis são resolvidas pelo build                                                      |
| Canal privado recusa autorização        | Conferir sessão API, cookie XSRF, Bearer, `/broadcasting/auth`, papel e nome do canal                                                          |
| WS falha em HTTPS                       | Confirmar `https`/WSS, certificado válido e host/porta expostos pela infraestrutura; não desativar validação de certificados                   |
| Inativo desaparece após desativar       | A listagem pública filtra ativos; a gestão de inativos precisa de contrato backend adicional                                                   |
| Após publicar há erro ao carregar chunk | Confirmar build/manifesto da mesma versão e disponibilização consistente dos assets; testar reload controlado, que pode limpar carrinho        |

Sequência de diagnóstico sem expor segredos:

```sh
php artisan --version
php artisan migrate:status
php artisan route:list --path=api/v1
composer show laravel/sanctum
```

Quando o problema for descoberta/configuração de pacotes, após instalar as dependências:

```sh
php artisan package:discover
php artisan config:clear
```

Esses comandos atualizam caches do ambiente; não substituem migrations, não criam automaticamente o super administrador e não exigem editar `config/auth.php` para adicionar manualmente o guard do Sanctum. Não usar comandos destrutivos de base como solução genérica.

## Desenvolvimento e entrega

### Acrescentar uma funcionalidade

1. Ler a rota, middleware, Form Request e API Resource entregues pelo backend. Confirmar papel e restaurante associados, método, campos, validação e formato de erro.
2. Atualizar `types.ts` do domínio, sem expor campos internos do modelo.
3. Criar/ajustar o serviço e o filtro de campos enviados. Colocar regras partilhadas no domínio e apresentação na tela.
4. Reutilizar componentes de formulário/estados; não duplicar transporte HTTP ou interpretação de erros em cada botão.
5. Se a tela combina domínios, usar `features`; se é uma operação de um domínio, usar a pasta desse domínio.
6. Ligar a tela à navegação e ao menu com as permissões adequadas. Adicionar carregamento sob demanda quando fizer sentido.
7. Tratar sucesso, loading, vazio, erro e nova tentativa. Pedidos cujo resultado pode ter sido gravado no servidor exigem recuperação cuidadosa.
8. Acrescentar testes que comprovem o comportamento novo ou a regressão corrigida, e executar a validação manual afetada.
9. Atualizar este guia com o contrato, como abrir a tela, como testar e limitações reais.

Uma nova pasta em `resources/js/pages` não cria uma rota Laravel. Não alterar `routes/*.php`, controllers ou migrations para completar uma tarefa exclusivamente frontend sem alinhar o contrato com a equipa backend. Alterações aos ficheiros gerados pelo Wayfinder devem ser feitas na origem apropriada e regeneradas, nunca aplicadas à mão.

### Critérios para rever uma alteração

- Imports usam `@/` para outros módulos e caminhos locais quando apropriado; `shared` não depende de regras de um domínio.
- Não há respostas comerciais fictícias na experiência real nem promessas de pagamento/entrega sem API correspondente.
- Preços decimais são convertidos para centavos antes de somar; totais confirmados vêm do servidor.
- Permissões, quatro estados e isolamento de restaurante são consistentes nas telas, serviços e testes.
- Mutações bloqueiam duplo envio, interpretam 422 e não repetem automaticamente checkout incerto.
- Listagens preservam filtros ao paginar e distinguem vazio de falha.
- As telas utilizadas em auth/settings web continuam disponíveis; não eliminar componentes só por não serem usados no portal.
- Não há tokens/credenciais em código, logs, screenshots de entrega ou fixtures.
- Novas dependências têm utilização concreta; atualizar lockfile e rever impacto no carregamento.

### Pipeline e assets de produção

O job `frontend` de `.github/workflows/deploy.yml` prepara PHP e Node 22, instala dependências, gera a chave do ambiente de CI e Wayfinder, executa `npm ci`, lint, TypeScript, os testes frontend e o build.

Esse job usa mocks para os testes JavaScript. Não configura uma demonstração autenticada, não valida visualmente as telas e não comprova entrega real de websocket. Os jobs PHP/containers do mesmo workflow são verificações complementares; não substituem a validação da interface.

A geração `npm run build` produz `public/build`. O script `build:ssr` também está declarado, mas a validação registada neste documento é do build normal; não se afirma que o runtime SSR esteja configurado, publicado ou testado de ponta a ponta.

Antes de publicar, validar que o HTML, `manifest.json`, chunks e variáveis públicas pertencem à mesma versão. Iniciar a publicação efetiva apenas pelo processo acordado para o ambiente; um build local aprovado não significa que o site foi publicado.

### Checklist de entrega

```text
[ ] Contrato e papel conferidos com o backend
[ ] npm run types:check
[ ] npm run check
[ ] npm run test:frontend
[ ] npm run build
[ ] Fluxo principal verificado no navegador
[ ] Erro e recuperação verificados para a funcionalidade alterada
[ ] Layout móvel, teclado e estados vazios verificados
[ ] Integração autenticada/Reverb validada quando fizer parte da alteração
[ ] FRONTENDSTING.md atualizado
[ ] Diff revisto, sem segredos nem alterações backend não combinadas
```

### Registo de uma sessão de testes

Ao entregar uma alteração, registar resultados concretos, em vez de escrever apenas «testado»:

```text
Data / responsável:
Commit ou versão:
Ambiente / origem da aplicação:
Navegador e tamanhos testados:
Papéis usados (sem credenciais):
Dados/fixtures utilizados:
Comandos executados e resultado:
Casos manuais (UI-xx) e resultado:
Falha e recuperação observadas:
Evidência sanitizada (se aplicável):
Limitações / casos não executados:
```

As verificações automatizadas e de navegação já realizadas estão registadas em «Verificação executada». O fluxo autenticado completo, transações de stock concorrentes, acessibilidade e reconexão real continuam a exigir execução do roteiro com dados e serviços apropriados; a documentação não converte esses casos em testes já aprovados.

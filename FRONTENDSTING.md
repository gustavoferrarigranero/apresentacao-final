# Frontend DeliveryHub — integração com o backend entregue

## Âmbito

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

Não é necessário executar migrations ou seeders para validar a interface. Uma base sem restaurantes ativos produz uma lista vazia com mensagem apropriada. Para testar fluxos autenticados, utilizar contas disponibilizadas pela equipa do backend; o registo no portal cria efetivamente um cliente na base ligada.

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

# restaurants

## Description

<details>
<summary><strong>Table Definition</strong></summary>

```sql
CREATE TABLE "restaurants" ("id" integer primary key autoincrement not null, "name" varchar not null, "address" varchar not null, "phone_number" varchar not null, "nif" varchar not null, "status" varchar check ("status" in ('active', 'inactive')) not null default 'active', "created_at" datetime, "updated_at" datetime)
```

</details>

## Columns

| Name         | Type     | Default  | Nullable | Children                                                                                | Parents | Comment |
| ------------ | -------- | -------- | -------- | --------------------------------------------------------------------------------------- | ------- | ------- |
| id           | INTEGER  |          | false    | [users](users.md) [categories](categories.md) [produts](produts.md) [orders](orders.md) |         |         |
| name         | varchar  |          | false    |                                                                                         |         |         |
| address      | varchar  |          | false    |                                                                                         |         |         |
| phone_number | varchar  |          | false    |                                                                                         |         |         |
| nif          | varchar  |          | false    |                                                                                         |         |         |
| status       | varchar  | 'active' | false    |                                                                                         |         |         |
| created_at   | datetime |          | true     |                                                                                         |         |         |
| updated_at   | datetime |          | true     |                                                                                         |         |         |

## Constraints

| Name | Type        | Definition                                 |
| ---- | ----------- | ------------------------------------------ |
| id   | PRIMARY KEY | PRIMARY KEY (id)                           |
| -    | CHECK       | check ("status" in ('active', 'inactive')) |

## Indexes

| Name                   | Definition                                                            |
| ---------------------- | --------------------------------------------------------------------- |
| restaurants_nif_unique | CREATE UNIQUE INDEX "restaurants_nif_unique" on "restaurants" ("nif") |

## Relations

```mermaid
erDiagram

"users" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"passkeys" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (customer_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"categories" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"produts" }o--|| "categories" : "FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"produts" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"order_itens" }o--|| "produts" : "FOREIGN KEY (product_id) REFERENCES produts (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"order_itens" }o--|| "orders" : "FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"

"restaurants" {
  INTEGER id
  varchar name
  varchar address
  varchar phone_number
  varchar nif
  varchar status
  datetime created_at
  datetime updated_at
}
"users" {
  INTEGER id
  varchar name
  varchar email
  datetime email_verified_at
  varchar password
  varchar remember_token
  datetime created_at
  datetime updated_at
  TEXT two_factor_secret
  TEXT two_factor_recovery_codes
  datetime two_factor_confirmed_at
  INTEGER restaurant_id FK
  varchar address
  varchar contact
  varchar nif
  varchar status
  varchar role
}
"passkeys" {
  INTEGER id
  INTEGER user_id FK
  varchar name
  varchar credential_id
  TEXT credential
  datetime last_used_at
  datetime created_at
  datetime updated_at
}
"orders" {
  INTEGER id
  varchar payment_status
  numeric payment_value
  INTEGER customer_id FK
  INTEGER user_id FK
  varchar address
  date delivery_date
  datetime created_at
  datetime updated_at
  INTEGER restaurant_id FK
}
"categories" {
  INTEGER id
  varchar name
  varchar status
  datetime created_at
  datetime updated_at
  INTEGER restaurant_id FK
}
"produts" {
  INTEGER id
  varchar name
  varchar type
  numeric price
  INTEGER quantity
  INTEGER category_id FK
  varchar status
  datetime created_at
  datetime updated_at
  INTEGER restaurant_id FK
}
"order_itens" {
  INTEGER id
  INTEGER product_id FK
  numeric value
  datetime created_at
  datetime updated_at
  INTEGER order_id FK
  INTEGER quantity
}
```

---

> Generated by [tbls](https://github.com/k1LoW/tbls)

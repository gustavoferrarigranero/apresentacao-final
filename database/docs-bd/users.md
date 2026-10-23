# users

## Description

<details>
<summary><strong>Table Definition</strong></summary>

```sql
CREATE TABLE "users" ("id" integer primary key autoincrement not null, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime, "password" varchar not null, "remember_token" varchar, "created_at" datetime, "updated_at" datetime, "two_factor_secret" text, "two_factor_recovery_codes" text, "two_factor_confirmed_at" datetime, "restaurant_id" integer, "address" varchar, "contact" varchar, "nif" varchar, "status" varchar check ("status" in ('active', 'inactive')) not null default 'active', "role" varchar not null default 'customer', foreign key("restaurant_id") references "restaurants"("id"))
```

</details>

## Columns

| Name                      | Type     | Default    | Nullable | Children                                    | Parents                       | Comment |
| ------------------------- | -------- | ---------- | -------- | ------------------------------------------- | ----------------------------- | ------- |
| id                        | INTEGER  |            | false    | [passkeys](passkeys.md) [orders](orders.md) |                               |         |
| name                      | varchar  |            | false    |                                             |                               |         |
| email                     | varchar  |            | false    |                                             |                               |         |
| email_verified_at         | datetime |            | true     |                                             |                               |         |
| password                  | varchar  |            | false    |                                             |                               |         |
| remember_token            | varchar  |            | true     |                                             |                               |         |
| created_at                | datetime |            | true     |                                             |                               |         |
| updated_at                | datetime |            | true     |                                             |                               |         |
| two_factor_secret         | TEXT     |            | true     |                                             |                               |         |
| two_factor_recovery_codes | TEXT     |            | true     |                                             |                               |         |
| two_factor_confirmed_at   | datetime |            | true     |                                             |                               |         |
| restaurant_id             | INTEGER  |            | true     |                                             | [restaurants](restaurants.md) |         |
| address                   | varchar  |            | true     |                                             |                               |         |
| contact                   | varchar  |            | true     |                                             |                               |         |
| nif                       | varchar  |            | true     |                                             |                               |         |
| status                    | varchar  | 'active'   | false    |                                             |                               |         |
| role                      | varchar  | 'customer' | false    |                                             |                               |         |

## Constraints

| Name                  | Type        | Definition                                                                                                 |
| --------------------- | ----------- | ---------------------------------------------------------------------------------------------------------- |
| id                    | PRIMARY KEY | PRIMARY KEY (id)                                                                                           |
| - (Foreign key ID: 0) | FOREIGN KEY | FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE |
| -                     | CHECK       | check ("status" in ('active', 'inactive'))                                                                 |

## Indexes

| Name               | Definition                                                    |
| ------------------ | ------------------------------------------------------------- |
| users_role_index   | CREATE INDEX "users_role_index" on "users" ("role")           |
| users_email_unique | CREATE UNIQUE INDEX "users_email_unique" on "users" ("email") |

## Relations

```mermaid
erDiagram

"passkeys" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (customer_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"order_itens" }o--|| "orders" : "FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"users" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"categories" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"produts" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"

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
"order_itens" {
  INTEGER id
  INTEGER product_id FK
  numeric value
  datetime created_at
  datetime updated_at
  INTEGER order_id FK
  INTEGER quantity
}
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
```

---

> Generated by [tbls](https://github.com/k1LoW/tbls)

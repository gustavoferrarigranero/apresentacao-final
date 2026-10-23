# orders

## Description

<details>
<summary><strong>Table Definition</strong></summary>

```sql
CREATE TABLE "orders" ("id" integer primary key autoincrement not null, "payment_status" varchar not null default ('pending'), "payment_value" numeric not null default ('0'), "customer_id" integer not null, "user_id" integer not null, "address" varchar not null, "delivery_date" date not null, "created_at" datetime, "updated_at" datetime, "restaurant_id" integer, foreign key("user_id") references "users"("id") on delete no action on update no action, foreign key("customer_id") references "users"("id") on delete no action on update no action, foreign key("restaurant_id") references "restaurants"("id") on delete restrict)
```

</details>

## Columns

| Name           | Type     | Default   | Nullable | Children                      | Parents                       | Comment |
| -------------- | -------- | --------- | -------- | ----------------------------- | ----------------------------- | ------- |
| id             | INTEGER  |           | false    | [order_itens](order_itens.md) |                               |         |
| payment_status | varchar  | 'pending' | false    |                               |                               |         |
| payment_value  | numeric  | '0'       | false    |                               |                               |         |
| customer_id    | INTEGER  |           | false    |                               | [users](users.md)             |         |
| user_id        | INTEGER  |           | false    |                               | [users](users.md)             |         |
| address        | varchar  |           | false    |                               |                               |         |
| delivery_date  | date     |           | false    |                               |                               |         |
| created_at     | datetime |           | true     |                               |                               |         |
| updated_at     | datetime |           | true     |                               |                               |         |
| restaurant_id  | INTEGER  |           | true     |                               | [restaurants](restaurants.md) |         |

## Constraints

| Name                  | Type        | Definition                                                                                                |
| --------------------- | ----------- | --------------------------------------------------------------------------------------------------------- |
| id                    | PRIMARY KEY | PRIMARY KEY (id)                                                                                          |
| - (Foreign key ID: 0) | FOREIGN KEY | FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE |
| - (Foreign key ID: 1) | FOREIGN KEY | FOREIGN KEY (customer_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE        |
| - (Foreign key ID: 2) | FOREIGN KEY | FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE            |

## Relations

```mermaid
erDiagram

"order_itens" }o--|| "orders" : "FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"order_itens" }o--|| "produts" : "FOREIGN KEY (product_id) REFERENCES produts (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (customer_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"passkeys" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"users" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"categories" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"produts" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"

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
```

---

> Generated by [tbls](https://github.com/k1LoW/tbls)

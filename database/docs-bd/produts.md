# produts

## Description

<details>
<summary><strong>Table Definition</strong></summary>

```sql
CREATE TABLE "produts" ("id" integer primary key autoincrement not null, "name" varchar not null, "type" varchar not null, "price" numeric not null, "quantity" integer not null, "category_id" integer not null, "status" varchar not null default ('active'), "created_at" datetime, "updated_at" datetime, "restaurant_id" integer, foreign key("category_id") references "categories"("id") on delete no action on update no action, foreign key("restaurant_id") references "restaurants"("id") on delete restrict)
```

</details>

## Columns

| Name          | Type     | Default  | Nullable | Children                      | Parents                       | Comment |
| ------------- | -------- | -------- | -------- | ----------------------------- | ----------------------------- | ------- |
| id            | INTEGER  |          | false    | [order_itens](order_itens.md) |                               |         |
| name          | varchar  |          | false    |                               |                               |         |
| type          | varchar  |          | false    |                               |                               |         |
| price         | numeric  |          | false    |                               |                               |         |
| quantity      | INTEGER  |          | false    |                               |                               |         |
| category_id   | INTEGER  |          | false    |                               | [categories](categories.md)   |         |
| status        | varchar  | 'active' | false    |                               |                               |         |
| created_at    | datetime |          | true     |                               |                               |         |
| updated_at    | datetime |          | true     |                               |                               |         |
| restaurant_id | INTEGER  |          | true     |                               | [restaurants](restaurants.md) |         |

## Constraints

| Name                  | Type        | Definition                                                                                                |
| --------------------- | ----------- | --------------------------------------------------------------------------------------------------------- |
| id                    | PRIMARY KEY | PRIMARY KEY (id)                                                                                          |
| - (Foreign key ID: 0) | FOREIGN KEY | FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE |
| - (Foreign key ID: 1) | FOREIGN KEY | FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE   |

## Relations

```mermaid
erDiagram

"order_itens" }o--|| "produts" : "FOREIGN KEY (product_id) REFERENCES produts (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"order_itens" }o--|| "orders" : "FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"produts" }o--|| "categories" : "FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"categories" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"produts" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"users" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"

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
```

---

> Generated by [tbls](https://github.com/k1LoW/tbls)

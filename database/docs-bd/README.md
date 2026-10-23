# database.sqlite

## Description

Documentação da base de dados do projeto

## Tables

| Name                                                | Columns | Comment | Type  |
| --------------------------------------------------- | ------- | ------- | ----- |
| [password_reset_tokens](password_reset_tokens.md)   | 3       |         | table |
| [sessions](sessions.md)                             | 6       |         | table |
| [cache](cache.md)                                   | 3       |         | table |
| [cache_locks](cache_locks.md)                       | 3       |         | table |
| [jobs](jobs.md)                                     | 7       |         | table |
| [job_batches](job_batches.md)                       | 10      |         | table |
| [failed_jobs](failed_jobs.md)                       | 7       |         | table |
| [passkeys](passkeys.md)                             | 8       |         | table |
| [restaurants](restaurants.md)                       | 8       |         | table |
| [users](users.md)                                   | 17      |         | table |
| [order_itens](order_itens.md)                       | 7       |         | table |
| [personal_access_tokens](personal_access_tokens.md) | 10      |         | table |
| [categories](categories.md)                         | 6       |         | table |
| [produts](produts.md)                               | 10      |         | table |
| [orders](orders.md)                                 | 10      |         | table |
| [pulse_values](pulse_values.md)                     | 6       |         | table |
| [pulse_entries](pulse_entries.md)                   | 6       |         | table |
| [pulse_aggregates](pulse_aggregates.md)             | 9       |         | table |

## Relations

```mermaid
erDiagram

"passkeys" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE MATCH NONE"
"users" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"order_itens" }o--|| "orders" : "FOREIGN KEY (order_id) REFERENCES orders (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"order_itens" }o--|| "produts" : "FOREIGN KEY (product_id) REFERENCES produts (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"categories" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"produts" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"produts" }o--|| "categories" : "FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--o| "restaurants" : "FOREIGN KEY (restaurant_id) REFERENCES restaurants (id) ON UPDATE NO ACTION ON DELETE RESTRICT MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (customer_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"
"orders" }o--|| "users" : "FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION MATCH NONE"

"password_reset_tokens" {
  varchar email PK
  varchar token
  datetime created_at
}
"sessions" {
  varchar id PK
  INTEGER user_id
  varchar ip_address
  TEXT user_agent
  TEXT payload
  INTEGER last_activity
}
"cache" {
  varchar key PK
  TEXT value
  INTEGER expiration
}
"cache_locks" {
  varchar key PK
  varchar owner
  INTEGER expiration
}
"jobs" {
  INTEGER id
  varchar queue
  TEXT payload
  INTEGER attempts
  INTEGER reserved_at
  INTEGER available_at
  INTEGER created_at
}
"job_batches" {
  varchar id PK
  varchar name
  INTEGER total_jobs
  INTEGER pending_jobs
  INTEGER failed_jobs
  TEXT failed_job_ids
  TEXT options
  INTEGER cancelled_at
  INTEGER created_at
  INTEGER finished_at
}
"failed_jobs" {
  INTEGER id
  varchar uuid
  varchar connection
  varchar queue
  TEXT payload
  TEXT exception
  datetime failed_at
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
"order_itens" {
  INTEGER id
  INTEGER product_id FK
  numeric value
  datetime created_at
  datetime updated_at
  INTEGER order_id FK
  INTEGER quantity
}
"personal_access_tokens" {
  INTEGER id
  varchar tokenable_type
  INTEGER tokenable_id
  TEXT name
  varchar token
  TEXT abilities
  datetime last_used_at
  datetime expires_at
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
"pulse_values" {
  INTEGER id
  INTEGER timestamp
  varchar type
  TEXT key
  varchar key_hash
  TEXT value
}
"pulse_entries" {
  INTEGER id
  INTEGER timestamp
  varchar type
  TEXT key
  varchar key_hash
  INTEGER value
}
"pulse_aggregates" {
  INTEGER id
  INTEGER bucket
  INTEGER period
  varchar type
  TEXT key
  varchar key_hash
  varchar aggregate
  numeric value
  INTEGER count
}
```

---

> Generated by [tbls](https://github.com/k1LoW/tbls)

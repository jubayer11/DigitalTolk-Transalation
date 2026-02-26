# DigitalTolk Translation Management API (Laravel)

API-driven **Translation Management Service** built for the “Laravel Senior Developer Code Test” requirements.

It supports:
- Multiple locales per translation key (e.g., `en`, `fr`, `es`, and easily extensible)
- Tagging translations for context (e.g., `web`, `mobile`, `desktop`)
- CRUD + search by key/content/tags
- JSON export endpoint for frontend apps (Vue.js etc.) that always returns **fresh** data
- Token-based authentication (JWT)
- Large dataset seeding (100k+ rows) for scalability testing
- Unit/Feature tests (including a lightweight performance test)

> **Note about Docker:** This project intentionally does **not** include Docker setup.

---

## Tech Stack

- **Laravel 12**
- **PHP** (recommended: **8.4+**)
- **SQLite** (default for quick setup)  
  Works with MySQL/Postgres too (recommended for real performance benchmarking)
- **JWT Auth:** `php-open-source-saver/jwt-auth`

---

## Requirement Coverage (Checklist)

### Core Requirements
- ✅ Store translations for multiple locales (translations table keyed by `translation_key_id + locale`)
- ✅ Add new languages in the future (no schema change needed; just insert new locale rows)
- ✅ Tag translations for context (many-to-many: translation_keys ↔ tags)
- ✅ Endpoints: create, update, view, delete, search
- ✅ Search by tags, keys, or content
- ✅ JSON export endpoint for frontend consumption
- ✅ Export endpoint always returns updated translations (cache invalidation on every write)
- ✅ Seed 100k+ records for scalability testing (command using chunked bulk inserts)

### Performance Considerations
- ✅ Export uses **JOIN-based query** and returns **key-value JSON**
- ✅ Indexes added for common filters (`key`, `locale`, pivot indexes, timestamps)
- ✅ Export caching by `(locale + tags)` with invalidation after writes
- ✅ Eager loading for search results

### Plus Points (Optional)
- ✅ Optimized SQL queries (especially export)
- ✅ Token-based authentication (JWT)
- ✅ Tests included
- ➖ Swagger/OpenAPI: not included (Postman collection is provided instead)
- ➖ CDN support: not included (not relevant for pure API JSON output)
- ➖ Docker: not included

---

## Project Architecture (Why this design)

The code follows SOLID-ish separation:

- **Controllers**: HTTP layer only
- **Requests**: validation + normalization
- **Services**: business logic + transactions + cache invalidation
- **Repository**: query + persistence logic
- **Resources**: consistent API output formatting

This makes it easy to swap DB engines, optimize queries, or add features (e.g., namespaces, versioning, soft deletes, full-text search).

---

## Database Schema (High Level)

- `translation_keys`
    - `id`, `key (unique)`, timestamps
- `translations`
    - `id`, `translation_key_id (FK)`, `locale`, `content`, timestamps
    - unique: `(translation_key_id, locale)`
- `tags`
    - `id`, `name (unique)`
- `translation_key_tag` (pivot)
    - `(translation_key_id, tag_id)` composite PK

Indexes are added for:
- `translation_keys.key` (unique)
- `translations.locale`
- `translations.updated_at`
- `translation_key_tag(tag_id, translation_key_id)`
- timestamps on key/translation tables (helps export freshness + ordering)

---

## Setup (Quickest: Use the Bundled Large SQLite DB)

This repo includes a **pre-populated SQLite database** file:

- `digitaltolk_translation`
- Contains ~**40,001 translation keys**
- Contains ~**120,003 translation rows** (3 locales per key) ✅ 100k+
- Contains an admin user

### 1) Requirements
- PHP 8.4+ (recommended)
- Composer

### 2) Install
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret --force
```
### 3) Use the bundled DB

Open `.env` and set:

```env
DB_CONNECTION=sqlite
DB_DATABASE=digitaltolk_translation

# Cache store can be file for simplicity:
CACHE_STORE=file
```
### 4) Run the server
```bash
php artisan serve
```
API base URL:

http://127.0.0.1:8000/api


### 5) Default Admin Credentials (bundled DB)
```bash
Email: admin@example.com

Password: password123
```
## Fresh Setup (Empty DB + Migrations)

If you don’t want to use the bundled DB:

### 1) Create a sqlite database file
```bash
touch database/database.sqlite
```
### 2) Update .env
```bash
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
CACHE_STORE=file
```
### 3) Migrate + seed
```bash
php artisan migrate
php artisan db:seed
php artisan app:seed-large-translation-dataset --keys=40000 --locales=en,fr,es --tags=web,mobile,desktop --chunk=1000
```
Seeder creates a sample user (admin@example.com) with password `password123` (factory default).
You can create an admin quickly via tinker:

## Authentication (JWT)

### Login
```bash
POST /api/auth/login
```
Body:

` {
"email": "admin@example.com",
"password": "password123"
} `

Response:
` {
"accessToken": "....",
"tokenType": "bearer",
"expiresIn": 3600
} `


---

## ✅ “API Quick Reference” 

```md
## API Quick Reference

Base URL:
- `http://127.0.0.1:8000/api`

### Public Endpoints
- `GET /health`
- `GET /translations` (search + filter)
- `GET /translations/{key}` (details with locales + tags)
- `GET /translations/export?locale=en` (JSON export)
  - optional: `&tags[]=web&tags[]=mobile`

### Auth Endpoints
- `POST /auth/login`
- `GET /auth/me` (protected)
- `POST /auth/refresh` (protected)
- `POST /auth/logout` (protected)

### Protected Endpoints (JWT required)
- `POST /translations`
- `PUT /translations/{key}`
- `DELETE /translations/{key}`

```

## Postman Collection

A ready-to-import Postman collection is included in this repository:

- `docs/postman/DigitalTolk-Translation.postman_collection.json`

Import it into Postman (File → Import) to quickly test:
- Auth login + bearer token usage
- CRUD endpoints
- Search filters
- JSON export endpoint

## Tests

Run all tests:
```bash
php artisan test
```

## License

This repository is provided **for assessment purposes only** as part of the DigitalTolk code test.

You may review and run the code for evaluation, but you may not redistribute or use it commercially without written permission from the author.

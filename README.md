# Inventory REST API

Laravel 13 / PHP 8.5 / MySQL 8.4. Kizárólag API-t kiszolgáló alkalmazás, Node és frontend-build nélkül.

## Indítás

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

- API: `http://localhost:8080/api/v1`.
- Állapotellenőrzés: `GET http://localhost:8080/up`.
- A gyökérútvonalon (`/`) nincs alkalmazásoldal; 404-et ad.

A Docker szolgáltatásai: `app` (PHP-FPM), `nginx`, `mysql` és `scheduler`.
A MySQL-adatok a `mysql-data` volume-ban maradnak.
A scheduler futtatja az ütemezett feladatokat, köztük az API-naplók napi takarítását.

## API

- CRUD: `/assets`, `/asset-models`, `/contracts`.
- Listázás: `/manufacturers`, `/vendors`.
- Darabszámok: az adott erőforrás `/count` útvonala.
- Szerződés eszközei: `GET /contracts/{contract}/assets`.
- Kapcsolt eszközök száma: `GET /contracts/{contract}/assets/count`.
- Hozzárendelés: `PUT /contracts/{contract}/assets/{asset}`.
- Leválasztás: `DELETE /contracts/{contract}/assets/{asset}`.

Az API-végpontok szándékosan hitelesítés nélkül érhetők el.

A listák kurzoros lapozást használnak: `?per_page=15&cursor=...`.
A következő és előző kurzor a válasz `meta` mezőjében található.
Az eszközlisták opcionális kapcsolatai: `?include=model,manufacturer,contracts`.

A hibák egységes `error` objektumban érkeznek, a kérésazonosító az `X-Request-Id` fejlécben is szerepel.

## OpenAPI-dokumentáció

- Felület: `http://localhost:8080/docs/api`.
- OpenAPI JSON: `http://localhost:8080/docs/api.json`.

Mindkét útvonal HTTP Basic Auth-tal védett.
A `.env` fájlban állítsd be az `API_DOCS_USERNAME` és `API_DOCS_PASSWORD` értékét.
Üres beállításokkal a dokumentáció zárva marad.
A dokumentáció felülete nem igényel helyi frontend-buildet.

## Konfiguráció

| Változó | Alapérték | Szerep |
|---|---|---|
| `APP_PORT` | `8080` | Nginx hostport |
| `FORWARD_DB_PORT` | `3306` | MySQL hostport |
| `DB_HOST` | `mysql` | Adatbázis szolgáltatásneve |
| `UID`, `GID` | `1000` | Konténer felhasználója és csoportja |
| `API_RATE_LIMIT_PER_MINUTE` | `60` | Percenkénti kéréskorlát kliensenként |
| `API_COUNT_CACHE_TTL` | `60` | Darabszámcache élettartama másodpercben |
| `API_CONTRACT_ASSETS_PREVIEW` | `15` | Szerződésbe ágyazott eszközelőnézet mérete |
| `API_LOGGING_ENABLED` | `true` | Adatbázisos request/response naplózás |
| `API_LOGGING_MAX_BODY_LENGTH` | `65535` | Naplózott törzsek méretkorlátja |
| `API_LOGGING_RETENTION_DAYS` | `30` | Naplók megőrzése napokban |

A naplózó maszkolja a konfigurált érzékeny mezőket és fejléceket.
A megőrzési időn túli bejegyzéseket a scheduler naponta törli.

## Fejlesztés és ellenőrzés

```bash
docker compose exec app php artisan test
docker compose exec app php artisan route:list
docker compose exec app vendor/bin/pint --test
docker compose logs -f app nginx scheduler
docker compose down
```

A tesztek memóriabeli SQLite-adatbázist használnak.

## Felépítés

- `routes/api.php`: verziózott API-útvonalak.
- `bootstrap/app.php`: routing, middleware-ek és hibakezelés.
- `app/Http`: kontrollerek, validáció, válaszok és middleware-ek.
- `app/Services`: üzleti műveletek és naplózás.
- `app/OpenApi`: dokumentációs kiegészítések.
- `database`: migrációk, factory-k és seederek.
- `tests`: unit- és funkcionális tesztek.

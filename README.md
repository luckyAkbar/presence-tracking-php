# SIMPLE PRESENCE TRACKING

## Running locally

Requirements: Docker and Docker Compose plugin.

Commands:

```bash
docker compose up -d --build
curl -s http://127.0.0.1:8080/api/ping | jq .
```

Expected output:

```json
{ "service": "presence-tracking", "status": "ok", "db": "ok", "time": "..." }
```

## Project layout

- `public/`: web root. Only files here are publicly accessible. Entry point: `public/index.php`.
- `src/`: application code. Namespaced under `App\...`.
  - `Config/Config.php`: simple config holder.
  - `Support/Db.php`: PDO connection and ping.
  - `Http/Router.php`: tiny router.
- `docker/`: container configs
  - `nginx/`: reverse proxy serving `public/` and forwarding PHP to `php:9000`.
  - `php/`: PHP-FPM 8.3 with secure defaults and `pdo_mysql`.
  - `mysql/`: MySQL 8 with init script.
- `compose.yaml`: defines `proxy` (Nginx), `php` (FPM), and `mysql`.

## Security notes

- Only `public/` is exposed by the proxy. `src/` is not web-accessible.
- Nginx blocks dotfiles and common sensitive extensions.
- PHP config uses `open_basedir` to restrict filesystem access to `public/`, `src/`, and `/tmp`.

an app that lets you or your organization track presence in every occasion. This application will lets you:
1. register your organization
2. create presence list for an occasion / meeting
3. track presence for all the intended participant / create a guest presence list
4. recapitulate presence stats across all occastions / meetings

## Product Design

this section will point out product-related requirement, such as users, use cases, feature requests, etc

### User Identification / User Roles

1. Organization Admin
2. Organization Member
3. Guests

### User's Stories

1. As an [Organization Admin] I want to initiate a presence tracker to track presence in a meeting / occasion to know which person from my organization member present and which one that doesn't.
2. 

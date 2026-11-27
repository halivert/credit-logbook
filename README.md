# Bitácora de crédito

Registro personal de tarjetas de crédito: compras, compras a meses y pagos.

Laravel 13 · Inertia 3 · Vue 3 · Tailwind 3 · Vite 8

## Requisitos

- PHP 8.4.1+ con `pdo_mysql` (o `pdo_sqlite`)
- Composer 2
- Node 24 (ver `.nvmrc`)
- MariaDB 10.11, o Docker para usar Sail (`docker-compose.yml`)

## Puesta en marcha

```sh
composer install
npm ci

cp .env.example .env
php artisan key:generate
# Configura DB_USERNAME / DB_PASSWORD en .env
php artisan migrate

npm run dev        # en otra terminal: php artisan serve
```

El registro de usuarios está apagado por defecto; ponlo en `REGISTRATION=true`
en `.env` para crear la primera cuenta.

## Comandos

| Comando             | Qué hace                                            |
| ------------------- | --------------------------------------------------- |
| `composer test`     | Suite de tests (SQLite en memoria, no necesita BD)  |
| `npm run build`     | Build de producción del frontend                    |
| `npm run typecheck` | Chequeo de tipos con `vue-tsc`                      |

## Dependencias

Las versiones están fijadas: `composer.lock` y `package-lock.json`, más versiones
exactas en `package.json` (`.npmrc` usa `save-exact`). `composer.json` fija la
plataforma en PHP 8.4.1 para que el lock siga siendo instalable en cualquier 8.4.

## Despliegue

Producción corre en un servidor de Laravel Forge; ver [docs/deploy.md](docs/deploy.md).

## Licencia

[GNU AGPL v3.0 o posterior](LICENSE). Si modificas este proyecto y lo ofreces
como servicio en red, debes publicar el código fuente de tus cambios bajo la
misma licencia.

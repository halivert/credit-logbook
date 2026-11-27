# Despliegue en Laravel Forge con Docker

La app corre en Docker Compose (`compose.prod.yml`) dentro de un servidor de Forge:

```
Internet ──HTTPS──▶ nginx de Forge (TLS, Let's Encrypt)
                       │ http://127.0.0.1:8080
                       ▼
                    app (FrankenPHP) ── worker ── scheduler
                       │
                    mariadb, redis (sin puertos expuestos)
```

Solo `app` publica un puerto y únicamente en `127.0.0.1`, así que todo el
tráfico pasa por el nginx de Forge.

## 1. Servidor

En Forge, crea el servidor e instala Docker (por SSH):

```sh
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker forge
```

Cierra sesión y vuelve a entrar para que `forge` pueda usar `docker`.

## 2. Sitio

1. Crea el sitio `tarjetero.halivert.dev` conectado a este repositorio, rama `main`.
   No marques "Install Composer dependencies": todo se construye en la imagen.
2. En **SSL**, emite el certificado de Let's Encrypt.
3. En **Files › Edit Nginx Configuration**, reemplaza el bloque `location /`
   (y elimina el de `location ~ \.php$`) por:

    ```nginx
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_set_header X-Forwarded-Host $host;
        client_max_body_size 8m;
    }
    ```

## 3. Variables de entorno

En **Environment** de Forge (es el `.env` del sitio, que lee `compose.prod.yml`):

```dotenv
APP_NAME=Tarjetero
APP_ENV=production
APP_KEY=            # ver abajo
APP_DEBUG=false
APP_URL=https://tarjetero.halivert.dev
TRUSTED_PROXIES=*   # solo nginx del host llega al contenedor

REGISTRATION=false

DB_CONNECTION=mariadb
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=tarjetero
DB_USERNAME=tarjetero
DB_PASSWORD=        # openssl rand -hex 24
DB_ROOT_PASSWORD=   # openssl rand -hex 24

REDIS_HOST=redis
REDIS_PASSWORD=     # openssl rand -hex 24

SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp    # o el proveedor que elijas
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=tarjetero@halivert.dev
MAIL_FROM_NAME="${APP_NAME}"
```

Genera la `APP_KEY` una sola vez y guárdala en un gestor de contraseñas:
**si se pierde, el límite de las tarjetas (cifrado) no se puede recuperar**.

```sh
docker run --rm php:8.4-cli php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

## 4. Script de despliegue

En **Deployments › Deploy Script**:

```sh
set -e
cd $FORGE_SITE_PATH

git pull origin $FORGE_SITE_BRANCH

COMPOSE="docker compose -f compose.prod.yml"

$COMPOSE build
$COMPOSE up -d mariadb redis --wait

# En un contenedor aparte, antes de reemplazar la app
$COMPOSE run --rm --no-deps app php artisan migrate --force

$COMPOSE up -d --remove-orphans --wait
docker image prune -f
```

Activa **Quick Deploy** si quieres desplegar en cada push a `main`.

## 5. Primer usuario

Con `REGISTRATION=false`, crea tu usuario desde el servidor:

```sh
cd /home/forge/tarjetero.halivert.dev
docker compose -f compose.prod.yml exec app php artisan tinker
>>> App\Models\User::create(['name' => '…', 'username' => '…', 'email' => '…', 'password' => '…']);
```

## 6. Respaldos

Los datos viven en el volumen `mariadb`. En **Scheduler** de Forge agrega un
respaldo diario:

```sh
cd /home/forge/tarjetero.halivert.dev && docker compose -f compose.prod.yml exec -T mariadb sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction "$MARIADB_DATABASE"' | gzip > /home/forge/backups/tarjetero-$(date +\%F).sql.gz
```

Crea antes `/home/forge/backups` y copia los respaldos fuera del servidor
(S3, Backblaze…). Un respaldo sin la `APP_KEY` no sirve para el límite cifrado.

## Operación

```sh
docker compose -f compose.prod.yml ps               # estado
docker compose -f compose.prod.yml logs -f app      # logs (van a stderr)
docker compose -f compose.prod.yml exec app php artisan …
```

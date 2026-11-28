# Despliegue en Laravel Forge

Forge administra el servidor (Ubuntu): nginx, PHP-FPM, MariaDB, Redis, el
certificado TLS, el worker de colas y el scheduler.

## 1. Servidor

Al crear el servidor en Forge:

- **PHP 8.4**
- **Base de datos: MariaDB** (la app usa el driver `mariadb`)
- Node se instala con el servidor. La app exige **Node 24** (`.npmrc` tiene
  `engine-strict`): revisa con `node -v` y, si hace falta, cámbialo en
  *Server › PHP / Node*.

## 2. Sitio

1. Crea el sitio `tarjetero.halivert.dev`, conectado a este repositorio,
   rama `main`, con *web directory* `/public`.
2. **SSL**: emite el certificado de Let's Encrypt y activa la redirección de
   HTTP a HTTPS.
3. **Database**: crea la base `tarjetero` y un usuario solo para ella.

## 3. Variables de entorno

En *Environment*:

```dotenv
APP_NAME=Tarjetero
APP_ENV=production
APP_KEY=            # php artisan key:generate --show
# Identificador de las passkeys, aparte de APP_KEY para que no cambie al rotarla
PASSKEYS_USER_HANDLE_SECRET=   # openssl rand -hex 32
APP_DEBUG=false
APP_URL=https://tarjetero.halivert.dev

REGISTRATION=false

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tarjetero
DB_USERNAME=tarjetero
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

LOG_CHANNEL=daily
LOG_LEVEL=warning

MAIL_MAILER=smtp    # o el proveedor que elijas
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=tarjetero@halivert.dev
MAIL_FROM_NAME="${APP_NAME}"

# A quién avisar si una hora tiene muchos más intentos sospechosos de lo normal
SECURITY_ALERT_EMAIL=tu@correo.com
```

**Guarda la `APP_KEY` en un gestor de contraseñas.** El límite de las tarjetas
se guarda cifrado con ella: si se pierde, no se puede recuperar ni desde un
respaldo.

Si pones Cloudflare (u otro proxy) delante, agrega `TRUSTED_PROXIES` con sus
rangos de IP para que Laravel vea las peticiones como HTTPS.

## 4. Script de despliegue

En *Deployments › Deploy Script*:

```sh
set -e
cd $FORGE_SITE_PATH

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --classmap-authoritative

npm ci
npm run build

$FORGE_PHP artisan migrate --force

$FORGE_PHP artisan optimize

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

$FORGE_PHP artisan queue:restart
```

Si usas *zero downtime deployments*, conserva las macros que pone Forge.

## 5. Worker y scheduler

- *Queues*: conexión `redis`, cola `default`, `--tries=3`.
- *Scheduler*: `php artisan schedule:run` cada minuto.

## 6. Primer usuario

Con `REGISTRATION=false`, créalo por SSH:

```sh
cd /home/forge/tarjetero.halivert.dev
php artisan tinker
>>> App\Models\User::create(['name' => '…', 'username' => '…', 'email' => '…', 'password' => '…']);
```

## 7. Respaldos

Configura *Database Backups* en Forge hacia un almacenamiento externo (S3,
Backblaze…), o un cron diario en *Scheduler*:

```sh
mariadb-dump --defaults-extra-file=/home/forge/.backup.cnf --single-transaction tarjetero | gzip > /home/forge/backups/tarjetero-$(date +\%F).sql.gz
```

`/home/forge/.backup.cnf` guarda las credenciales fuera del comando (que se ve
en `ps`), con permisos `600`:

```ini
[client]
user=tarjetero
password=…
```

Prueba restaurar un respaldo al menos una vez. Sin la `APP_KEY`, el respaldo
no sirve para el límite cifrado.

## 8. Monitoreo de seguridad

Los intentos sospechosos (contraseña o código incorrectos, bloqueos e
intentos con correos sin cuenta) se cuentan por hora, de forma anónima: sin
usuarios, correos ni IPs. Se guardan un año.

```sh
php artisan security:report            # últimos 7 días
php artisan security:report --days=30
```

El scheduler revisa cada hora la hora anterior y manda un correo a
`SECURITY_ALERT_EMAIL` si tuvo al menos 20 eventos y 5 veces el promedio por
hora de la semana anterior (`SECURITY_ALERT_MINIMUM` y
`SECURITY_ALERT_MULTIPLIER` para ajustarlo).


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
# Nombre en la pestaña del navegador; se lee al compilar (npm run build)
VITE_APP_NAME="${APP_NAME}"

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

# Resend por SMTP, ver "Correo" abajo
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=resend
MAIL_PASSWORD=re_…    # API key de Resend
MAIL_FROM_ADDRESS=no-reply@tarjetero.halivert.dev
MAIL_FROM_NAME="${APP_NAME}"

# A quién avisar si una hora tiene muchos más intentos sospechosos de lo normal
SECURITY_ALERT_EMAIL=tu@correo.com

# Aviso de privacidad (/privacidad): tu nombre y el correo para solicitudes
PRIVACY_OWNER="Tu nombre"
PRIVACY_EMAIL=privacidad@tu-dominio
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

### Correo

La app manda pocos correos (restablecer contraseña, verificar correo y
alertas de seguridad), así que alcanza el plan gratis de
[Resend](https://resend.com): 3,000 al mes, máximo 100 al día.

1. En Resend, *Domains → Add domain*: `tarjetero.halivert.dev`. Un subdominio
   separa la reputación de estos correos de la de `halivert.dev`.
2. Copia en el DNS los registros que te muestra: DKIM (`resend._domainkey`),
   y el MX y el SPF del subdominio de rebotes (`send.`). El DNS de
   `halivert.dev` está en WordPress.com, donde el nombre va sin el dominio:
   `resend._domainkey.tarjetero` y `send.tarjetero`. Espera a que diga
   *Verified*. Para ver si ya están publicados, sin esperar la propagación:
   `dig +short TXT resend._domainkey.tarjetero.halivert.dev @ns1.wordpress.com`
3. DMARC: no hace falta otro. El de `halivert.dev` ya cubre sus subdominios
   (`sp=`), y uno con `p=none` en el subdominio lo debilitaría.
4. *API Keys → Create*: permiso *Sending access*, solo para ese dominio. Va
   en `MAIL_PASSWORD`; el usuario SMTP siempre es `resend`.
5. Prueba desde el servidor:

   ```sh
   php artisan tinker
   >>> Mail::raw('Prueba', fn ($m) => $m->to('tu@correo.com')->subject('Prueba'));
   ```

Se usa por SMTP a propósito, sin el paquete ni las funciones propias de
Resend: para cambiar de proveedor (por ejemplo a Amazon SES) basta cambiar
estas variables y los registros DNS. Un dominio solo puede tener un registro
SPF; si un día usas dos proveedores, inclúyelos en el mismo.

## 5. Worker y scheduler

- *Queues*: conexión `redis`, cola `default`, `--tries=3`.
- *Scheduler*: `php artisan schedule:run` cada minuto.

## 6. Usuarios

Con `REGISTRATION=false` nadie puede registrarse solo. Las cuentas se crean
por SSH con una invitación, que no necesita correo:

```sh
cd /home/forge/tarjetero.halivert.dev
php artisan user:invite ana@example.com "Ana" --founder
```

Muestra un enlace para compartir por donde sea. Lleva a elegir la contraseña,
vence en 7 días y sirve una vez. `--founder` marca a quienes entraron en la
beta (gratis, como se les prometió); `--again` da un enlace nuevo a alguien
ya invitado. Sirve también para tu propia cuenta.

## 7. Respaldos

Nada en el código hace respaldos: el scheduler de Laravel solo poda datos
viejos y manda alertas. Elige una opción y configúrala en Forge.

**A. *Database Backups* de Forge** hacia un almacenamiento externo (S3,
Backblaze…), con retención de 30 días o menos. Es la mejor: el respaldo no
vive en el mismo servidor.

**B. Un cron diario en *Scheduler***, usuario `forge`, que deja los respaldos
en el mismo servidor. Antes, por SSH:

```sh
mkdir -p /home/forge/backups && chmod 700 /home/forge/backups
```

El comando del cron:

```sh
mariadb-dump --defaults-extra-file=/home/forge/.backup.cnf --single-transaction tarjetero | gzip > /home/forge/backups/tarjetero-$(date +\%F).sql.gz && find /home/forge/backups -name 'tarjetero-*.sql.gz' -mtime +30 -delete
```

El aviso de privacidad promete que los respaldos se borran en máximo 30
días (por quien borra su cuenta): el `find` del final se encarga, y si usas
*Database Backups* de Forge, pon la retención en 30 días o menos.

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

Los intentos sospechosos (contraseña o código incorrectos, bloqueos, IPs
bloqueadas e intentos con correos sin cuenta) quedan en `security_events`
90 días. Los de correos sin cuenta se guardan sin usuario y sin el correo,
solo con la IP.

```sh
php artisan security:report            # últimos 7 días
php artisan security:report --days=30  # hasta 90
```

Muestra los intentos por día y las IPs con más intentos: cuántas cuentas
reales intentaron, cuántos correos sin cuenta y si desde esa IP alguien sí
entró.

**Límite por IP:** tras 30 inicios de sesión fallidos en una hora desde la
misma IP, con cualquier correo, se rechazan los siguientes (web y API)
aunque la contraseña sea correcta. Se ajusta con
`SECURITY_LOGIN_IP_MAX_FAILURES` y `SECURITY_LOGIN_IP_DECAY_MINUTES`.

**Alerta:** el scheduler revisa cada hora la hora anterior y manda un correo
a `SECURITY_ALERT_EMAIL` con los conteos y las IPs principales si tuvo al
menos 20 eventos y 5 veces el promedio por hora de la semana anterior
(`SECURITY_ALERT_MINIMUM` y `SECURITY_ALERT_MULTIPLIER`).

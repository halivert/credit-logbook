# Despliegue en Laravel Forge

Forge administra el servidor (Ubuntu): nginx, PHP-FPM, MariaDB, Redis, el
certificado TLS, el worker de colas y el scheduler.

## 1. Servidor

Al crear el servidor en Forge:

- **PHP 8.4**
- **Base de datos: MySQL 8.4 LTS** (`DB_CONNECTION=mysql`). Forge no ofrece
  MariaDB; la app funciona igual con las dos (migraciones, pruebas y
  respaldos probados en MySQL 8.4). Sail usa MariaDB 10.11.
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

Forge copia `.env.example`, que ya trae los valores de producción. En
*Environment* solo llena lo que está vacío y revisa lo marcado:

| Variable | Valor |
|---|---|
| `APP_KEY` | `php artisan key:generate --show` (Forge suele ponerla) |
| `PASSKEYS_USER_HANDLE_SECRET` | `openssl rand -hex 32` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | los de la base que creaste en Forge |
| `MAIL_PASSWORD` | la API key de Resend (ver "Correo") |
| `SECURITY_ALERT_EMAIL` | tu correo |
| `PRIVACY_OWNER`, `PRIVACY_EMAIL` | tu nombre y un correo que revises |
| `APP_URL` | revisa que sea el dominio del sitio |

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
   en `MAIL_PASSWORD`; lo demás de `MAIL_*` ya viene en `.env.example`.
5. Prueba desde el servidor:

   ```sh
   php artisan tinker
   >>> Mail::raw('Prueba', fn ($m) => $m->to('tu@correo.com')->subject('Prueba'));
   ```

Se usa por SMTP a propósito, sin el paquete ni las funciones propias de
Resend: para cambiar de proveedor (por ejemplo a Amazon SES) basta cambiar
las variables `MAIL_*` y los registros DNS. Un dominio solo puede tener un registro
SPF; si un día usas dos proveedores, inclúyelos en el mismo.

## 5. Worker y scheduler

- *Queues*: conexión `redis`, cola `default`, `--tries=3`. Manda los correos
  de restablecer contraseña, para que la página no espere al servidor de
  correo; sin worker, esos correos no salen.
- *Scheduler*: `php artisan schedule:run` cada minuto. Corre los respaldos,
  la limpieza de eventos de seguridad viejos y las alertas.

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

El scheduler corre `php artisan db:backup` todos los días a las 4:00 (hora
del servidor): respalda la base comprimida en `BACKUP_PATH` y borra los de más
de `BACKUP_KEEP_DAYS` días. No hay nada que configurar en Forge aparte del
scheduler. Si un respaldo falla, llega un correo a `SECURITY_ALERT_EMAIL`.

El aviso de privacidad promete que lo que alguien borra desaparece de los
respaldos en máximo 30 días: no subas `BACKUP_KEEP_DAYS` sin cambiarlo
también ahí.

Los respaldos viven en el mismo servidor: si el servidor se pierde, se
pierden con él. Cópialos a otro lado de vez en cuando, por ejemplo desde tu
computadora:

```sh
rsync -av forge@tarjetero.halivert.dev:/home/forge/backups/ ~/Respaldos/tarjetero/
```

Para probarlo a mano y para restaurar (prueba restaurar al menos una vez):

```sh
php artisan db:backup
gunzip -c /home/forge/backups/tarjetero-AAAA-MM-DD-HHMMSS.sql.gz | mysql -u tarjetero -p tarjetero
```

Sin la `APP_KEY`, el respaldo no sirve para el límite cifrado.

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

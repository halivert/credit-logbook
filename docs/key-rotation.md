# Rotar la APP_KEY

Hazlo si la llave pudo filtrarse (un `.env` en un repo, un log, una captura, o
alguien con acceso al servidor que ya no debe tenerlo).

Con la `APP_KEY` se cifran las sesiones, las cookies y estos datos en la base:

- `credit_cards.limit`
- `users.two_factor_secret` y `users.two_factor_recovery_codes`

## Pasos

1. **Respalda** la base de datos y guarda la llave actual en tu gestor de
   contraseñas.
2. Genera una llave nueva sin escribirla en el `.env`:

    ```sh
    php artisan key:generate --show
    ```

3. En el `.env` (en Forge, *Environment*), pon la nueva en `APP_KEY` y la
   anterior en `APP_PREVIOUS_KEYS`:

    ```dotenv
    APP_KEY=base64:NUEVA...
    APP_PREVIOUS_KEYS=base64:ANTERIOR...
    ```

    Laravel cifra con la nueva y descifra con cualquiera de las dos, así que
    nadie pierde su sesión. Solo dejan de servir los enlaces de recuperación de
    contraseña pendientes (caducan en 60 minutos de todos modos).

4. Aplica la configuración (`php artisan config:cache` o un despliegue).
5. Re-cifra los datos guardados:

    ```sh
    php artisan app:reencrypt --dry-run   # cuántos valores cambiarían
    php artisan app:reencrypt
    ```

    Es seguro repetirlo: lo que ya usa la llave nueva se salta. Si algún valor
    no se puede descifrar con ninguna llave, lo reporta, no lo toca y termina
    con error: en ese caso **no quites** la llave anterior.

6. Revisa que las tarjetas muestren su límite, quita `APP_PREVIOUS_KEYS` y
   vuelve a aplicar la configuración.

Si agregas otra columna con el cast `encrypted`, súmala a
`ReencryptCommand::COLUMNS`.

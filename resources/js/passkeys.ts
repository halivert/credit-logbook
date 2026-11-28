import {
    InvalidDomainError,
    NotSupportedError,
    PasskeyExistsError,
    ServerError,
    UnexpectedResponseError,
    UserCancelledError,
    type PasskeyError,
} from "@laravel/passkeys"

/**
 * The passkeys client reports errors in English: say them in Spanish. A
 * cancelled prompt isn't an error worth showing, people just closed it.
 */
export function passkeyErrorMessage(error: PasskeyError | null): string | undefined {
    if (!error || error instanceof UserCancelledError) return undefined

    if (error instanceof NotSupportedError) return "Este navegador no permite passkeys."
    if (error instanceof PasskeyExistsError) return "Este dispositivo ya tiene una passkey en tu cuenta."
    if (error instanceof InvalidDomainError) return "Las passkeys no funcionan en este dominio."

    if (error instanceof ServerError) {
        if (error.status === 429) return "Demasiados intentos. Espera un minuto y vuelve a intentarlo."
        if (error.status === 419) return "La página expiró. Recárgala y vuelve a intentarlo."

        // The server's messages are already translated
        return error.message || "No se pudo usar la passkey. Intenta de nuevo."
    }

    if (error instanceof UnexpectedResponseError) return "Algo salió mal. Recarga la página y vuelve a intentarlo."

    return "No se pudo usar la passkey. Intenta de nuevo."
}

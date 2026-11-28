<script setup lang="ts">
import { useFormat } from "@/format"

export type SecurityEventItem = {
    id: string
    type: string
    method: string | null
    detail: string | null
    device: string | null
    ip: string | null
    at: string
    suspicious: boolean
}

defineProps<{
    events: SecurityEventItem[]
}>()

const { toDateTime } = useFormat()

const loginMethods: Record<string, string> = {
    password: "Inicio de sesión con contraseña",
    two_factor: "Inicio de sesión con contraseña y código",
    recovery_code: "Inicio de sesión con código de recuperación",
    passkey: "Inicio de sesión con passkey",
    registration: "Cuenta creada",
    remember: "Sesión recordada en este dispositivo",
}

const descriptions: Record<string, string> = {
    login_failed: "Intento con contraseña incorrecta",
    two_factor_failed: "Intento con código incorrecto",
    lockout: "Demasiados intentos: el inicio de sesión se bloqueó un momento",
    password_changed: "Contraseña cambiada",
    password_reset: "Contraseña restablecida por correo",
    two_factor_enabled: "Verificación en dos pasos activada",
    two_factor_disabled: "Verificación en dos pasos desactivada",
    recovery_codes_generated: "Nuevos códigos de recuperación",
    passkey_added: "Passkey agregada",
    passkey_removed: "Passkey borrada",
}

function describe(event: SecurityEventItem): string {
    if (event.type === "login") {
        return loginMethods[event.method ?? ""] ?? "Inicio de sesión"
    }

    const description = descriptions[event.type] ?? event.type

    return event.detail ? `${description}: ${event.detail}` : description
}
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-2xl">Actividad reciente</h2>
            <p class="mt-1 text-lg text-muted">
                Inicios de sesión y cambios de seguridad de los últimos 90
                días. Si ves algo que no hiciste, cambia tu contraseña y revisa
                tus passkeys.
            </p>
        </header>

        <ul
            v-if="events.length"
            class="px-list"
        >
            <li v-for="event in events" :key="event.id" class="py-3">
                <p
                    class="font-medium"
                    :class="{
                        'text-warning': event.suspicious,
                    }"
                >
                    {{ describe(event) }}
                </p>
                <p class="text-lg text-muted">
                    {{ toDateTime(event.at) }}
                    <template v-if="event.device"> · {{ event.device }}</template>
                    <template v-if="event.ip"> · {{ event.ip }}</template>
                </p>
            </li>
        </ul>
        <p v-else class="text-lg text-muted">
            Todavía no hay actividad.
        </p>
    </section>
</template>

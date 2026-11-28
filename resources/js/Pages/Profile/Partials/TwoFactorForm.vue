<script setup lang="ts">
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { router, useForm } from "@inertiajs/vue3"
import { ref } from "vue"

export type TwoFactorState = {
    enabled: boolean
    pending: boolean
    qrCodeSvg: string | null
    setupKey: string | null
    recoveryCodes: string[] | null
}

defineProps<{
    twoFactor: TwoFactorState
}>()

const processing = ref(false)

const confirmation = useForm({ code: "" })

const options = {
    preserveScroll: true,
    onStart: () => (processing.value = true),
    onFinish: () => (processing.value = false),
}

function enable() {
    router.post(route("two-factor.enable"), {}, options)
}

function confirmCode() {
    confirmation.post(route("two-factor.confirm"), {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        onFinish: () => confirmation.reset(),
    })
}

function regenerateRecoveryCodes() {
    router.post(route("two-factor.regenerate-recovery-codes"), {}, options)
}

function disable() {
    if (
        window.confirm(
            "Sin la verificación en dos pasos, tu contraseña basta para entrar. ¿Desactivarla?",
        )
    ) {
        router.delete(route("two-factor.disable"), options)
    }
}

const button =
    "inline-flex justify-center items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition disabled:opacity-50"
const secondary = `${button} bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700`
const danger = `${button} text-red-600 dark:text-red-400 ring-1 ring-red-300 dark:ring-red-800 hover:bg-red-50 dark:hover:bg-red-950`
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-lg font-medium">Verificación en dos pasos</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Además de tu contraseña, te pedimos un código de una app de
                autenticación (Google Authenticator, 1Password, Authy…).
            </p>
        </header>

        <!-- Off -->
        <template v-if="!twoFactor.enabled && !twoFactor.pending">
            <PrimaryButton :disabled="processing" @click="enable"
                >Activar</PrimaryButton
            >
        </template>

        <!-- Set up, waiting for the first code -->
        <template v-else-if="twoFactor.pending">
            <ol class="list-decimal ps-5 space-y-1 text-sm">
                <li>Escanea este código con tu app de autenticación.</li>
                <li>Escribe el código de 6 dígitos que te muestra.</li>
            </ol>

            <div
                v-if="twoFactor.qrCodeSvg"
                class="inline-block rounded-lg bg-white p-3"
                v-html="twoFactor.qrCodeSvg"
            />

            <p class="text-sm text-gray-600 dark:text-gray-400">
                ¿No puedes escanearlo? Escribe esta clave en tu app:
                <code
                    class="block mt-1 break-all font-mono text-gray-900 dark:text-gray-100 select-all"
                    >{{ twoFactor.setupKey }}</code
                >
            </p>

            <form class="space-y-3" @submit.prevent="confirmCode">
                <div>
                    <InputLabel for="code" value="Código" />
                    <TextInput
                        id="code"
                        class="mt-1 block w-40 text-center text-xl tracking-[0.4em] font-mono"
                        v-model="confirmation.code"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="confirmation.errors.code" />
                </div>

                <div class="flex gap-3">
                    <PrimaryButton :disabled="confirmation.processing"
                        >Confirmar</PrimaryButton
                    >
                    <button
                        type="button"
                        :class="secondary"
                        :disabled="processing"
                        @click="disable"
                    >
                        Cancelar
                    </button>
                </div>
            </form>
        </template>

        <!-- On -->
        <template v-else>
            <p class="font-medium text-emerald-600 dark:text-emerald-400">
                Activa
            </p>

            <div
                v-if="twoFactor.recoveryCodes"
                class="rounded-md bg-amber-50 dark:bg-amber-900/30 p-4 space-y-2"
            >
                <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
                    Guarda estos códigos de recuperación en un lugar seguro (por
                    ejemplo, tu gestor de contraseñas). Sirven para entrar si
                    pierdes tu teléfono, cada uno una sola vez. No los
                    volveremos a mostrar.
                </p>
                <ul
                    class="grid grid-cols-2 gap-1 font-mono text-sm text-amber-900 dark:text-amber-100 select-all"
                >
                    <li v-for="code in twoFactor.recoveryCodes" :key="code">
                        {{ code }}
                    </li>
                </ul>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    :class="secondary"
                    :disabled="processing"
                    @click="regenerateRecoveryCodes"
                >
                    Nuevos códigos de recuperación
                </button>
                <button
                    type="button"
                    :class="danger"
                    :disabled="processing"
                    @click="disable"
                >
                    Desactivar
                </button>
            </div>
        </template>
    </section>
</template>

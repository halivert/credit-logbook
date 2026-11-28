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

const secondary = "nes-btn inline-flex justify-center items-center"
const danger = "nes-btn is-error inline-flex justify-center items-center"
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-2xl">Verificación en dos pasos</h2>
            <p class="mt-1 text-lg text-muted">
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
            <ol class="list-decimal ps-5 space-y-1 text-lg">
                <li>Escanea este código con tu app de autenticación.</li>
                <li>Escribe el código de 6 dígitos que te muestra.</li>
            </ol>

            <div
                v-if="twoFactor.qrCodeSvg"
                class="inline-block bg-white p-3 border-4 border-line"
                v-html="twoFactor.qrCodeSvg"
            />

            <p class="text-lg text-muted">
                ¿No puedes escanearlo? Escribe esta clave en tu app:
                <code
                    class="block mt-1 break-all font-mono text-ink select-all"
                    >{{ twoFactor.setupKey }}</code
                >
            </p>

            <form class="space-y-3" @submit.prevent="confirmCode">
                <div>
                    <InputLabel for="code" value="Código" />
                    <TextInput
                        id="code"
                        class="mt-1 block w-40 text-center text-3xl tracking-[0.4em] font-mono"
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
            <p class="font-medium text-success">
                Activa
            </p>

            <div
                v-if="twoFactor.recoveryCodes"
                class="px-alert bg-warning/10 text-warning space-y-2"
            >
                <p class="text-lg font-medium text-warning">
                    Guarda estos códigos de recuperación en un lugar seguro (por
                    ejemplo, tu gestor de contraseñas). Sirven para entrar si
                    pierdes tu teléfono, cada uno una sola vez. No los
                    volveremos a mostrar.
                </p>
                <ul
                    class="grid grid-cols-2 gap-1 font-mono text-lg text-warning select-all"
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

<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, useForm } from "@inertiajs/vue3"
import { nextTick, ref } from "vue"

const usingRecoveryCode = ref(false)
const codeInput = ref<InstanceType<typeof TextInput> | null>(null)

const form = useForm({
    code: "",
    recovery_code: "",
})

async function toggle() {
    usingRecoveryCode.value = !usingRecoveryCode.value
    form.clearErrors()
    form.reset()

    await nextTick()
    codeInput.value?.focus()
}

function submit() {
    form.transform((data) =>
        usingRecoveryCode.value
            ? { recovery_code: data.recovery_code }
            : { code: data.code },
    ).post(route("two-factor.login.store"), {
        onError: () => form.reset(),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Verificación en dos pasos" />

        <h1 class="text-3xl">Verificación en dos pasos</h1>
        <p class="mt-1 mb-4 text-lg text-muted">
            <template v-if="usingRecoveryCode">
                Escribe uno de tus códigos de recuperación. Cada código sirve
                una sola vez.
            </template>
            <template v-else>
                Escribe el código de 6 dígitos de tu app de autenticación.
            </template>
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div v-if="usingRecoveryCode">
                <InputLabel for="recovery_code" value="Código de recuperación" />
                <TextInput
                    id="recovery_code"
                    ref="codeInput"
                    class="mt-1 block w-full font-mono"
                    v-model="form.recovery_code"
                    autocomplete="one-time-code"
                    autocapitalize="off"
                    spellcheck="false"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.recovery_code" />
            </div>

            <div v-else>
                <InputLabel for="code" value="Código" />
                <TextInput
                    id="code"
                    ref="codeInput"
                    class="mt-1 block w-full text-center text-3xl tracking-[0.5em] font-mono"
                    v-model="form.code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    autocomplete="one-time-code"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Continuar</PrimaryButton
            >

            <button
                type="button"
                class="w-full text-lg text-primary hover:underline"
                @click="toggle"
            >
                {{
                    usingRecoveryCode
                        ? "Usar el código de mi app"
                        : "Usar un código de recuperación"
                }}
            </button>
        </form>
    </GuestLayout>
</template>

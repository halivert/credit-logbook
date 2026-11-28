<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, useForm } from "@inertiajs/vue3"
import { usePasskeyVerify } from "@laravel/passkeys/vue"
import { passkeyErrorMessage } from "@/passkeys"

const form = useForm({
    password: "",
})

const passkey = usePasskeyVerify({
    routes: {
        options: route("passkey.confirm-options"),
        submit: route("passkey.confirm"),
    },
    onSuccess: (response) => {
        window.location.href = response.redirect ?? route("credit-cards.index")
    },
})

function submit() {
    form.post(route("password.confirm.store"), {
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Confirma tu contraseña" />

        <h1 class="text-sm leading-relaxed">Confirma tu contraseña</h1>
        <p class="mt-1 mb-4 text-sm text-muted">
            Por seguridad, escríbela de nuevo antes de continuar.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Confirmar</PrimaryButton
            >

            <template v-if="passkey.isSupported.value">
                <button
                    type="button"
                    class="nes-btn flex w-full justify-center items-center !mx-0"
                    :disabled="passkey.isLoading.value"
                    @click="passkey.verify"
                >
                    Confirmar con passkey
                </button>
                <InputError
                    :message="passkeyErrorMessage(passkey.errorInstance.value)"
                />
            </template>
        </form>
    </GuestLayout>
</template>

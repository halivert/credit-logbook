<script setup lang="ts">
import Checkbox from "@/Components/Checkbox.vue"
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, Link, useForm } from "@inertiajs/vue3"
import { usePasskeyVerify } from "@laravel/passkeys/vue"
import { passkeyErrorMessage } from "@/passkeys"

defineProps<{
    canResetPassword?: boolean
    canRegister?: boolean
    status?: string
}>()

const form = useForm({
    email: "",
    password: "",
    remember: false,
})

// A full page load after signing in, so the session and CSRF token are new
const passkey = usePasskeyVerify({
    autofill: true,
    remember: () => form.remember,
    onSuccess: (response) => {
        window.location.href = response.redirect ?? route("credit-cards.index")
    },
})

function submit() {
    form.post(route("login.store"), {
        onFinish: () => form.reset("password"),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <h1 class="mb-4 text-title">Iniciar sesión</h1>

        <p
            v-if="status"
            class="mb-4 text-small font-medium text-success"
        >
            {{ status }}
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username webauthn"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between gap-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-small text-muted"
                        >Recordarme</span
                    >
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-small text-primary text-right hover:underline"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Entrar</PrimaryButton
            >

            <template v-if="passkey.isSupported.value">
                <div
                    class="flex items-center gap-3 text-note text-muted"
                >
                    <span class="divider flex-1" />
                    o
                    <span class="divider flex-1" />
                </div>

                <button
                    type="button"
                    class="btn flex w-full justify-center items-center"
                    :disabled="passkey.isLoading.value"
                    @click="passkey.verify"
                >
                    Entrar con passkey
                </button>
                <InputError
                    :message="passkeyErrorMessage(passkey.errorInstance.value)"
                />
            </template>

            <p
                v-if="canRegister"
                class="text-center text-small text-muted"
            >
                ¿No tienes cuenta?
                <Link
                    :href="route('register')"
                    class="link"
                    >Regístrate</Link
                >
            </p>
        </form>
    </GuestLayout>
</template>

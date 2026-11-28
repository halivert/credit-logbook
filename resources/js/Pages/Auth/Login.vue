<script setup lang="ts">
import Checkbox from "@/Components/Checkbox.vue"
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, Link, useForm } from "@inertiajs/vue3"
import { usePasskeyVerify } from "@laravel/passkeys/vue"

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

        <h1 class="mb-4 text-lg font-semibold">Iniciar sesión</h1>

        <p
            v-if="status"
            class="mb-4 text-sm font-medium text-emerald-600 dark:text-emerald-400"
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

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >Recordarme</span
                    >
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
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
                    class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400"
                >
                    <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700" />
                    o
                    <span class="h-px flex-1 bg-gray-200 dark:bg-gray-700" />
                </div>

                <button
                    type="button"
                    class="flex w-full justify-center items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 ring-1 ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                    :disabled="passkey.isLoading.value"
                    @click="passkey.verify"
                >
                    Entrar con passkey
                </button>
                <InputError :message="passkey.error.value ?? undefined" />
            </template>

            <p
                v-if="canRegister"
                class="text-center text-sm text-gray-600 dark:text-gray-400"
            >
                ¿No tienes cuenta?
                <Link
                    :href="route('register')"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline"
                    >Regístrate</Link
                >
            </p>
        </form>
    </GuestLayout>
</template>

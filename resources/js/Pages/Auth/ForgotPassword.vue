<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, Link, useForm } from "@inertiajs/vue3"

defineProps<{
    status?: string
}>()

const form = useForm({
    email: "",
})

function submit() {
    form.post(route("password.email"))
}
</script>

<template>
    <GuestLayout>
        <Head title="Restablecer contraseña" />

        <h1 class="text-sm leading-relaxed">Restablecer contraseña</h1>
        <p class="mt-1 mb-4 text-sm text-muted">
            Te enviaremos un enlace a tu correo para elegir una nueva.
        </p>

        <p
            v-if="status"
            class="mb-4 text-sm font-medium text-success"
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
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Enviar enlace</PrimaryButton
            >

            <p class="text-center text-sm">
                <Link
                    :href="route('login')"
                    class="px-link"
                    >Volver a iniciar sesión</Link
                >
            </p>
        </form>
    </GuestLayout>
</template>

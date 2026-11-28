<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue"
import InputError from "@/Components/InputError.vue"
import InputLabel from "@/Components/InputLabel.vue"
import PrimaryButton from "@/Components/PrimaryButton.vue"
import TextInput from "@/Components/TextInput.vue"
import { Head, Link, useForm } from "@inertiajs/vue3"

const props = defineProps<{
    email: string
    token: string
    /** Coming from an invitation: choosing a password for the first time */
    invited?: boolean
}>()

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
})

function submit() {
    form.post(route("password.update"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    })
}
</script>

<template>
    <GuestLayout>
        <Head :title="invited ? 'Bienvenido' : 'Nueva contraseña'" />

        <h1 class="mb-4 text-title">
            {{ invited ? "Bienvenido, elige tu contraseña" : "Elige una nueva contraseña" }}
        </h1>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Nueva contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autofocus
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confírmala"
                />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <PrimaryButton
                class="w-full justify-center"
                :disabled="form.processing"
                >Guardar contraseña</PrimaryButton
            >

            <p v-if="invited" class="reading text-note text-muted text-center">
                Al guardar tu contraseña aceptas los
                <Link :href="route('terms')" class="link">términos de uso</Link>
                y el
                <Link :href="route('privacy')" class="link"
                    >aviso de privacidad</Link
                >.
            </p>
        </form>
    </GuestLayout>
</template>
